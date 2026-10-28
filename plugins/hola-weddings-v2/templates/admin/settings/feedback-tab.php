<?php
/**
 * Per-site Feedback settings tab.
 * Jira connection credentials are in Network Settings → Jira.
 * This tab only shows the recent error log for debugging.
 */

use HW\UI\Forms\Layouts\Heading;
?>

<div class="alert alert-info d-flex align-items-center gap-2 mb-4" role="alert" style="font-size:.9rem">
	<i class="fas fa-info-circle"></i>
	<span>
		<?php esc_html_e( 'Jira connection credentials are configured in ', 'hola-wedding' ); ?>
		<a href="<?php echo esc_url( network_admin_url( 'admin.php?page=hw-network-settings' ) ); ?>">
			<?php esc_html_e( 'Network Settings → Jira', 'hola-wedding' ); ?>
		</a>.
		<?php esc_html_e( 'Only administrators can submit feedback reports.', 'hola-wedding' ); ?>
	</span>
</div>

<?php

$errors = get_option( 'hw_feedback_errors', [] );

if ( empty( $errors ) ) {
	echo '<p class="text-muted">' . esc_html__( 'No Jira errors logged on this site.', 'hola-wedding' ) . '</p>';
	return;
}

Heading::make()
	->heading( 'Recent Jira Errors' )
	->subHeading( 'Last 20 Jira API failures logged on this site.' )
	->render();
?>

<div class="table-responsive">
	<table class="table table-sm table-bordered">
		<thead class="table-light">
			<tr>
				<th><?php esc_html_e( 'Time', 'hola-wedding' ); ?></th>
				<th><?php esc_html_e( 'Error', 'hola-wedding' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $errors as $entry ) : ?>
				<tr>
					<td style="white-space:nowrap"><?php echo esc_html( $entry['time'] ?? '' ); ?></td>
					<td><?php echo esc_html( $entry['message'] ?? '' ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
