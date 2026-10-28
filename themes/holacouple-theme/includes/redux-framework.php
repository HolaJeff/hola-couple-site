<?php

if ( class_exists( 'Redux' ) ) {

    $option_name = 'hw_theme';
    $theme = wp_get_theme();

    $args = [
        'opt_name'             => $option_name, // Unique ID for the options
        'display_name'         => $theme->get( 'Name' ),
        'display_version'      => $theme->get( 'Version' ),
        'menu_title'           => 'Theme Options',
        'page_title'           => 'Hola Weddings Theme Options',
        'customizer'           => true, // Enable options in the Customizer
        'menu_type'            => 'menu', // Can be 'menu' or 'submenu'
        'menu_slug'            => 'hw-theme-options', // URL slug for the options page
        'page_priority'        => 2, // Position in the admin menu
        'menu_icon'            => HW_ASSETS_URL . 'img/hw-icon.png', // Menu icon
        'admin_bar'            => false, // Show in admin bar
        'allow_sub_menu'       => true, // Enable submenus
        'dev_mode'             => false, // Disable debug mode
    ];

    Redux::set_args( $option_name, apply_filters('hw_theme_redux_args', $args ) );

    // Add a section with fields
    /* Redux::set_section( $option_name, [
        'title'  => 'General Settings',
        'id'     => 'general',
        'desc'   => 'Basic theme settings',
        'fields' => [
            [
                'id'       => 'site_logo',
                'type'     => 'media',
                'title'    => 'Site Logo',
                'subtitle' => 'Upload the logo for your site.',
            ],
            [
                'id'       => 'primary_color',
                'type'     => 'color',
                'title'    => 'Primary Color',
                'subtitle' => 'Select the primary color of your theme.',
                'default'  => '#ff0000',
            ],
            [
                'id'       => 'footer_text',
                'type'     => 'text',
                'title'    => 'Footer Text',
                'subtitle' => 'Enter the footer copyright text.',
                'default'  => '© 2024 My Theme',
            ],
        ],
    ]); */

    Redux::set_section( $option_name, [
        'title'  => 'General',
        'id'     => 'general',
        'fields' => [
            [
                'id'       => 'header_logo_display_separator',
                'type'     => 'switch',
                'title'    => 'Display Names Separator',
                'default' => 1,
            ],
            [
                'id'       => 'header_logo_separator_char',
                'type'     => 'text',
                'title'    => 'Separator Character',
                'default'  => '&',
                'required' => [ 'header_logo_display_separator', '=', 1 ],
            ]
        ]
    ]);

    Redux::set_section( $option_name, [
        'title'  => 'Header',
        'id'     => 'header',
        'icon' => 'el-icon-indent-left',
        'fields' => [
            [
                'id'       => 'show_site_logo',
                'type'     => 'switch',
                'title'    => 'Show Site Logo',
                'default' => 1
            ],
            [
                'id'       => 'header_display_container',
                'type'     => 'switch',
                'title'    => 'Add Container',
                'default' => 1
            ],
            [
                'id' => 'main_menu_alignment',
                'type' => 'button_set',
                'title' => __( 'Main Menu Alignment' , 'redux_docs_generator' ),
                'options' => [
                    'start' => 'Left',
                    'center' => 'Center',
                    'end' => 'Right'
                ],
                'default' => 'center'
            ],
            [
                'id'       => 'hide_login',
                'type'     => 'switch',
                'title'    => 'Hide Login',
                'default' => false
            ],
        ]
    ]);

    // colors
    Redux::set_section( $option_name, [
        'title'  => 'Colors',
        'id'     => 'colors',
        'icon' => 'el-icon-brush',
        'fields' => [
            [
                'id'       => 'primary_color',
                'type'     => 'color',
                'title'    => 'Primary Color',
            ],
            [
                'id'       => 'secondary_color',
                'type'     => 'color',
                'title'    => 'Secondary Color',
            ],
            [
                'id'       => 'text_color',
                'type'     => 'color',
                'title'    => 'Text Color',
            ],
            [
                'id'       => 'accent_color',
                'type'     => 'color',
                'title'    => 'Accent Color',
            ],
            [
                'id'       => 'bg_color',
                'type'     => 'color_rgba',
                'title'    => 'Background Color',
            ],
            [
                'id'       => 'bg_color_contrast',
                'type'     => 'color',
                'title'    => 'Background Contrast Color',
            ],
            [
                'id'       => 'footer_bg',
                'type'     => 'color',
                'title'    => 'Footer Background Color',
            ],
            [
                'id'       => 'footer_text',
                'type'     => 'color',
                'title'    => 'Footer Text Color',
            ],
            [
                'id'       => 'footer_logo',
                'type'     => 'color',
                'title'    => 'Footer Logo Color',
            ],
            [
                'id'       => 'hero_overlay',
                'type'     => 'color_rgba',
                'title'    => 'Hero Overlay Color',
            ],
            [
                'id'       => 'hero_text',
                'type'     => 'color',
                'title'    => 'Hero Text Color',
            ]
        ]
    ]);

    // Typography
    Redux::set_section( $option_name, [
        'title'  => 'Typography',
        'id'     => 'typography',
        'icon' => 'el-icon-fontsize',
        'fields' => [
            [
                'id'=> 'body_typography',
                'type' => 'typography',
                'title' => 'Body Typography',
            ],
            [
                'id'=> 'header_typography',
                'type' => 'typography',
                'title' => 'Header Typography',
            ],
            [
                'id'=> 'logo_typography',
                'type' => 'typography',
                'title' => 'Logo Typography',
                'default' => 'Corinthia'
            ],
            [
                'id'=> 'header_tags_typography',
                'type' => 'typography',
                'title' => 'Headers Typography',
                'default' => 'Playfair Display'
            ],
        ],
    ]);
}

/**
 * print redux typography field to root variables
 *
 * @param string $prefix
 * @param array $data
 * @return void
 */
function hw_redux_helper_print_typography_variables( $prefix, $data ){
    
    if(!empty($data['font-family'])){
        echo "--{$prefix}-font-family: '{$data['font-family']}';";
    }

    if(!empty($data['font-size'])){
        echo "--{$prefix}-font-size: {$data['font-size']};";
    }

    if(!empty($data['font-weight'])){
        echo "--{$prefix}-font-weight: {$data['font-weight']};";
    }

    if(!empty($data['line-height'])){
        echo "--{$prefix}-line-height: {$data['line-height']};";
    }

    if(!empty($data['color'])){
        echo "--{$prefix}-color: {$data['color']};";
    }
}

/**
 * print redux color field to root variable
 *
 * @param string $name
 * @param array $data
 * @return void
 */
function hw_redux_helper_print_color_rgba_variable( $name, $data ){
    if(!empty($data['rgba'])){
        echo "--{$name}: {$data['rgba']};";
    }
}

/**
 * print redux color field to root variable
 *
 * @param string $name
 * @param array $data
 * @return void
 */
function hw_redux_helper_print_variable( $name, $data ){
    if(!empty($data)){
        echo "--{$name}: {$data};";
    }
}

/**
 * print CSS default variables from Redux
 *
 * @return void
 */
function hw_add_redux_css_font_variables(){

    global $hw_theme;

    ?>
    <style>
        :root{
            <?= isset( $hw_theme['body_typography'] ) ? hw_redux_helper_print_typography_variables('hw-body', $hw_theme['body_typography']) : ''?>
            <?= isset( $hw_theme['header_typography'] ) ? hw_redux_helper_print_typography_variables('hw-header', $hw_theme['header_typography']) : '' ?>
            <?= isset( $hw_theme['logo_typography'] ) ? hw_redux_helper_print_typography_variables('hw-logo', $hw_theme['logo_typography']) : '' ?>
            <?= isset( $hw_theme['header_tags_typography'] ) ? hw_redux_helper_print_typography_variables('hw-header-tags', $hw_theme['header_tags_typography']) : '' ?>

            <?= isset( $hw_theme['primary_color'] ) ? hw_redux_helper_print_variable('hw-primary-color', $hw_theme['primary_color']) : '' ?>
            <?= isset( $hw_theme['secondary_color'] ) ? hw_redux_helper_print_variable('hw-secondary-color', $hw_theme['secondary_color']) : '' ?>
            <?= isset( $hw_theme['text_color'] ) ? hw_redux_helper_print_variable('hw-text-color', $hw_theme['text_color']) : '' ?>
            <?= isset( $hw_theme['accent_color'] ) ? hw_redux_helper_print_variable('hw-accent-color', $hw_theme['accent_color']) : '' ?>
            <?= isset( $hw_theme['bg_color'] ) ? hw_redux_helper_print_color_rgba_variable('hw-bg-color', $hw_theme['bg_color']) : '' ?>
            <?= isset( $hw_theme['bg_color_contrast'] ) ? hw_redux_helper_print_variable('hw-bg-color-contrast', $hw_theme['bg_color_contrast']) : '' ?>
            <?= isset( $hw_theme['footer_bg'] ) ? hw_redux_helper_print_variable('hw-footer-bg-color', $hw_theme['footer_bg']) : '' ?>
            <?= isset( $hw_theme['footer_text'] ) ? hw_redux_helper_print_variable('hw-footer-text-color', $hw_theme['footer_text']) : '' ?>
            <?= isset( $hw_theme['footer_logo'] ) ? hw_redux_helper_print_variable('hw-footer-logo-color', $hw_theme['footer_logo']) : '' ?>

            <?= isset( $hw_theme['hero_overlay'] ) ? hw_redux_helper_print_color_rgba_variable('hw-hero-overlay', $hw_theme['hero_overlay']) : '' ?>
            <?= isset( $hw_theme['hero_text'] ) ? hw_redux_helper_print_variable('hw-hero-text-color', $hw_theme['hero_text']) : '' ?>
        }
    </style>
    <?php
}
add_action('wp_head', 'hw_add_redux_css_font_variables', 11);