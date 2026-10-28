<?php

/**
 * determinate if user has a role assigned
 *
 * @param string $role
 * @param WP_User $user
 * @return bool
 */
function hw_user_has_role( $role,  $user = null ){

    // if id is passed
    if(is_integer($user)){

        $user = get_userdata($user);
    }

    // if user doesn't exist get current user
    if(!$user){
        
        $user = wp_get_current_user();
    }
    
    return in_array( $role, (array) $user->roles );
}

/**
 * add/remove roles
 *
 * @return void
 */
function hw_configure_roles(){

    $shared_perms = [
		'read_view'					=> true,
		'edit_theme_options'		=> true,
		'unfiltered_html'			=> true,
		'upload_files'				=> true,
		'read'						=> true
	];

    //if ( get_option( 'hw_roles_version' ) < 1) {
        
        // remove unwanted roles and custom roles, this helps to reset role capabiities when plugin is activated
        remove_role( 'contributor' );
        remove_role( 'author' );
        remove_role( 'quote-manager' );

        // NAVY Editor Role
        if ( is_null( get_role( 'quote-manager' ) ) ) {

            $role = add_role(
                'quote-manager',
                __( 'Quote Manager' ),
                $shared_perms
            );	
            add_capabilities_to_role($role, 'quote', 'quotes');

            $admin_role = get_role('administrator');
            add_capabilities_to_role($admin_role, 'quote', 'quotes');

            update_option( 'hw_roles_version', 1 );
        }
    //}
}
add_action('init', 'hw_configure_roles');