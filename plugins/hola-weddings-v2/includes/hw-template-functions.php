<?php

/**
 * Locate template.
 *
 * Locate the called template.
 * Search Order:
 * 1. /themes/theme/templates/$template_name
 * 2. /themes/theme/$template_name
 * 3. /plugins/plugin/templates/$template_name.
 *
 * @since 1.0.0
 *
 * @param   string  $template_name          Template to load.
 * @param   string  $string $template_path  Path to templates.
 * @param   string  $default_path           Default path to template files.
 * @return  string                          Path to the template file.
 */
function hw_locate_template( $template_name, $template_path = '', $default_path = '' ) {

    global $wp_stylesheet_path, $wp_template_path;

    // Set variable to search in the templates folder of theme.
    if ( ! $template_path ) :
      $template_path = 'hw-plugin/';
    endif;
  
    // Set default plugin templates path.
    if ( ! $default_path ) :
      $default_path = HW_PLUGIN_PATH . '/'; // Path to the template folder
    endif;

    $template_names = [
        $template_path . $template_name ,
        $template_name
    ];

    if ( ! isset( $wp_stylesheet_path ) || ! isset( $wp_template_path ) ) {
		wp_set_template_globals();
	}
  
    $is_child_theme = is_child_theme();

	$template = '';
	foreach ( (array) $template_names as $template_name ) {
		if ( ! $template_name ) {
			continue;
		}
		if ( $is_child_theme && file_exists( $wp_stylesheet_path . '/' . $template_name ) ) {
			$template = $wp_stylesheet_path . '/' . $template_name;
			break;
		} elseif ( !$is_child_theme && file_exists( $wp_template_path . '/' . $template_name ) ) {
			$template = $wp_template_path . '/' . $template_name;
			break;
		} elseif ( file_exists( ABSPATH . WPINC . '/theme-compat/' . $template_name ) ) {
			$template = ABSPATH . WPINC . '/theme-compat/' . $template_name;
			break;
		}
	}
    
    // Get plugins template file.
    if ( ! $template ) :
      $template = $default_path . $template_name;
    endif;
    
    return apply_filters( 'hw_locate_template', $template, $template_name, $template_path, $default_path );

}

/**
 * Get template.
 *
 * Search for the template and include the file.
 *
 * @since 1.0.0
 *
 * @see PLUGIN_locate_template()
 *
 * @param string  $template_name          Template to load.
 * @param array   $args                   Args passed for the template file.
 * @param string  $template_path          Path to templates.
 * @param string  $default_path           Default path to template files.
 */
function hw_get_template( $template_name, $args = array(), $tempate_path = '', $default_path = '' ) {

    if ( is_array( $args ) && isset( $args ) ) :
      extract( $args );
    endif;
    
    $template_name = $template_name . '.php';
  
    $template_file = hw_locate_template( $template_name, $tempate_path, $default_path );
    
    if ( ! file_exists( $template_file ) ) :
      _doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> does not exist.', $template_file ), '1.0.0' );
      return;
    endif;
  
    include $template_file;

}

/**
 * embed the signature modal
 *
 * @return void
 */
function hw_signature_modal(){

  hw_get_template('template-parts/modals/signature');
}

/**
 * render the "Continue with Google" button
 *
 * Outputs nothing unless the network has OAuth credentials saved and this site
 * has the button switched on, so callers do not need to check first.
 *
 * The return URL is resolved here rather than by the caller — every call site
 * wants the same behaviour, and it is easy to forget.
 *
 * @param array $args {
 *     @type string $class   Classes for the anchor. Defaults to a full width Bootstrap button.
 *     @type string $label   Button text. Defaults to the label configured for the site.
 *     @type string $divider Text for a separator rendered above the button, e.g. 'or'.
 *                           Omit for no separator.
 * }
 * @return void
 */
function hw_google_login_button( $args = [] ){

  if( ! hw_is_google_login_configured() ){

    return;
  }

  $args = wp_parse_args( $args, [
    // .hw-google-btn owns width, border, radius and padding; adding .btn on top
    // would drag Bootstrap's own metrics in and fight it.
    'class'   => '',
    'label'   => hw_get_google_login_button_label(),
    'divider' => '',
  ] );

  $args['url'] = hw_get_google_login_url( hw_get_google_login_return_url() );

  hw_get_template( 'template-parts/partials/auth/google-login-button', $args );
}

/**
 * return if the booking form has the container
 *
 * @return bool
 */
function hw_booking_form_page_has_container(){

  return get_field( 'hw_booking_form_page_add_container' );
}