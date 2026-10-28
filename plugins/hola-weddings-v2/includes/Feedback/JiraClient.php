<?php

namespace HW\Feedback;

/**
 * Jira Cloud REST API v3 client using OAuth 2.0 Bearer tokens.
 *
 * Tokens are never passed to the browser — all API calls happen server-side.
 * The access token is refreshed automatically when expired.
 */
class JiraClient {

	private string $project_key;
	private string $default_issue_type;
	private string $default_priority;

	public function __construct() {
		$this->project_key        = $this->cfg( 'project_key' );
		$this->default_issue_type = $this->cfg( 'issue_type', 'Bug' );
		$this->default_priority   = $this->cfg( 'priority', 'Medium' );
	}

	// =========================================================================
	// Public API
	// =========================================================================

	/** True when OAuth is connected and a project key is set. */
	public function is_configured(): bool {
		return ( new JiraOAuth() )->is_configured()
			&& JiraTokenStore::is_connected()
			&& $this->project_key !== '';
	}

	/**
	 * Create a Jira issue from the sanitized payload and optionally attach screenshots.
	 *
	 * If the submitting WordPress user's email matches a Jira account in the project,
	 * the issue reporter is set accordingly (requires "Modify Reporter" project permission).
	 *
	 * @param array   $data        Sanitized feedback payload.
	 * @param array[] $screenshots Indexed array of file entries from get_file_params().
	 * @return array{ key: string, url: string }|\WP_Error
	 */
	public function create_issue( array $data, array $screenshots = [] ): array|\WP_Error {

		if ( ! $this->is_configured() ) {
			return new \WP_Error( 'jira_not_configured', 'Jira integration is not configured or not connected.' );
		}

		$site_label = (string) wp_parse_url( home_url(), PHP_URL_HOST );

		$body = [
			'fields' => [
				'project'     => [ 'key' => $this->project_key ],
				'summary'     => mb_substr( sanitize_text_field( $data['title'] ), 0, 255 ),
				'description' => $this->build_adf( $data ),
				'issuetype'   => [ 'name' => $this->resolve_issue_type( $data['type'] ?? '' ) ],
				'labels'      => array_filter( [ $site_label ] ),
			],
		];

		$priority = $this->resolve_priority( $data['priority'] ?? '' );
		if ( $priority ) {
			$body['fields']['priority'] = [ 'name' => $priority ];
		}

		// Reporter: prefer explicit account_id from the picker; fall back to WP user email lookup.
		$account_id = $data['reporter']['account_id'] ?? '';
		if ( $account_id === '' ) {
			$wp_email   = wp_get_current_user()->user_email ?? '';
			$account_id = $wp_email ? $this->find_jira_account_id( $wp_email ) : '';
		}
		if ( $account_id !== '' ) {
			$body['fields']['reporter'] = [ 'accountId' => $account_id ];
		}

		// Website URL custom field — look up field ID by name, assign same value as page_url.
		$website_url_field = $this->get_field_id_by_name( 'Website URL' );
		if ( $website_url_field !== '' && ! empty( $data['page_url'] ) ) {
			$body['fields'][ $website_url_field ] = esc_url_raw( $data['page_url'] );
		}

		// Sprint — assign to active sprint so the issue appears on the board, not just backlog.
		$sprint_id = $this->get_active_sprint_id();
		if ( $sprint_id > 0 ) {
			$body['fields']['customfield_10020'] = $sprint_id;
		}

		$response = $this->api_request( 'POST', '/rest/api/3/issue', $body );

		if ( is_wp_error( $response ) ) return $response;

		$issue_key = $response['key'] ?? '';
		if ( ! $issue_key ) {
			return new \WP_Error( 'jira_no_key', 'Jira returned no issue key.' );
		}

		$site_url  = rtrim( JiraTokenStore::get_site_url(), '/' );
		$issue_url = $site_url ? "{$site_url}/browse/{$issue_key}" : $issue_key;

		foreach ( $screenshots as $screenshot ) {
			$attach = $this->attach_file( $issue_key, $screenshot );
			if ( is_wp_error( $attach ) ) {
				$this->append_error( 'Screenshot attachment failed: ' . $attach->get_error_message() );
			}
		}

		return [ 'key' => $issue_key, 'url' => $issue_url ];
	}

	// =========================================================================
	// Additional public queries
	// =========================================================================

	/**
	 * Permanently delete a Jira issue by key.
	 * Jira returns 204 No Content on success.
	 *
	 * @param string $issue_key  e.g. "WEB-123".
	 * @return true|\WP_Error
	 */
	public function delete_issue( string $issue_key ): true|\WP_Error {

		if ( ! $this->is_configured() ) {
			return new \WP_Error( 'jira_not_configured', 'Jira integration is not configured.' );
		}

		$result = $this->api_request( 'DELETE', '/rest/api/3/issue/' . rawurlencode( $issue_key ) );

		// api_request returns [] for 204 No Content (empty body) — treat as success.
		return is_wp_error( $result ) ? $result : true;
	}

	/**
	 * Fetch Jira issues for the current site, ordered newest-first.
	 *
	 * When a "Website URL" custom field exists the query is scoped to the current
	 * site's hostname so issues from other sites are excluded. Falls back to all
	 * project issues when the field is not configured.
	 *
	 * @param int $page     1-based page number.
	 * @param int $per_page Results per page (max 50).
	 * @return array{ issues: array, total: int, page: int, hasMore: bool }|\WP_Error
	 */
	public function get_site_issues( int $page = 1, int $per_page = 50 ): array|\WP_Error {

		if ( ! $this->is_configured() ) {
			return new \WP_Error( 'jira_not_configured', 'Jira integration is not configured.' );
		}

		$per_page = min( $per_page, 50 );
		$start    = ( $page - 1 ) * $per_page;
		$domain   = (string) wp_parse_url( home_url(), PHP_URL_HOST );

		$jql = ( $domain !== '' )
			? sprintf( 'project = "%s" AND labels = "%s" ORDER BY created DESC', $this->project_key, $domain )
			: sprintf( 'project = "%s" ORDER BY created DESC', $this->project_key );

		// Use the current Atlassian search endpoint. /rest/api/3/issue/search is
		// mis-routed (treats "search" as an issue key) and /rest/api/2/search was
		// removed (410). /rest/api/3/search/jql is the replacement per CHANGE-2046.
		$query = http_build_query( [ 'jql' => $jql, 'startAt' => $start, 'maxResults' => $per_page ] );
		foreach ( [ 'summary', 'status', 'priority', 'issuetype', 'created' ] as $f ) {
			$query .= '&fields=' . rawurlencode( $f );
		}

		$response = $this->api_request( 'GET', '/rest/api/3/search/jql?' . $query );

		if ( is_wp_error( $response ) ) return $response;

		$site_url = rtrim( JiraTokenStore::get_site_url(), '/' );
		$issues   = [];

		foreach ( $response['issues'] ?? [] as $issue ) {
			$fields = $issue['fields'] ?? [];
			$key    = $issue['key']    ?? '';

			$issues[] = [
				'key'      => $key,
				'url'      => $site_url ? "{$site_url}/browse/{$key}" : $key,
				'summary'  => $fields['summary']              ?? '',
				'status'   => $fields['status']['name']       ?? '',
				'priority' => $fields['priority']['name']     ?? '',
				'type'     => $fields['issuetype']['name']    ?? '',
				'created'  => $fields['created']              ?? '',
			];
		}

		$total = $response['total'] ?? 0;

		return [
			'issues'  => $issues,
			'total'   => $total,
			'page'    => $page,
			'hasMore' => $total > $start + count( $issues ),
		];
	}

	/**
	 * Return all accessible Jira projects as [ 'KEY' => 'Project Name (KEY)' ].
	 * Used to populate the project select box in the admin settings tab.
	 *
	 * @return array<string,string>|\WP_Error
	 */
	public function get_projects(): array|\WP_Error {

		// Only requires an active OAuth connection — no project key needed yet.
		if ( ! ( new JiraOAuth() )->is_configured() || ! JiraTokenStore::is_connected() ) {
			return new \WP_Error( 'jira_not_connected', 'Jira is not connected.' );
		}

		$path     = '/rest/api/3/project/search?' . http_build_query( [
			'maxResults' => 100,
			'orderBy'    => 'name',
		] );
		$response = $this->api_request( 'GET', $path );

		if ( is_wp_error( $response ) ) return $response;

		$options = [];
		foreach ( $response['values'] ?? [] as $project ) {
			$key  = $project['key']  ?? '';
			$name = $project['name'] ?? $key;
			if ( $key !== '' ) {
				$options[ $key ] = "{$name} ({$key})";
			}
		}

		return $options;
	}

	/**
	 * Fetch users assignable to issues in a project.
	 * Returns [ [ 'accountId' => '...', 'displayName' => '...' ], ... ].
	 * Results are not cached here — callers should use a transient if needed.
	 *
	 * @param string $project_key Defaults to the configured project key.
	 * @return array|\WP_Error
	 */
	public function get_project_members( string $project_key = '' ): array|\WP_Error {

		if ( ! ( new JiraOAuth() )->is_configured() || ! JiraTokenStore::is_connected() ) {
			return new \WP_Error( 'jira_not_connected', 'Jira is not connected.' );
		}

		if ( $project_key === '' ) {
			$project_key = $this->project_key;
		}

		if ( $project_key === '' ) {
			return new \WP_Error( 'no_project', 'No project key configured.' );
		}

		// Fetch the list of roles for the project (keys = role names, values = role URLs).
		$roles = $this->api_request(
			'GET',
			'/rest/api/3/project/' . rawurlencode( $project_key ) . '/role'
		);
		if ( is_wp_error( $roles ) ) return $roles;

		// Walk each role and collect its individual user actors.
		$members = [];
		$seen    = [];

		// Roles populated exclusively by Jira apps/add-ons — not real project members.
		$system_roles = [ 'atlassian-addons-project-access' ];

		foreach ( $roles as $role_name => $role_url ) {
			if ( in_array( $role_name, $system_roles, true ) ) continue;

			// Extract the numeric role ID from the end of the URL.
			$role_id = basename( (string) $role_url );
			if ( ! ctype_digit( $role_id ) ) continue;

			$role = $this->api_request(
				'GET',
				'/rest/api/3/project/' . rawurlencode( $project_key ) . '/role/' . $role_id
			);
			if ( is_wp_error( $role ) ) continue;

			foreach ( $role['actors'] ?? [] as $actor ) {
				// Skip group actors — we only want individually-assigned users.
				if ( ( $actor['type'] ?? '' ) !== 'atlassian-user-role-actor' ) continue;

				$account_id = $actor['actorUser']['accountId'] ?? '';
				$name       = $actor['displayName']            ?? '';

				if ( $account_id !== '' && $name !== '' && ! isset( $seen[ $account_id ] ) ) {
					$seen[ $account_id ] = true;
					$members[] = [ 'accountId' => $account_id, 'displayName' => $name ];
				}
			}
		}

		usort( $members, fn( $a, $b ) => strcmp( $a['displayName'], $b['displayName'] ) );

		return $members;
	}

	/**
	 * Return available issue types as [ 'Bug' => 'Bug', 'Task' => 'Task', ... ].
	 *
	 * When a project key is provided the list is scoped to that project's issue
	 * type scheme, which is more accurate. Falls back to all instance-level
	 * issue types when no project key is given. Subtask types are excluded.
	 *
	 * @param string $project_key Optional project key to scope the results.
	 * @return array<string,string>|\WP_Error
	 */
	public function get_issue_types( string $project_key = '' ): array|\WP_Error {

		if ( ! ( new JiraOAuth() )->is_configured() || ! JiraTokenStore::is_connected() ) {
			return new \WP_Error( 'jira_not_connected', 'Jira is not connected.' );
		}

		if ( $project_key !== '' ) {
			$response = $this->api_request(
				'GET',
				'/rest/api/3/project/' . rawurlencode( $project_key ) . '?expand=issueTypes'
			);
			if ( is_wp_error( $response ) ) return $response;
			$types = $response['issueTypes'] ?? [];
		} else {
			$response = $this->api_request( 'GET', '/rest/api/3/issuetype' );
			if ( is_wp_error( $response ) ) return $response;
			$types = array_values( $response );
		}

		$options = [];
		foreach ( $types as $type ) {
			$name = $type['name'] ?? '';
			if ( $name !== '' && empty( $type['subtask'] ) ) {
				$options[ $name ] = $name;
			}
		}

		return $options;
	}

	// =========================================================================
	// Private helpers
	// =========================================================================

	/**
	 * Return a valid access token, refreshing it first if it has expired.
	 *
	 * @return string|\WP_Error
	 */
	private function get_valid_token(): string|\WP_Error {

		if ( JiraTokenStore::is_expired() ) {
			$result = ( new JiraOAuth() )->refresh();
			if ( is_wp_error( $result ) ) return $result;
		}

		$token = JiraTokenStore::get_access_token();

		if ( $token === '' ) {
			return new \WP_Error(
				'jira_not_connected',
				'Jira is not connected. Please authorize via Network Settings → Jira.'
			);
		}

		return $token;
	}

	/**
	 * Make an authenticated request to the Jira Cloud REST API.
	 *
	 * @param string $method HTTP verb.
	 * @param string $path   API path, e.g. '/rest/api/3/issue'.
	 * @param array  $body   JSON-encodable body (ignored for GET).
	 * @return array|\WP_Error Decoded JSON response on success.
	 */
	private function api_request( string $method, string $path, array $body = [] ): array|\WP_Error {

		$token = $this->get_valid_token();
		if ( is_wp_error( $token ) ) return $token;

		$cloud_id = JiraTokenStore::get_cloud_id();
		$url      = "https://api.atlassian.com/ex/jira/{$cloud_id}{$path}";

		$args = [
			'method'  => $method,
			'headers' => [
				'Authorization' => "Bearer {$token}",
				'Content-Type'  => 'application/json',
				'Accept'        => 'application/json',
			],
			'timeout' => 20,
		];

		if ( $method !== 'GET' && ! empty( $body ) ) {
			$args['body'] = wp_json_encode( $body );
		}

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) return $response;

		$code     = (int) wp_remote_retrieve_response_code( $response );
		$raw_body = wp_remote_retrieve_body( $response );
		$data     = json_decode( $raw_body, true ) ?? [];

		if ( $code < 200 || $code >= 300 ) {
			$msg = $data['errorMessages'][0] ?? $data['message'] ?? "Jira returned HTTP {$code}";
			return new \WP_Error( 'jira_api_error', $msg, [ 'http_code' => $code ] );
		}

		return $data;
	}

	/**
	 * Attach a screenshot to an existing Jira issue via multipart upload.
	 * wp_remote_post does not natively support file uploads, so the body is
	 * constructed manually.
	 *
	 * @param string $issue_key Jira issue key (e.g. "WEB-123").
	 * @param array  $file      File entry from $_FILES / get_file_params().
	 * @return true|\WP_Error
	 */
	private function attach_file( string $issue_key, array $file ): true|\WP_Error {

		$token = $this->get_valid_token();
		if ( is_wp_error( $token ) ) return $token;

		$cloud_id = JiraTokenStore::get_cloud_id();
		$path     = $file['tmp_name'];
		$name     = sanitize_file_name( $file['name'] ?? 'screenshot.png' );
		$mime     = $file['type'] ?? 'image/png';
		$url      = "https://api.atlassian.com/ex/jira/{$cloud_id}/rest/api/3/issue/{$issue_key}/attachments";
		$boundary = 'HWFeedback' . bin2hex( random_bytes( 8 ) );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$file_data = file_get_contents( $path );
		if ( $file_data === false ) {
			return new \WP_Error( 'read_error', 'Could not read screenshot file.' );
		}

		$body  = "--{$boundary}\r\n";
		$body .= "Content-Disposition: form-data; name=\"file\"; filename=\"{$name}\"\r\n";
		$body .= "Content-Type: {$mime}\r\n";
		$body .= "Content-Transfer-Encoding: binary\r\n\r\n";
		$body .= $file_data . "\r\n";
		$body .= "--{$boundary}--\r\n";

		$response = wp_remote_post( $url, [
			'headers' => [
				'Authorization'     => "Bearer {$token}",
				'X-Atlassian-Token' => 'no-check',
				'Content-Type'      => "multipart/form-data; boundary={$boundary}",
			],
			'body'    => $body,
			'timeout' => 30,
		] );

		if ( is_wp_error( $response ) ) return $response;

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			return new \WP_Error( 'attach_failed', "Attachment upload returned HTTP {$code}." );
		}

		return true;
	}

	/**
	 * Look up a Jira accountId by email address.
	 * Returns empty string when the user is not found or on any error —
	 * reporter assignment is always best-effort and non-fatal.
	 */
	private function find_jira_account_id( string $email ): string {

		$token = $this->get_valid_token();
		if ( is_wp_error( $token ) ) return '';

		$cloud_id = JiraTokenStore::get_cloud_id();
		$url      = add_query_arg(
			[ 'query' => $email, 'maxResults' => 1 ],
			"https://api.atlassian.com/ex/jira/{$cloud_id}/rest/api/3/user/search"
		);

		$response = wp_remote_get( $url, [
			'headers' => [
				'Authorization' => "Bearer {$token}",
				'Accept'        => 'application/json',
			],
			'timeout' => 10,
		] );

		if ( is_wp_error( $response ) ) return '';
		if ( (int) wp_remote_retrieve_response_code( $response ) !== 200 ) return '';

		$data = json_decode( wp_remote_retrieve_body( $response ), true ) ?? [];

		return $data[0]['accountId'] ?? '';
	}

	// ── ADF builder ───────────────────────────────────────────────────────────

	/**
	 * Build an Atlassian Document Format (ADF) description object.
	 * Required by Jira Cloud REST API v3.
	 */
	private function build_adf( array $data ): array {

		$content = [];

		if ( ! empty( $data['description'] ) ) {
			$content[] = [
				'type'    => 'paragraph',
				'content' => [ [ 'type' => 'text', 'text' => $data['description'] ] ],
			];
			$content[] = [ 'type' => 'rule' ];
		}

		$content[] = [
			'type'    => 'heading',
			'attrs'   => [ 'level' => 3 ],
			'content' => [ [ 'type' => 'text', 'text' => 'Technical Details' ] ],
		];

		$rows = [];

		$details = [
			[ 'Page URL',    $data['page_url']   ?? '' ],
			[ 'Browser',     $data['user_agent']  ?? '' ],
			[ 'Screen',      isset( $data['screen'] )   ? "{$data['screen']['width']} × {$data['screen']['height']}" : '' ],
			[ 'Viewport',    isset( $data['viewport'] ) ? "{$data['viewport']['width']} × {$data['viewport']['height']}" : '' ],
			[ 'Pixel Ratio', (string) ( $data['device_pixel_ratio'] ?? '' ) ],
			[ 'Referrer',    $data['referrer']    ?? '' ],
			[ 'Timestamp',   $data['timestamp']   ?? '' ],
			[ 'Reporter',    $this->format_reporter( $data['reporter'] ?? [] ) ],
			[ 'Environment', $data['app_context']['environment'] ?? '' ],
			[ 'App Version', $data['app_context']['version']     ?? '' ],
			[ 'Priority',    $data['priority'] ?? '' ],
			[ 'Type',        $data['type']     ?? '' ],
		];

		foreach ( $details as [ $label, $value ] ) {
			if ( (string) $value === '' ) continue;
			$rows[] = $this->adf_table_row( $label, (string) $value );
		}

		if ( $rows ) {
			$content[] = [
				'type'    => 'table',
				'attrs'   => [ 'isNumberColumnEnabled' => false, 'layout' => 'default' ],
				'content' => $rows,
			];
		}

		return [ 'type' => 'doc', 'version' => 1, 'content' => $content ];
	}

	private function adf_table_row( string $label, string $value ): array {
		return [
			'type'    => 'tableRow',
			'content' => [
				[
					'type'    => 'tableCell',
					'attrs'   => [ 'colwidth' => [ 160 ] ],
					'content' => [ [
						'type'    => 'paragraph',
						'content' => [ [
							'type'  => 'text',
							'text'  => $label,
							'marks' => [ [ 'type' => 'strong' ] ],
						] ],
					] ],
				],
				[
					'type'    => 'tableCell',
					'content' => [ [
						'type'    => 'paragraph',
						'content' => [ [ 'type' => 'text', 'text' => $value ] ],
					] ],
				],
			],
		];
	}

	private function format_reporter( array $reporter ): string {
		$parts = array_filter( [
			$reporter['name']  ?? '',
			! empty( $reporter['email'] ) ? '<' . $reporter['email'] . '>' : '',
		] );
		return implode( ' ', $parts );
	}

	// ── Mapping resolvers ─────────────────────────────────────────────────────

	/**
	 * Find a Jira custom field ID by its display name (case-insensitive).
	 * Result is cached for 24 hours so we don't hit /rest/api/3/field on every submission.
	 */
	private function get_field_id_by_name( string $field_name ): string {
		$cache_key = 'hw_jira_field_id_' . md5( strtolower( $field_name ) );
		$cached    = get_transient( $cache_key );
		if ( $cached !== false ) return (string) $cached;

		$fields = $this->api_request( 'GET', '/rest/api/3/field' );
		if ( is_wp_error( $fields ) ) return '';

		$id = '';
		foreach ( $fields as $field ) {
			if ( strcasecmp( $field['name'] ?? '', $field_name ) === 0 ) {
				$id = $field['id'] ?? '';
				break;
			}
		}

		set_transient( $cache_key, $id, DAY_IN_SECONDS );
		return $id;
	}

	/**
	 * Return the ID of the active sprint for the configured project, or 0 if none.
	 * Cached for 1 hour — sprint changes are infrequent.
	 */
	private function get_active_sprint_id(): int {
		$cache_key = 'hw_jira_active_sprint_' . md5( $this->project_key );
		$cached    = get_transient( $cache_key );
		if ( $cached !== false ) return (int) $cached;

		$boards = $this->api_request(
			'GET',
			'/rest/agile/1.0/board?' . http_build_query( [
				'projectKeyOrId' => $this->project_key,
				'maxResults'     => 1,
			] )
		);

		$sprint_id = 0;

		if ( ! is_wp_error( $boards ) ) {
			$board_id = (int) ( $boards['values'][0]['id'] ?? 0 );

			if ( $board_id > 0 ) {
				$sprints = $this->api_request(
					'GET',
					"/rest/agile/1.0/board/{$board_id}/sprint?" . http_build_query( [ 'state' => 'active' ] )
				);

				if ( ! is_wp_error( $sprints ) ) {
					$sprint_id = (int) ( $sprints['values'][0]['id'] ?? 0 );
				}
			}
		}

		set_transient( $cache_key, $sprint_id, HOUR_IN_SECONDS );
		return $sprint_id;
	}

	private function resolve_issue_type( string $frontend_type ): string {
		return $frontend_type !== '' ? $frontend_type : ( $this->default_issue_type ?: 'Bug' );
	}

	private function resolve_priority( string $frontend_priority ): string {
		return $frontend_priority !== '' ? $frontend_priority : ( $this->default_priority ?: 'Medium' );
	}

	private function cfg( string $key, string $default = '' ): string {
		$constants = [
			'project_key' => 'HW_JIRA_PROJECT_KEY',
			'issue_type'  => 'HW_JIRA_ISSUE_TYPE',
			'priority'    => 'HW_JIRA_DEFAULT_PRIORITY',
		];
		$constant = $constants[ $key ] ?? '';
		if ( $constant && defined( $constant ) ) return (string) constant( $constant );
		return (string) get_network_option( null, "hw_jira_{$key}", $default );
	}

	private function append_error( string $message ): void {
		$errors = get_option( 'hw_feedback_errors', [] );
		array_unshift( $errors, [
			'message' => $message,
			'time'    => current_time( 'mysql' ),
		] );
		update_option( 'hw_feedback_errors', array_slice( $errors, 0, 20 ), false );
	}
}
