<?php
/**
 * Network-level Jira OAuth settings tab.
 *
 * Section 1 — OAuth App Credentials  (client_id via data[], client_secret masked)
 * Section 2 — Connection Status       (connect / disconnect links)
 * Section 3 — Configuration           (project key, issue type, priority, allowed types)
 */

use HW\Feedback\JiraClient;
use HW\Feedback\JiraOAuth;
use HW\Feedback\JiraTokenStore;
use HW\UI\Forms\Controls\Password;
use HW\UI\Forms\Controls\Select;
use HW\UI\Forms\Controls\Text;
use HW\UI\Forms\Layouts\Grid;
use HW\UI\Forms\Layouts\Heading;

$is_connected = JiraTokenStore::is_connected();
$site_name    = JiraTokenStore::get_site_name();
$site_url     = JiraTokenStore::get_site_url();
$redirect_uri = JiraOAuth::get_redirect_uri();

$client_id_saved     = (string) get_network_option( null, 'hw_jira_client_id', '' );
$client_secret_saved = (string) get_network_option( null, 'hw_jira_client_secret', '' );
$client_id_const     = defined( 'HW_JIRA_CLIENT_ID' );
$client_secret_const = defined( 'HW_JIRA_CLIENT_SECRET' );

$connect_url    = wp_nonce_url( admin_url( 'admin-post.php?action=hw_jira_connect' ),    'hw_jira_connect' );
$disconnect_url = wp_nonce_url( admin_url( 'admin-post.php?action=hw_jira_disconnect' ), 'hw_jira_disconnect' );

$current_project_key = (string) get_network_option( null, 'hw_jira_project_key', '' );

// Fetch Jira projects and issue types for select boxes (only when connected).
$jira_projects    = null;
$jira_issue_types = null;
if ( $is_connected ) {
	$client = new JiraClient();

	$fetched = $client->get_projects();
	if ( ! is_wp_error( $fetched ) && ! empty( $fetched ) ) {
		$jira_projects = $fetched;
	}

	$fetched_types = $client->get_issue_types( $current_project_key );
	if ( ! is_wp_error( $fetched_types ) && ! empty( $fetched_types ) ) {
		$jira_issue_types = $fetched_types;
	}
}

// URL-query notices (set by the action handlers after redirect).
$notice_connected    = ! empty( $_GET['jira_connected'] );
$notice_disconnected = ! empty( $_GET['jira_disconnected'] );
$notice_error        = ! empty( $_GET['jira_error'] )
	? sanitize_text_field( urldecode( wp_unslash( $_GET['jira_error'] ) ) )
	: '';

$error_messages = [
	'app_not_configured' => __( 'Enter a Client ID and Client Secret before connecting.', 'hola-wedding' ),
];
?>

<?php if ( $notice_connected ) : ?>
	<div class="alert alert-success mb-4">
		<i class="fas fa-check-circle"></i>
		<?php esc_html_e( 'Jira connected successfully.', 'hola-wedding' ); ?>
	</div>
<?php elseif ( $notice_disconnected ) : ?>
	<div class="alert alert-info mb-4">
		<i class="fas fa-info-circle"></i>
		<?php esc_html_e( 'Jira disconnected. Tokens have been removed.', 'hola-wedding' ); ?>
	</div>
<?php elseif ( $notice_error !== '' ) : ?>
	<div class="alert alert-danger mb-4">
		<i class="fas fa-exclamation-triangle"></i>
		<?php echo esc_html( $error_messages[ $notice_error ] ?? $notice_error ); ?>
	</div>
<?php endif; ?>

<?php
// ── Section 1: OAuth App Credentials ─────────────────────────────────────────
Heading::make()
	->heading( __( 'OAuth App Credentials', 'hola-wedding' ) )
	->subHeading( __( 'Create an OAuth 2.0 (3LO) app at developer.atlassian.com/console/myapps and register the callback URL shown below.', 'hola-wedding' ) )
	->tag( 'h5' )
	->render();
?>

<div class="form-group mb-3">
	<label class="form-label"><?php esc_html_e( 'OAuth Callback URL', 'hola-wedding' ); ?></label>
	<div class="input-group input-group-sm" style="max-width:620px">
		<input
			type="text"
			class="form-control font-monospace"
			value="<?php echo esc_attr( $redirect_uri ); ?>"
			readonly
			onclick="this.select()"
		>
		<button
			type="button"
			class="btn btn-outline-secondary"
			onclick="navigator.clipboard.writeText('<?php echo esc_js( $redirect_uri ); ?>')"
			title="<?php esc_attr_e( 'Copy to clipboard', 'hola-wedding' ); ?>"
		>
			<i class="fas fa-copy"></i>
		</button>
	</div>
	<small class="form-text text-muted">
		<?php esc_html_e( 'Register this exact URL in your Atlassian app. Required scopes:', 'hola-wedding' ); ?>
		<code>read:jira-user write:jira-work read:jira-work offline_access</code>
	</small>
</div>

<?php
// Client ID
if ( $client_id_const ) : ?>
	<div class="form-group mb-3">
		<label class="form-label"><?php esc_html_e( 'Client ID', 'hola-wedding' ); ?></label>
		<p class="text-primary mb-0 small"><i class="fas fa-lock"></i> <?php esc_html_e( 'Defined via HW_JIRA_CLIENT_ID constant.', 'hola-wedding' ); ?></p>
	</div>
<?php else :
	Text::make( 'data[hw_jira_client_id]' )
		->label( __( 'Client ID', 'hola-wedding' ) )
		->value( $client_id_saved )
		->description( __( 'From your Atlassian OAuth app.', 'hola-wedding' ) )
		->render();
endif;

// Client Secret
if ( $client_secret_const ) : ?>
	<div class="form-group mb-3">
		<label class="form-label"><?php esc_html_e( 'Client Secret', 'hola-wedding' ); ?></label>
		<p class="text-primary mb-0 small"><i class="fas fa-lock"></i> <?php esc_html_e( 'Defined via HW_JIRA_CLIENT_SECRET constant.', 'hola-wedding' ); ?></p>
	</div>
<?php else :
	Password::make( 'hw_jira_client_secret_input' )
		->label( __( 'Client Secret', 'hola-wedding' ) )
		->saved( $client_secret_saved !== '' )
		->render();
endif;
?>

<?php
// ── Section 2: Connection Status ──────────────────────────────────────────────
Heading::make()
	->heading( __( 'Jira Connection', 'hola-wedding' ) )
	->tag( 'h5' )
	->render();

if ( $is_connected ) : ?>
	<div class="d-flex align-items-center gap-3 mb-3" style="flex-wrap:wrap">
		<span class="badge" style="background:#2e7d32;font-size:.85rem;padding:.4em .75em">
			<i class="fas fa-circle" style="font-size:.55rem;vertical-align:middle"></i>
			<?php esc_html_e( 'Connected', 'hola-wedding' ); ?>
		</span>

		<?php if ( $site_name ) : ?>
			<span>
				<strong><?php echo esc_html( $site_name ); ?></strong>
				<?php if ( $site_url ) : ?>
					&mdash;
					<a href="<?php echo esc_url( $site_url ); ?>" target="_blank" rel="noopener noreferrer">
						<?php echo esc_html( $site_url ); ?>
					</a>
				<?php endif; ?>
			</span>
		<?php endif; ?>

		<a
			href="<?php echo esc_url( $disconnect_url ); ?>"
			class="btn btn-sm btn-outline-danger"
			onclick="return confirm('<?php esc_attr_e( 'Disconnect Jira? Stored tokens will be removed.', 'hola-wedding' ); ?>')"
		>
			<i class="fas fa-unlink"></i> <?php esc_html_e( 'Disconnect', 'hola-wedding' ); ?>
		</a>
	</div>

<?php else : ?>
	<p class="text-muted small mb-3">
		<?php esc_html_e( 'Not connected. Save your Client ID and Secret above, then click the button below.', 'hola-wedding' ); ?>
	</p>
	<a href="<?php echo esc_url( $connect_url ); ?>" class="btn btn-primary">
		<i class="fas fa-plug"></i> <?php esc_html_e( 'Connect to Jira', 'hola-wedding' ); ?>
	</a>

<?php endif; ?>

<?php
// ── Section 3: Configuration ──────────────────────────────────────────────────
Heading::make()
	->heading( __( 'Configuration', 'hola-wedding' ) )
	->tag( 'h5' )
	->render();

$current_issue_type   = (string) get_network_option( null, 'hw_jira_issue_type', 'Bug' );
$current_allowed_types = get_network_option( null, 'hw_jira_allowed_issue_types', [] );
if ( ! is_array( $current_allowed_types ) ) {
	$current_allowed_types = [];
}

if ( $jira_projects ) :
	// Connected — Alpine-reactive project + issue-type selects.
	$jira_nonce = wp_create_nonce( 'hw_jira_ajax' );
	?>
	<script>
	document.addEventListener('alpine:init', function() {
		Alpine.data('jiraConfig', function() {
			return {
				issueTypes:   <?= wp_json_encode( $jira_issue_types ?? (object) [] ) ?>,
				currentType:  <?= wp_json_encode( $current_issue_type ) ?>,
				allowedTypes: <?= wp_json_encode( $current_allowed_types ) ?>,
				loading:      false,
				typesOpen:    false,
				toggleType: function(key) {
					var idx = this.allowedTypes.indexOf(key);
					if (idx === -1) this.allowedTypes.push(key);
					else this.allowedTypes.splice(idx, 1);
				},
				init: function() {
					// Fetch issue types on load when the server returned none for the saved project.
					if ( Object.keys(this.issueTypes).length === 0 && <?= wp_json_encode( $current_project_key ) ?> ) {
						this.updateIssueTypes( <?= wp_json_encode( $current_project_key ) ?> );
					}
				},
				updateIssueTypes: async function(key) {
					if (!key) return;
					this.loading = true;
					try {
						var r = await fetch(
							<?= wp_json_encode( admin_url( 'admin-ajax.php' ) ) ?> +
							'?action=hw_jira_get_issue_types' +
							'&project_key=' + encodeURIComponent(key) +
							'&_ajax_nonce=' + <?= wp_json_encode( $jira_nonce ) ?>
						);
						var d = await r.json();
						if (d.success) {
							this.issueTypes = d.data;
							// Keep default type if still valid, otherwise pick first.
							if (!Object.prototype.hasOwnProperty.call(this.issueTypes, this.currentType)) {
								this.currentType = Object.keys(this.issueTypes)[0] || '';
							}
							// Drop any allowed types that no longer exist in the new project.
							this.allowedTypes = this.allowedTypes.filter(
								t => Object.prototype.hasOwnProperty.call(this.issueTypes, t)
							);
						}
					} catch(e) {} finally { this.loading = false; }
				}
			};
		});
	});
	</script>

	<div class="row g-3 mb-3" x-data="jiraConfig">
		<?php
		Select::make( 'data[hw_jira_project_key]' )
			->label( __( 'Project', 'hola-wedding' ) )
			->value( $current_project_key )
			->options( $jira_projects )
			->description( __( 'Jira project where issues will be created.', 'hola-wedding' ) )
			->attributes( [ '@change' => 'updateIssueTypes($event.target.value)' ] )
			->wrapperClass( 'col-6' )
			->render();
		?>

		<div class="form-group col-6">
			<label for="hw_jira_issue_type" class="form-label"><?php esc_html_e( 'Default Issue Type', 'hola-wedding' ); ?></label>
			<select
				id="hw_jira_issue_type"
				name="data[hw_jira_issue_type]"
				class="form-select"
				x-model="currentType"
				:disabled="loading"
			>
				<template x-for="[key, label] in Object.entries(issueTypes)" :key="key">
					<option :value="key" x-text="label"></option>
				</template>
			</select>
			<small class="form-text d-block">
				<span x-show="loading"><?php esc_html_e( 'Loading…', 'hola-wedding' ); ?></span>
				<span x-show="!loading"><?php esc_html_e( 'Used when no type mapping matches.', 'hola-wedding' ); ?></span>
			</small>
		</div>

		<?php
		Select::make( 'data[hw_jira_default_priority]' )
			->label( __( 'Default Priority', 'hola-wedding' ) )
			->value( (string) get_network_option( null, 'hw_jira_default_priority', 'Medium' ) )
			->options( [
				'Highest' => __( 'Highest', 'hola-wedding' ),
				'High'    => __( 'High',    'hola-wedding' ),
				'Medium'  => __( 'Medium',  'hola-wedding' ),
				'Low'     => __( 'Low',     'hola-wedding' ),
				'Lowest'  => __( 'Lowest',  'hola-wedding' ),
			] )
			->description( __( 'Used when no priority mapping matches.', 'hola-wedding' ) )
			->wrapperClass( 'col-6' )
			->render();
		?>

		<div class="form-group col-6" style="position:relative" @click.outside="typesOpen = false">
			<label class="form-label"><?php esc_html_e( 'Allowed Issue Types', 'hola-wedding' ); ?></label>

			<div
				class="form-control d-flex flex-wrap gap-1 align-items-center"
				style="min-height:38px;cursor:pointer;padding:4px 8px;"
				@click="typesOpen = !typesOpen"
				:class="{ 'disabled': loading }"
			>
				<template x-for="type in allowedTypes" :key="type">
					<span class="badge bg-primary d-inline-flex align-items-center gap-1" style="font-size:.8rem;font-weight:500">
						<span x-text="issueTypes[type] || type"></span>
						<button
							type="button"
							class="btn-close btn-close-white"
							style="font-size:.6rem"
							@click.stop="allowedTypes = allowedTypes.filter(t => t !== type)"
						></button>
					</span>
				</template>
				<span x-show="allowedTypes.length === 0" class="text-muted small"><?php esc_html_e( 'All types allowed', 'hola-wedding' ); ?></span>
				<i class="fas fa-chevron-down ms-auto" style="font-size:.7rem;opacity:.5" :style="typesOpen ? 'transform:rotate(180deg)' : ''"></i>
			</div>

			<div
				x-show="typesOpen"
				class="border rounded bg-white shadow-sm w-100"
				style="position:absolute;z-index:1050;max-height:220px;overflow-y:auto"
			>
				<div class="list-group list-group-flush">
					<template x-for="[key, label] in Object.entries(issueTypes)" :key="key">
						<button
							type="button"
							class="list-group-item list-group-item-action d-flex align-items-center gap-2"
							:class="{ 'active': allowedTypes.includes(key) }"
							style="font-size:.875rem;border-left:0;border-right:0"
							@click.stop="toggleType(key)"
						>
							<i class="fas fa-fw" :class="allowedTypes.includes(key) ? 'fa-check-square' : 'fa-square'" style="font-size:.8rem"></i>
							<span x-text="label"></span>
						</button>
					</template>
				</div>
			</div>

			<template x-for="type in allowedTypes" :key="type">
				<input type="hidden" name="hw_jira_allowed_issue_types[]" :value="type">
			</template>

			<small class="form-text d-block">
				<span x-show="loading"><?php esc_html_e( 'Loading…', 'hola-wedding' ); ?></span>
				<span x-show="!loading"><?php esc_html_e( 'Issue types shown in the feedback widget. Leave empty to allow all.', 'hola-wedding' ); ?></span>
			</small>
		</div>
	</div>

<?php else :
	// Not connected — plain text fallbacks.
	Grid::make()->columns( 2 )->schema( [
		Text::make( 'data[hw_jira_project_key]' )
			->label( __( 'Project Key', 'hola-wedding' ) )
			->value( $current_project_key )
			->description( __( 'Short key shown in issue URLs, e.g. WEB for WEB-123. Connect to Jira above to pick from a list.', 'hola-wedding' ) ),
		Text::make( 'data[hw_jira_issue_type]' )
			->label( __( 'Default Issue Type', 'hola-wedding' ) )
			->value( $current_issue_type )
			->description( __( 'Used when no type mapping matches, e.g. Bug, Task, Story.', 'hola-wedding' ) ),
		Select::make( 'data[hw_jira_default_priority]' )
			->label( __( 'Default Priority', 'hola-wedding' ) )
			->value( (string) get_network_option( null, 'hw_jira_default_priority', 'Medium' ) )
			->options( [
				'Highest' => __( 'Highest', 'hola-wedding' ),
				'High'    => __( 'High',    'hola-wedding' ),
				'Medium'  => __( 'Medium',  'hola-wedding' ),
				'Low'     => __( 'Low',     'hola-wedding' ),
				'Lowest'  => __( 'Lowest',  'hola-wedding' ),
			] )
			->description( __( 'Used when no priority mapping matches.', 'hola-wedding' ) ),
	] )->render();
endif;
