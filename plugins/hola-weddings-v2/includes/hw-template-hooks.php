<?php

/**
 * Add modals to the footer
 */
add_action('wp_footer', function(){

    hw_get_template('template-parts/modals/login');
    hw_signature_modal();
    ?>
    <script type="text/javascript">
		feather.replace({ 'stroke-width': 1.5, 'height': 20, 'width': 20 });
		
		jQuery(document).on('change', function(){
			feather.replace({ 'stroke-width': 1.5, 'height': 20, 'width': 20 });
		});
	</script>
    <?php
}, 20);

/**
 * print things in head
 *
 * @return void
 */
function hw_print_head(){

    if(hw_is_home_endpoint()){

        $hero_options = get_field('hw_homepage_hero');

        if(!$hero_options['disabled']): ?>
            <style>
                #hero{
                    <?php if( !empty( $hero_options['style_height_mobile'] ) ): ?>
                        min-height: <?= $hero_options['style_height_mobile'] ?: '' ?>;
                    <?php endif; ?>
                    
                    <?php if( !empty( $hero_options['style_height_desktop'] ) ): ?>
                        @media (min-width: 992px) {
                            min-height: <?= $hero_options['style_height_desktop'] ?: '550px' ?>;
                        }
                    <?php endif; ?>
                }
            </style>
            <?php
        endif;
    }
}
add_action('wp_head', 'hw_print_head', 10 );

/**
 * Undocumented function
 *
 * @return void
 */
function hw_add_body_classes($classes){

    if(hw_is_home_endpoint()){

        $hero_options = get_field('hw_homepage_hero');

        if(!$hero_options['disabled']){
            $classes[] = 'has-hero';
        }
    }

    return $classes;
}
add_filter('body_class', 'hw_add_body_classes');

/**
 * render the Google sign-in button in the login modal
 *
 * hw_google_login_button() decides whether there is anything to show, so there
 * is no condition to duplicate here.
 *
 * @return void
 */
function hw_render_login_modal_google_button(){

    hw_google_login_button( [ 'divider' => __( 'or', 'hola-weddings' ) ] );
}
add_action('hw_login_modal_after_form', 'hw_render_login_modal_google_button', 10 );

/**
 * render the Google button in the sign-up pane
 *
 * The same button serves both purposes — Google creates the account when it does
 * not recognise the address — so the sign-up pane gets one too. Bound to the
 * pane-aware hook and filtered to 'signup' only, because the sign-in pane is
 * already served by hw_login_modal_after_form above and would otherwise render
 * the button twice.
 *
 * @param string $pane 'signin' | 'signup' | 'reset'
 * @return void
 */
function hw_render_signup_google_button( $pane ){

    if ( $pane !== 'signup' ) {
        return;
    }

    hw_google_login_button( [ 'divider' => __( 'or', 'hola-weddings' ) ] );
}
add_action('hw_auth_after_form', 'hw_render_signup_google_button', 10, 1 );

/**
 * render the invoice payment custom message modal
 *
 * @return void
 */
function hw_render_invoice_payment_message_modal(){

    if(hw_get_invoice_payment_btn_action() == 'request-payment-link'){
        
        hw_get_template('template-parts/modals/invoice-payment-message');
    }
}
add_action('hw_account_reservation_view_end', 'hw_render_invoice_payment_message_modal', 10 );
add_action('hw_account_reservations_view_end', 'hw_render_invoice_payment_message_modal', 10 );

/**
 * display names template in hero content
 *
 * @param array $options
 * @return void
 */
function hw_home_hero_content_names( $options ){

    if( $options['hide_names'] ){
        return;
    }

    $wedding = hw()->getWedding();

    $separator = $options['names_separator'] ?? '&';
    $brideName = $options['names_format'] == 'full-name' ? $wedding->getHerName() : $wedding->getBrideFirstName();
    $groomName = $options['names_format'] == 'full-name' ? $wedding->getHisName() : $wedding->getGroomFirstName();

    hw_get_template( 'template-parts/home/hero/content/names', [ 'separator' => $separator, 'brideName' => $brideName, 'groomName' => $groomName ] );
}
add_action( 'hw_home_hero_content', 'hw_home_hero_content_names', 10, 1);

/**
 * display dates template in hero content
 *
 * @param array $options
 * @return void
 */
function hw_home_hero_content_dates( $options ){

    $wedding = hw()->getWedding();
    $date = $wedding->getDate();

    if($options['hide_date'] || empty($date)){
        return;
    }

    hw_get_template( 'template-parts/home/hero/content/dates', [ 'date' => $date ] );
}
add_action( 'hw_home_hero_content', 'hw_home_hero_content_dates', 20, 1);

/**
 * display countdown template in hero content
 *
 * @param array $options
 * @return void
 */
function hw_home_hero_content_countdown( $options ){

    $wedding = hw()->getWedding();
    $date = $wedding->getDate();

    if(!$options['display_countdown'] || empty($date)){
        return;
    }

    hw_get_template( 'template-parts/home/hero/content/countdown', [ 'date' => $date ] );
}
add_action( 'hw_home_hero_content', 'hw_home_hero_content_countdown', 30, 1);