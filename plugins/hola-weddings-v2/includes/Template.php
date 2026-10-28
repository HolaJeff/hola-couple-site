<?php

namespace HW;

class Template{
    
    use Traits\Singleton;
    
    protected $templates;
    
    public function __construct(){
        
        $this->templates = [
            'endpoints/home.php' => 'Home',
            'endpoints/travel-info.php' => 'Travel Info',
            'endpoints/book.php' => 'Book',
        ];
        
		add_filter( 'theme_page_templates', array( $this, 'add_new_template' ) );
        
        add_filter( 'template_include', [ $this, 'view_project_template'] );
        add_filter( 'wp_insert_post_data',  array( $this, 'register_project_templates' ) );
    }
    
    public function add_new_template( $posts_templates ) {
        
		$posts_templates = array_merge( $posts_templates, $this->templates );
		return $posts_templates;
	}
    
    public function register_project_templates($atts){
        
        // Create the key used for the themes cache
        $cache_key = 'page_templates-' . md5( get_theme_root() . '/' . get_stylesheet() );
    
        // Retrieve the cache list. 
        // If it doesn't exist, or it's empty prepare an array
        $templates = wp_get_theme()->get_page_templates();
        if ( empty( $templates ) ) {
            $templates = array();
        } 
    
        // New cache, therefore remove the old one
        wp_cache_delete( $cache_key , 'themes');
    
        // Now add our template to the list of templates by merging our templates
        // with the existing templates array from the cache.
        $templates = array_merge( $templates, $this->templates );
    
        // Add the modified cache to allow WordPress to pick it up for listing
        // available templates
        wp_cache_add( $cache_key, $templates, 'themes', 1800 );
    
        return $atts;
    }
    
    public function view_project_template( $template ){
        
        // Get global post
        global $post;
    
        // Return template if post is empty
        if ( ! $post ) {
            return $template;
        }

        $page_template = get_post_meta( $post->ID, '_wp_page_template', true );
    
        // Return default template if we don't have a custom one defined
        if ( !isset( $this->templates[$page_template] ) ) {
            
            return $template;
        } 
    
        // check if the file exists in theme folder
        $theme_file = get_stylesheet_directory() . '/templates/' . $page_template ;

        if( file_exists( $theme_file ) ){
            return $theme_file;
        }

        // get plugin file
        $file = HW_PLUGIN_PATH . '/' . $page_template;
    
        // Just to be safe, we check if the file exist first
        if ( file_exists( $file ) ) {
            return $file;
        }
    
        // Return template
        return $template;
    }
}