<?php

namespace HW\CLI;

class UpdateHomeSections{

    /**
     * process all sites
     *
     * @return void
     */
    public function update(){

        $sites = get_sites([
            'number' => 9999
        ]);

        foreach($sites as $site){

            $blog_id = $site->blog_id;
            switch_to_blog($blog_id);

            \WP_CLI::line(sprintf('Processing site %s', $site->domain));

            self::process();

            restore_current_blog();
        }
    }

    /**
     * test migrating single site by id
     * 
     * ## OPTIONS
     *
     * <id>
     * : The id of the site
     *
     * @return void
     */
    public function update_site( $args ){

        if(!isset($args[0]) || empty($args[0])){
            \WP_CLI::error(__('Invalid site id'));
            return;
        }
        
        $site = get_site($args[0]);

        if(!$site instanceof \WP_site){
            \WP_CLI::error(__('Website doesn\'t exist'));
            return;
        }

        $blog_id = $site->blog_id;
        switch_to_blog($blog_id);

        \WP_CLI::line(sprintf('Processing site %s', $site->domain));

        // process the site
        self::process( $site );

        restore_current_blog();
    }

    /**
     * process current site data
     *
     * @return void
     */
    protected function process(){

        $pages = array_map(function($post){
            return $post->ID;
        }, self::get_home_page_by_template());

        foreach( $pages as $page_id ){

            \WP_CLI::line(sprintf('Updating page %d', $page_id));

            $sections_data = get_field('hw_homepage_sections', $page_id);
            if(in_array("story", array_column( $sections_data, "acf_fc_layout")) 
                && !in_array("couple", array_column( $sections_data, "acf_fc_layout"))){
    
                // duplicate the section into the new couple one
                $story_index = array_search("story", array_column($sections_data, "acf_fc_layout"));
                $couple_section = $sections_data[$story_index];
                $couple_section['acf_fc_layout'] = 'couple';
                array_splice($sections_data, $story_index, 0, [$couple_section]);

                // update the story item index
                $story_index = array_search("story", array_column($sections_data, "acf_fc_layout"));
                // update the old story section removing unwanted fields
                $sections_data[$story_index]['title'] = __('Our Story', 'hola-weddings');
                unset($sections_data[$story_index]['subtitle']);
                unset($sections_data[$story_index]['display_pictures']);
                unset($sections_data[$story_index]['display_story']);
                unset($sections_data[$story_index]['hide_names']);

                update_field('hw_homepage_sections', $sections_data, $page_id);

                \WP_CLI::line(sprintf('Sections updated', $page_id));
            }
        }
    }

    /**
     * return a array of pages with the home template assigned
     *
     * @return array
     */
    protected function get_home_page_by_template(){

        $args = array(
            'meta_key'   => '_wp_page_template',
            'meta_value' => 'endpoints/home.php',
            'post_type'  => 'page',
            'post_status' => 'publish'
        );

        return get_pages($args);
    }
}