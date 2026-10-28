<?php
/**
 * Site-level Login / Sign Up settings.
 *
 * The OAuth credentials are network-level (Network Settings → Google); this tab
 * only decides whether this wedding site offers the button and how it behaves.
 */

use HW\UI\Forms\Controls\Checkbox;
use HW\UI\Forms\Controls\Select;
use HW\UI\Forms\Controls\Text;
use HW\UI\Forms\Layouts\Heading;

$has_credentials = hw_get_google_client_id() !== '' && hw_get_google_client_secret() !== '';
$network_url     = network_admin_url( 'admin.php?page=hw-network-settings&tab=google' );

Heading::make()
	->heading( __( 'Email &amp; Password', 'hola-weddings' ) )
	->subHeading( __( 'Let couples create an account with an email address and a password.', 'hola-weddings' ) )
	->render();

Checkbox::make( 'hw_enable_signup' )
	->id( 'enable-signup' )
	->label( __( 'Enable Sign Up', 'hola-weddings' ) )
	->description( __( 'Shows the Sign Up form in the login modal. New accounts must confirm their email address before their reservations appear.', 'hola-weddings' ) )
	->setInputLabel( __( 'Yes', 'hola-weddings' ) )
	->value( hw_is_signup_enabled() )
	->styled()
	->render();

Heading::make()
	->heading( __( 'Google', 'hola-weddings' ) )
	->subHeading( __( 'Let couples create an account and sign in with their Google account.', 'hola-weddings' ) )
	->render();

if ( ! $has_credentials ) : ?>
	<div class="alert alert-warning">
		<i class="fas fa-exclamation-triangle"></i>
		<?php esc_html_e( 'No Google OAuth credentials are configured for this network yet, so the button stays hidden even when switched on below.', 'hola-weddings' ); ?>
		<?php if ( current_user_can( 'manage_network_options' ) ) : ?>
			<a href="<?php echo esc_url( $network_url ); ?>"><?php esc_html_e( 'Add them in Network Settings → Google', 'hola-weddings' ); ?></a>.
		<?php else : ?>
			<?php esc_html_e( 'Ask a network administrator to add them.', 'hola-weddings' ); ?>
		<?php endif; ?>
	</div>
<?php endif;

Checkbox::make( 'hw_enable_google_login' )
	->id( 'enable-google-login' )
	->label( __( 'Enable Google Button', 'hola-weddings' ) )
	->description( __( 'Show "Continue with Google" on the login and sign-up forms.', 'hola-weddings' ) )
	->setInputLabel( __( 'Yes', 'hola-weddings' ) )
	->value( hw_is_google_login_enabled() )
	->styled()
	->attributes( [
		'x-model' => 'enableGoogleLogin',
	] )
	->render();

Text::make( 'hw_google_login_button_label' )
	->label( __( 'Button Label', 'hola-weddings' ) )
	->description( __( 'Leave blank to use "Continue with Google".', 'hola-weddings' ) )
	->value( hw_get_option( 'hw_google_login_button_label', '' ) )
	->show( 'enableGoogleLogin' )
	->render();

Checkbox::make( 'hw_google_allow_signup' )
	->id( 'google-allow-signup' )
	->label( __( 'Allow Sign Up with Google', 'hola-weddings' ) )
	->description( __( 'When off, only people who already have an account can use the button — nobody new is created.', 'hola-weddings' ) )
	->setInputLabel( __( 'Yes', 'hola-weddings' ) )
	->value( hw_google_signup_allowed() )
	->styled()
	->show( 'enableGoogleLogin' )
	->render();

Select::make( 'hw_google_login_redirect_to' )
	->label( __( 'After Sign In', 'hola-weddings' ) )
	->value( hw_get_google_login_redirect_to() )
	->options( [
		'same-page' => __( 'Return to the page they started on', 'hola-weddings' ),
		'account'   => __( 'Go to their account dashboard', 'hola-weddings' ),
	] )
	->description( __( 'A redirect_to passed by the frontend always wins over this.', 'hola-weddings' ) )
	->show( 'enableGoogleLogin' )
	->render();
?>

<div class="mt-4" x-show="enableGoogleLogin">
	<p class="text-muted small mb-1">
		<strong><?php esc_html_e( 'How an account is matched', 'hola-weddings' ); ?></strong>
	</p>
	<ul class="text-muted small">
		<li><?php esc_html_e( 'A Google account already linked to a user signs straight in.', 'hola-weddings' ); ?></li>
		<li><?php esc_html_e( 'Otherwise, if the verified Google email matches an existing account, the two are linked on the spot.', 'hola-weddings' ); ?></li>
		<li><?php esc_html_e( 'Otherwise a new account is created, provided sign-up is allowed above.', 'hola-weddings' ); ?></li>
	</ul>
</div>
