<?php

namespace HW\CLI;

use HW\Database;

class RoomBlocks{

    public function migrateAll(){
        
        global $wpdb;

        $sites = get_sites([
            'number' => 9999
        ]);

        // print site ids to test
        /*\WP_CLI\Utils\format_items(
            'ids',
            $sites,
            [ 'blog_id' ]
        );*/

        foreach($sites as $site){

            $blog_id = $site->blog_id;
            switch_to_blog($blog_id);

            \WP_CLI::line(sprintf('Processing site %s', $site->domain));
            
            // call the method on every site
            Database::getInstance()->do_updates();

            $rooms = hw_get_rooms_data();

            if(!$rooms){

                \WP_CLI::line(__('No rooms data'));
                continue;
            }

            foreach($rooms as $room_id => $room_data){

                /************************  */
                /** created the room block */
                /************************  */

                // if room is not enabled skip
                if(!isset($room_data['enabled'])){

                    continue;
                }

                $room = hw_get_room($room_id);

                $post_data = [
                    'post_type' => 'room_blocks',
                    'post_title' => $room['name'],
                    'post_status' => 'publish',
                    'meta_input' => [
                        'hw_room_id' => $room_id,
                        'hw_occupancy_rules' => $room_data['prices'],
                        'hw_inventory' => $room_data['inventory'],
                        'hwzi_room_block_id' => $room_data['zoho_id']
                    ]
                ];

                $room_block_id = wp_insert_post($post_data);
            
                if(is_wp_error($room_block_id)){
                    
                    \WP_CLI::error(sprintf('Error creating Room block for room #%s - %s', $room_id, $room['name']));
                    return;
                }

                \WP_CLI::success(sprintf('Room block for room %s created', $room_id));

                /************************ */
                /** update dynamic prices */
                /************************ */

                // search for dynamic prices with given room id and replace it for the new room block id
                $query = new \WP_Query([
                    'post_type' => 'dynamic_prices',
                    'posts_per_page' => -1,
                    'meta_query' => [
                        [
                            'key'   => 'hw_room',
                            'value' => $room_id
                        ]
                    ]
                ]);

                if($query->have_posts()){
                    foreach($query->posts as $post){
                        
                        update_post_meta($post->ID, 'hw_room_block', $room_block_id);
                        
                        \WP_CLI::success(sprintf('- Dynamic price #%s updated', $post->ID));
                    }
                }

                /******************************* */
                /** update rooms inventory table */
                /******************************* */

                $table_name = $wpdb->prefix . 'rooms_inventory';
                $sql = "UPDATE {$table_name} SET room_block_id = {$room_block_id} WHERE room_id = {$room_id};";
                $wpdb->query($sql);
                \WP_CLI::success(sprintf('Updated inventory for room #%s', $room_id));
            }

            restore_current_blog();
        }
    }


    /**
     * delete all room block in all sites
     *
     * @return void
     */
    public function deleteAll(){
        
        global $wpdb;

        $sites = get_sites([
            'number' => 9999
        ]);

        foreach($sites as $site){

            $blog_id = $site->blog_id;
            switch_to_blog($blog_id);
            $count = 0;

            $query = new \WP_Query([
                'post_type' => 'room_blocks',
                'post_status' => 'any',
                'posts_per_page' => -1
            ]);

            if($query->have_posts()){
                foreach($query->posts as $post){
                    wp_delete_post($post->ID, true);
                    $count++;
                }
            }

            if($count > 0){
                \WP_CLI::success(sprintf('Delete %d for post #%s', $count, $site->domain));
            }

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
    public function migrate_single_site( $args ){

        global $wpdb;

        if(!isset($args[0]) || empty($args[0])){
            \WP_CLI::error(__('Invalid id'));
            return;
        }
        
        $site = get_site($args[0]);

        if(!$site instanceof \WP_site){
            \WP_CLI::error(__('Website doesn\'t exist'));
            return;
        }

        $blog_id = $site->blog_id;
        switch_to_blog($blog_id);

        // call the method on every site
        Database::getInstance()->do_updates();

        \WP_CLI::line(sprintf('Processing site %s', $site->domain));

        $rooms = hw_get_rooms_data();

        if(!$rooms){

            \WP_CLI::error(__('No rooms data'));
            return;
        }

        foreach($rooms as $room_id => $room_data){

            /************************  */
            /** created the room block */
            /************************  */

            // if room is not enabled skip
            if(!isset($room_data['enabled'])){

                return;
            }

            $room = hw_get_room($room_id);

            $post_data = [
                'post_type' => 'room_blocks',
                'post_title' => $room['name'],
                'post_status' => 'publish',
                'meta_input' => [
                    'hw_room_id' => $room_id,
                    'hw_occupancy_rules' => $room_data['prices'],
                    'hw_inventory' => $room_data['inventory'],
                    'hwzi_room_block_id' => $room_data['zoho_id']
                ]
            ];

            $room_block_id = wp_insert_post($post_data);
        
            if(is_wp_error($room_block_id)){
                
                \WP_CLI::error(sprintf('Error creating Room block for room #%s - %s', $room_id, $room['name']));
                return;
            }

            \WP_CLI::success(sprintf('Room block for room %s created', $room_id));

            /************************ */
            /** update dynamic prices */
            /************************ */

            // search for dynamic prices with given room id and replace it for the new room block id
            $query = new \WP_Query([
                'post_type' => 'dynamic_prices',
                'posts_per_page' => -1,
                'meta_query' => [
                    [
                        'key'   => 'hw_room',
                        'value' => $room_id
                    ]
                ]
            ]);

            if($query->have_posts()){
                foreach($query->posts as $post){
                    
                    update_post_meta($post->ID, 'hw_room_block', $room_block_id);
                    
                    \WP_CLI::success(sprintf('- Dynamic price #%s updated', $post->ID));
                }
            }

            /******************************* */
            /** update rooms inventory table */
            /******************************* */

            $table_name = $wpdb->prefix . 'rooms_inventory';
            $sql = "UPDATE {$table_name} SET room_block_id = {$room_block_id} WHERE room_id = {$room_id};";
            $wpdb->query($sql);
            \WP_CLI::success(sprintf('Updated inventory for room #%s', $room_id));
        }

        restore_current_blog();
    }
}