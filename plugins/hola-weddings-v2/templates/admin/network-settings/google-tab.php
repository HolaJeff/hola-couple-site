<?php
/**
 * Network-level Google Sign-In settings tab.
 *
 * Section 1 — OAuth client credentials (client_id via data[], client_secret masked)
 * Section 2 — Where the button is switched on, per site
 */

use HW\UI\Forms\Controls\Password;
use HW\UI\Forms\Controls\Text;
use HW\UI\Forms\Layouts\Heading;

$callback_url  = hw_get_google_callback_url();
$js_origin     = is_multisite()
	? untrailingslashit( get_home_url( get_main_site_id(), '/' ) )
	: untrailingslashit( home_url( '/' ) );

$client_id_saved     = (string) get_network_option( null, 'hw_google_client_id', '' );
$client_secret_saved = (string) get_network_option( null, 'hw_google_client_secret', '' );
$client_id_const     = defined( 'HW_GOOGLE_CLIENT_ID' ) && HW_GOOGLE_CLIENT_ID;
$client_secret_const = defined( 'HW_GOOGLE_CLIENT_SECRET' ) && HW_GOOGLE_CLIENT_SECRET;
$redirect_const      = defined( 'HW_GOOGLE_OAUTH_REDIRECT_URI' ) && HW_GOOGLE_OAUTH_REDIRECT_URI;

$is_configured = hw_get_google_client_id() !== '' && hw_get_google_client_secret() !== '';

// Sites across the network with the button switched on.
$enabled_sites = [];

if ( is_multisite() ) {

	foreach ( get_sites( [ 'number' => 200, 'fields' => 'ids' ] ) as $site_id ) {

		if ( get_blog_option( $site_id, 'hw_enable_google_login', false ) ) {

			$enabled_sites[] = [
				'name' => get_blog_details( $site_id )->blogname,
				'url'  => get_admin_url( $site_id, 'admin.php?page=holaweddings' ),
			];
		}
	}
}
?>

<?php if ( $is_configured ) : ?>
	<div class="alert alert-success mb-4">
		<i class="fas fa-check-circle"></i>
		<?php esc_html_e( 'Credentials are saved. Each site can now switch the Google button on under Wedding Settings → Login/Sign Up.', 'hola-weddings' ); ?>
	</div>
<?php else : ?>
	<div class="alert alert-warning mb-4">
		<i class="fas fa-exclamation-triangle"></i>
		<?php esc_html_e( 'Google sign-in is inactive until both a Client ID and a Client Secret are saved here.', 'hola-weddings' ); ?>
	</div>
<?php endif; ?>

<?php
// ── Section 1: OAuth client credentials ──────────────────────────────────────
Heading::make()
	->heading( __( 'OAuth Client Credentials', 'hola-weddings' ) )
	->subHeading( __( 'Create a Web application OAuth client at console.cloud.google.com/apis/credentials and register the two URLs below. One client covers the whole network.', 'hola-weddings' ) )
	->tag( 'h5' )
	->render();
?>

<div class="form-group mb-3">
	<label class="form-label"><?php esc_html_e( 'Authorized redirect URI', 'hola-weddings' ); ?></label>
	<div class="input-group input-group-sm" style="max-width:620px">
		<input
			type="text"
			class="form-control font-monospace"
			value="<?php echo esc_attr( $callback_url ); ?>"
			readonly
			onclick="this.select()"
		>
		<button
			type="button"
			class="btn btn-outline-secondary"
			onclick="navigator.clipboard.writeText('<?php echo esc_js( $callback_url ); ?>')"
			title="<?php esc_attr_e( 'Copy to clipboard', 'hola-weddings' ); ?>"
		>
			<i class="fas fa-copy"></i>
		</button>
	</div>
	<small class="form-text text-muted">
		<?php esc_html_e( 'Add this exact URL under "Authorized redirect URIs". It never changes and serves every site on the network.', 'hola-weddings' ); ?>
		<?php if ( $redirect_const ) : ?>
			<br><span class="text-primary"><i class="fas fa-lock"></i> <?php esc_html_e( 'Overridden by the HW_GOOGLE_OAUTH_REDIRECT_URI constant.', 'hola-weddings' ); ?></span>
		<?php endif; ?>
	</small>
</div>

<div class="form-group mb-3">
	<label class="form-label"><?php esc_html_e( 'Authorized JavaScript origin', 'hola-weddings' ); ?></label>
	<div class="input-group input-group-sm" style="max-width:620px">
		<input
			type="text"
			class="form-control font-monospace"
			value="<?php echo esc_attr( $js_origin ); ?>"
			readonly
			onclick="this.select()"
		>
		<button
			type="button"
			class="btn btn-outline-secondary"
			onclick="navigator.clipboard.writeText('<?php echo esc_js( $js_origin ); ?>')"
			title="<?php esc_attr_e( 'Copy to clipboard', 'hola-weddings' ); ?>"
		>
			<i class="fas fa-copy"></i>
		</button>
	</div>
	<small class="form-text text-muted">
		<?php esc_html_e( 'Optional for the redirect flow used here — add it only if you later render Google\'s own button in the browser.', 'hola-weddings' ); ?>
	</small>
</div>

<?php
if ( $client_id_const ) : ?>
	<div class="form-group mb-3">
		<label class="form-label"><?php esc_html_e( 'Client ID', 'hola-weddings' ); ?></label>
		<p class="text-primary mb-0 small"><i class="fas fa-lock"></i> <?php esc_html_e( 'Defined via the HW_GOOGLE_CLIENT_ID constant.', 'hola-weddings' ); ?></p>
	</div>
<?php else :
	Text::make( 'data[hw_google_client_id]' )
		->label( __( 'Client ID', 'hola-weddings' ) )
		->value( $client_id_saved )
		->description( __( 'Ends in .apps.googleusercontent.com', 'hola-weddings' ) )
		->render();
endif;

if ( $client_secret_const ) : ?>
	<div class="form-group mb-3">
		<label class="form-label"><?php esc_html_e( 'Client Secret', 'hola-weddings' ); ?></label>
		<p class="text-primary mb-0 small"><i class="fas fa-lock"></i> <?php esc_html_e( 'Defined via the HW_GOOGLE_CLIENT_SECRET constant.', 'hola-weddings' ); ?></p>
	</div>
<?php else :
	Password::make( 'hw_google_client_secret_input' )
		->label( __( 'Client Secret', 'hola-weddings' ) )
		->saved( $client_secret_saved !== '' )
		->render();
endif;

// ── Section 2: where it is switched on ───────────────────────────────────────
Heading::make()
	->heading( __( 'Sites Using Google Sign-In', 'hola-weddings' ) )
	->tag( 'h5' )
	->render();

if ( ! is_multisite() ) : ?>
	<p class="text-muted small">
		<?php esc_html_e( 'Switch the button on under Wedding Settings → Login/Sign Up.', 'hola-weddings' ); ?>
	</p>
<?php elseif ( empty( $enabled_sites ) ) : ?>
	<p class="text-muted small">
		<?php esc_html_e( 'No site has the Google button switched on yet. Enable it per site under Wedding Settings → Login/Sign Up.', 'hola-weddings' ); ?>
	</p>
<?php else : ?>
	<ul class="list-unstyled small mb-0">
		<?php foreach ( $enabled_sites as $site ) : ?>
			<li class="mb-1">
				<i class="fas fa-check-circle" style="color:#2e7d32"></i>
				<a href="<?php echo esc_url( $site['url'] ); ?>"><?php echo esc_html( $site['name'] ); ?></a>
			</li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>
