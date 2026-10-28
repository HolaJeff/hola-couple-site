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
        'page_priority'        => 3, // Position in the admin menu
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
                'id'       => 'primary_color_contrast',
                'type'     => 'color',
                'title'    => 'Primary Color Contrast',
            ],
            [
                'id'       => 'primary_color_hover',
                'type'     => 'color',
                'title'    => 'Primary Color Hover',
            ],
            [
                'id'       => 'primary_color_hover_contrast',
                'type'     => 'color',
                'title'    => 'Primary Color Hover Contrast',
            ],
            [
                'type'     => 'divide',
            ],
            [
                'id'       => 'secondary_color',
                'type'     => 'color',
                'title'    => 'Secondary Color',
            ],
            [
                'id'       => 'secondary_color_contrast',
                'type'     => 'color',
                'title'    => 'Secondary Color Contrast',
            ],
            [
                'id'       => 'secondary_color_hover',
                'type'     => 'color',
                'title'    => 'Secondary Color Hover',
            ],
            [
                'id'       => 'secondary_color_hover_contrast',
                'type'     => 'color',
                'title'    => 'Secondary Color Hover Contrast',
            ],
            [
                'type'     => 'divide',
            ],
            [
                'id'       => 'accent_color',
                'type'     => 'color',
                'title'    => 'Accent Color',
            ],
            [
                'id'       => 'accent_color_contrast',
                'type'     => 'color',
                'title'    => 'Accent Color Contrast',
            ],
            [
                'id'       => 'accent_color_hover',
                'type'     => 'color',
                'title'    => 'Accent Color Hover',
            ],
            [
                'id'       => 'accent_color_hover_contrast',
                'type'     => 'color',
                'title'    => 'Accent Color Hover Contrast',
            ],
            [
                'type'     => 'divide',
            ],
            [
                'id'       => 'text_color',
                'type'     => 'color',
                'title'    => 'Text Color',
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

    /** Header */
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
                'id'       => 'show_site_logo_decoration',
                'type'     => 'switch',
                'title'    => 'Show Logo Decoration',
                'default' => 1,
                'required' => [ 'show_site_logo', '=', 1 ],
            ],
            [
                'id'       => 'site_logo_decoration_image',
                'type'     => 'media',
                'url'      => false,
                'title'    => 'Logo Decoration Image',
                'required' => [ 'show_site_logo_decoration', '=', 1 ],
            ],
            [
                'id'       => 'site_logo_decoration_height_mobile',
                'type'     => 'slider',
                'title'    => 'Logo Decoration Height (Mobile)',
                'desc'     => 'Height in pixels of the logo decoration image on mobile devices.',
                'default'  => 40,
                'min'      => 10,
                'max'      => 200,
                'step'     => 1,
                'display_value' => 'text',
                'required' => [ 'site_logo_decoration_image', 'not_empty', '' ],
            ],
            [
                'id'       => 'site_logo_decoration_height_desktop',
                'type'     => 'slider',
                'title'    => 'Logo Decoration Height (Desktop)',
                'desc'     => 'Height in pixels of the logo decoration image from tablet/desktop up.',
                'default'  => 50,
                'min'      => 10,
                'max'      => 200,
                'step'     => 1,
                'display_value' => 'text',
                'required' => [ 'site_logo_decoration_image', 'not_empty', '' ],
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
            [
                'type'     => 'divide',
            ],
            [
                'id'       => 'header_bg',
                'type'     => 'color',
                'title'    => 'Header Background Color',
            ],
            [
                'id'       => 'header_text',
                'type'     => 'color',
                'title'    => 'Header Text Color',
            ],
            [
                'id'       => 'main_nav_text',
                'type'     => 'color',
                'title'    => 'Main Nav Text Color',
            ],
            [
                'id'       => 'main_nav_text_active',
                'type'     => 'color',
                'title'    => 'Main Nav Active Text Color',
            ],
        ]
    ]);

    /** Footer */
    Redux::set_section( $option_name, [
        'title'  => 'Footer',
        'id'     => 'footer',
        'icon' => 'el-icon-indent-left',
        'fields' => [
            [
                'id'       => 'footer_display_logo',
                'type'     => 'switch',
                'title'    => 'Dislpay Logo',
                'default' => 1
            ],
            [
                'type'     => 'divide',
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
                'color' => false
            ],
            [
                'id'=> 'header_typography',
                'type' => 'typography',
                'title' => 'Header Typography',
                'color' => false
            ],
            [
                'id'=> 'footer_typography',
                'type' => 'typography',
                'title' => 'Footer Typography',
                'output' => ['#footer .upper-footer p', '#footer .upper-footer a'],
                'color' => false
            ],
            [
                'id'=> 'logo_typography',
                'type' => 'typography',
                'title' => 'Logo Typography',
                'default' => 'Corinthia',
                'color' => false
            ],
            [
                'id'=> 'header_tags_typography',
                'type' => 'typography',
                'title' => 'Headers Typography',
                'default' => 'Playfair Display',
                'color' => false
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
            <?= isset( $hw_theme['footer_typography'] ) ? hw_redux_helper_print_typography_variables('hw-footer', $hw_theme['footer_typography']) : '' ?>
            <?= isset( $hw_theme['logo_typography'] ) ? hw_redux_helper_print_typography_variables('hw-logo', $hw_theme['logo_typography']) : '' ?>
            <?= isset( $hw_theme['header_tags_typography'] ) ? hw_redux_helper_print_typography_variables('hw-header-tags', $hw_theme['header_tags_typography']) : '' ?>

            <?php // primary color ?>
            <?= isset( $hw_theme['primary_color'] ) ? hw_redux_helper_print_variable('hw-primary-color', $hw_theme['primary_color']) : '' ?>
            <?= isset( $hw_theme['primary_color_contrast'] ) ? hw_redux_helper_print_variable('hw-primary-contrast-color', $hw_theme['primary_color_contrast']) : '' ?>
            <?= isset( $hw_theme['primary_color_hover'] ) ? hw_redux_helper_print_variable('hw-primary-darker-color', $hw_theme['primary_color_hover']) : '' ?>
            <?= isset( $hw_theme['primary_color_hover_contrast'] ) ? hw_redux_helper_print_variable('hw-primary-darker-contrast-color', $hw_theme['primary_color_hover_contrast']) : '' ?>
            
            <?php // secondary color ?>
            <?= isset( $hw_theme['secondary_color'] ) ? hw_redux_helper_print_variable('hw-secondary-color', $hw_theme['secondary_color']) : '' ?>
            <?= isset( $hw_theme['secondary_color_contrast'] ) ? hw_redux_helper_print_variable('hw-secondary-contrast', $hw_theme['secondary_color_contrast']) : '' ?>
            <?= isset( $hw_theme['secondary_color_hover'] ) ? hw_redux_helper_print_variable('hw-secondary-hover', $hw_theme['secondary_color_hover']) : '' ?>
            <?= isset( $hw_theme['secondary_color_hover_contrast'] ) ? hw_redux_helper_print_variable('hw-secondary-hover-contrast', $hw_theme['secondary_color_hover_contrast']) : '' ?>
            
            <?php // Accent color ?>
            <?= isset( $hw_theme['accent_color'] ) ? hw_redux_helper_print_variable('hw-accent-color', $hw_theme['accent_color']) : '' ?>
            <?= isset( $hw_theme['accent_color_contrast'] ) ? hw_redux_helper_print_variable('hw-accent-contrast', $hw_theme['accent_color_contrast']) : '' ?>
            <?= isset( $hw_theme['accent_color_hover'] ) ? hw_redux_helper_print_variable('hw-accent-hover', $hw_theme['accent_color_hover']) : '' ?>
            <?= isset( $hw_theme['accent_color_hover_contrast'] ) ? hw_redux_helper_print_variable('hw-accent-hover-contrast', $hw_theme['accent_color_hover_contrast']) : '' ?>

            <?php // Other colors ?>
            <?= isset( $hw_theme['text_color'] ) ? hw_redux_helper_print_variable('hw-text-color', $hw_theme['text_color']) : '' ?>
            <?= isset( $hw_theme['bg_color'] ) ? hw_redux_helper_print_color_rgba_variable('hw-bg-color', $hw_theme['bg_color']) : '' ?>
            <?= isset( $hw_theme['bg_color_contrast'] ) ? hw_redux_helper_print_variable('hw-bg-color-contrast', $hw_theme['bg_color_contrast']) : '' ?>
            <?= isset( $hw_theme['header_bg'] ) ? hw_redux_helper_print_variable('hw-header-bg-color', $hw_theme['header_bg']) : '' ?>
            <?= isset( $hw_theme['header_text'] ) ? hw_redux_helper_print_variable('hw-header-text-color', $hw_theme['header_text']) : '' ?>
            <?= isset( $hw_theme['main_nav_text'] ) ? hw_redux_helper_print_variable('hw-main-nav-text-color', $hw_theme['main_nav_text']) : '' ?>
            <?= isset( $hw_theme['main_nav_text_active'] ) ? hw_redux_helper_print_variable('hw-main-nav-text-active-color', $hw_theme['main_nav_text_active']) : '' ?>
            <?= isset( $hw_theme['footer_bg'] ) ? hw_redux_helper_print_variable('hw-footer-bg-color', $hw_theme['footer_bg']) : '' ?>
            <?= isset( $hw_theme['footer_text'] ) ? hw_redux_helper_print_variable('hw-footer-text-color', $hw_theme['footer_text']) : '' ?>
            <?= isset( $hw_theme['footer_logo'] ) ? hw_redux_helper_print_variable('hw-footer-logo-color', $hw_theme['footer_logo']) : '' ?>

            <?= isset( $hw_theme['hero_overlay'] ) ? hw_redux_helper_print_color_rgba_variable('hw-hero-overlay', $hw_theme['hero_overlay']) : '' ?>
            <?= isset( $hw_theme['hero_text'] ) ? hw_redux_helper_print_variable('hw-hero-text-color', $hw_theme['hero_text']) : '' ?>
        }

        <?php // logo decoration image height overrides ?>
        <?php if( hw_theme_header_display_logo() && hw_theme_header_display_logo_decoration() && hw_theme_header_has_custom_logo_decoration() ) : ?>
            #header .logo .logo-wrapper > a .logo-decoration{
                height: <?= hw_theme_header_get_logo_decoration_height('mobile') ?>px;
            }

            @media (min-width: 992px){
                #header .logo .logo-wrapper > a .logo-decoration{
                    height: <?= hw_theme_header_get_logo_decoration_height('desktop') ?>px;
                }
            }
        <?php endif; ?>
    </style>
    <?php
}
add_action('wp_head', 'hw_add_redux_css_font_variables', 11);