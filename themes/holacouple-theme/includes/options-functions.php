<?php

function hw_theme_get_main_menu_alignment(){
    
    global $hw_theme;

    return $hw_theme['main_menu_alignment'] ?? 'center';
}