<?php

namespace HWTheme;

class App{
    
    use \HWTheme\Traits\Singleton;
    
    public function init(){

        add_theme_support( 'title-tag' );
        
        Menus::getInstance();
        Scripts::getInstance();
        Template::getInstance();
        Widgets::getInstance();
        
        // Disables the block editor from managing widgets in the Gutenberg plugin.
        add_filter( 'gutenberg_use_widgets_block_editor', '__return_false' );
        // Disables the block editor from managing widgets.
        add_filter( 'use_widgets_block_editor', '__return_false' );
    }
}