<?php

namespace HW\Feedback;

/**
 * Validates and sanitizes incoming feedback report payloads and screenshot uploads.
 */
class FeedbackValidator {

	const MAX_SCREENSHOT_BYTES = 5 * 1024 * 1024; // 5 MB
	const ALLOWED_MIME_TYPES   = [ 'image/png', 'image/jpeg' ];

	/**
	 * Validate required fields and basic format constraints.
	 *
	 * @param array $payload Decoded JSON payload from the request.
	 * @return true|\WP_Error
	 */
	public function validate( array $payload ): true|\WP_Error {

		if ( empty( $payload['title'] ) || ! is_string( $payload['title'] ) ) {
			return new \WP_Error( 'missing_title', 'Issue title is required.', [ 'status' => 400 ] );
		}

		if ( mb_strlen( $payload['title'] ) > 255 ) {
			return new \WP_Error( 'title_too_long', 'Title must be 255 characters or fewer.', [ 'status' => 400 ] );
		}

		if ( ! empty( $payload['page_url'] ) && ! filter_var( $payload['page_url'], FILTER_VALIDATE_URL ) ) {
			return new \WP_Error( 'invalid_url', 'Invalid page URL.', [ 'status' => 400 ] );
		}

		if ( ! empty( $payload['reporter']['email'] ) && ! is_email( $payload['reporter']['email'] ) ) {
			return new \WP_Error( 'invalid_email', 'Invalid reporter email.', [ 'status' => 400 ] );
		}

		if ( ! empty( $payload['referrer'] ) && ! filter_var( $payload['referrer'], FILTER_VALIDATE_URL ) ) {
			// Non-fatal: clear rather than reject
			$payload['referrer'] = '';
		}

		return true;
	}

	/**
	 * Sanitize all text fields in the payload and return a clean array.
	 *
	 * @param array $payload Raw decoded payload.
	 * @return array Sanitized payload ready for use.
	 */
	public function sanitize( array $payload ): array {

		return [
			'title'              => sanitize_text_field( $payload['title'] ?? '' ),
			'description'        => sanitize_textarea_field( $payload['description'] ?? '' ),
			'priority'           => sanitize_text_field( $payload['priority'] ?? 'Medium' ),
			'type'               => sanitize_text_field( $payload['type'] ?? 'Bug' ),
			'page_url'           => esc_url_raw( $payload['page_url'] ?? '' ),
			'user_agent'         => sanitize_text_field( mb_substr( $payload['user_agent'] ?? '', 0, 500 ) ),
			'screen'             => [
				'width'  => absint( $payload['screen']['width']  ?? 0 ),
				'height' => absint( $payload['screen']['height'] ?? 0 ),
			],
			'viewport'           => [
				'width'  => absint( $payload['viewport']['width']  ?? 0 ),
				'height' => absint( $payload['viewport']['height'] ?? 0 ),
			],
			'device_pixel_ratio' => round( (float) ( $payload['device_pixel_ratio'] ?? 1 ), 2 ),
			'referrer'           => esc_url_raw( $payload['referrer'] ?? '' ),
			'timestamp'          => sanitize_text_field( $payload['timestamp'] ?? '' ),
			'reporter'           => [
				'name'       => sanitize_text_field( $payload['reporter']['name']      ?? '' ),
				'email'      => sanitize_email(      $payload['reporter']['email']     ?? '' ),
				'account_id' => sanitize_text_field( $payload['reporter']['accountId'] ?? '' ),
			],
			'app_context'        => [
				'environment' => sanitize_text_field( $payload['app_context']['environment'] ?? '' ),
				'version'     => sanitize_text_field( $payload['app_context']['version']     ?? '' ),
			],
		];
	}

	/**
	 * Validate an uploaded screenshot file entry from $_FILES.
	 *
	 * @param array $file Single file entry from $request->get_file_params().
	 * @return true|\WP_Error
	 */
	public function validate_screenshot( array $file ): true|\WP_Error {

		if ( isset( $file['error'] ) && $file['error'] !== UPLOAD_ERR_OK ) {
			return new \WP_Error(
				'upload_error',
				'Screenshot upload error (code ' . (int) $file['error'] . ').',
				[ 'status' => 400 ]
			);
		}

		if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			return new \WP_Error( 'invalid_upload', 'Invalid screenshot upload.', [ 'status' => 400 ] );
		}

		if ( (int) $file['size'] > self::MAX_SCREENSHOT_BYTES ) {
			return new \WP_Error( 'file_too_large', 'Screenshot must be under 5 MB.', [ 'status' => 413 ] );
		}

		// Extension-based MIME check (cheap).
		$checked = wp_check_filetype( basename( $file['name'] ?? 'screenshot.png' ) );
		if ( ! in_array( $checked['type'], self::ALLOWED_MIME_TYPES, true ) ) {
			return new \WP_Error( 'invalid_mime', 'Screenshot must be PNG or JPEG.', [ 'status' => 415 ] );
		}

		// Content-based MIME check via magic bytes (reliable).
		if ( function_exists( 'finfo_open' ) ) {
			$finfo       = new \finfo( FILEINFO_MIME_TYPE );
			$actual_mime = $finfo->file( $file['tmp_name'] );
			if ( ! in_array( $actual_mime, self::ALLOWED_MIME_TYPES, true ) ) {
				return new \WP_Error(
					'invalid_content',
					'Screenshot file content is not a recognised image.',
					[ 'status' => 415 ]
				);
			}
		}

		return true;
	}
}
