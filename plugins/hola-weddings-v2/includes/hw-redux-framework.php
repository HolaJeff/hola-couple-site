<?php

/**
 * register redux fields
 *
 * @return void
 */
function hw_add_redux(){

    // check if redux was loaded
    if(!class_exists('Redux')){

        return;
    }

    $option_name = 'hw_plugin';

    $args = [
        'opt_name'             => $option_name, // Unique ID for the options
        'display_name'         => 'Hola Weddings Global Options',
        'display_version'      => '1.0',
        'page_title'           => 'Hola Weddings Plugin Options',
        'customizer'           => true, // Enable options in the Customizer
        'menu_type'            => 'menu', // Can be 'menu' or 'submenu'
        'menu_slug'            => 'hw-plugin-options', // URL slug for the options page
        'page_priority'        => 2, // Position in the admin menu
        'menu_icon'            => HW_ASSETS_URL . 'img/hw-icon.png', // Menu icon
        'admin_bar'            => false, // Show in admin bar
        'allow_sub_menu'       => false, // Enable submenus
        'dev_mode'             => false, // Disable debug mode
    ];
    Redux::set_args( $option_name, $args );

    // this section is added in order to display the fake options page
    Redux::set_section( $option_name, [
        'title'  => 'Fake Title',
        'id'     => 'fake-section',
        'icon' => 'el-icon-indent-left',
        'fields' => [
        ]
    ]);

    // register the homepage metabox
    Redux_Metaboxes::set_box(
        $option_name,
        array(
            'id' => 'hw_home_options',
            'post_types' => [ 'page', 'post' ],
            'page_template' => [ 'endpoints/home.php' ],
            'position'   => 'normal',
            'priority'   => 'default',
            'sections'   => array(
                [
                    'id' => 'sections',
                    'title' => 'Manage Sections',
                    'icon_class' => 'icon-large',
                    'icon'       => 'el-icon-th-list',
                    'fields'     => array(
                        array(
                            'title'   => esc_html__( 'Homepage Sections', 'hola-weddings' ),
                            'id'      => 'hw-homepage-sections',
                            'type'    => 'sorter',
                            'options' => [
                                'enabled' => [
                                    'story' => __('Couple Details', 'holaweddings'),
                                    'overview' => __('Wedding Overview', 'holaweddings'),
                                    'gallery' => __('Gallery', 'holaweddings')
                                ],
                                'disabled' => [
                                    'post-content' => __('Post Content', 'hola-weddings')
                                ]
                            ],
                        ),
                    ),
                ],
                [
                    'id' => 'hero_section',
                    'title' => 'Hero',
                    'icon_class' => 'icon-large',
                    'icon'       => 'el-icon-video',
                    'fields' => [
                        [
                            'id'       => 'hw-homepage-hero-disable',
                            'type'     => 'switch', 
                            'title'    => esc_html__('Disable', 'hola-weddings'),
                            'default'  => false,
                        ],
                        [
                            'id' => 'hw-homepage-hero-subtitle',
                            'type' => 'text',
                            'title' => __( 'Subtitle' , 'hola-weddings' ),
                            'default' => __( 'Save the Date', 'hola-weddings' )
                        ]
                    ]
                ],
                [
                    'id' => 'couple_details_section',
                    'title' => 'Couple Details',
                    'icon_class' => 'icon-large',
                    'icon'       => 'el-icon-adult',
                    'fields' => [
                        [
                            'id'       => 'hw-homepage-hero-hide-pictures',
                            'type'     => 'switch', 
                            'title'    => esc_html__('Hide Pictures', 'hola-weddings'),
                            'default'  => false,
                        ]
                    ]
                ],
                [
                    'id' => 'gallery_section',
                    'title' => 'Gallery',
                    'icon_class' => 'icon-large',
                    'icon'       => 'el-icon-picture',
                    'fields' => [
                        
                    ]
                ]
            ),
        )
    );
}
add_action('init', 'hw_add_redux');

/**
 * removes the fake options page from the menu
 *
 * @return void
 */
function hw_hide_redux_plugin_fake_menu_page() {

    remove_menu_page('hw_plugin_options');
}
add_action( 'admin_init', 'hw_hide_redux_plugin_fake_menu_page' );