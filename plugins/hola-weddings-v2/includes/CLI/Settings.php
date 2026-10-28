<?php

namespace HW\CLI;

class Settings{

    public function updateTransferFields(){
        
        global $wpdb;

        $sites = get_sites([
            'number' => 9999
        ]);

        foreach($sites as $site){

            $blog_id = $site->blog_id;
            switch_to_blog($blog_id);

            \WP_CLI::line(sprintf('Processing site %s', $site->domain));

            $amount = hw_get_service_fee();
            
            if($amount > 0){
                
                update_option('hw_has_transfer', 1);
                \WP_CLI::line(sprintf('Updated', $site->domain));
            }
            

            restore_current_blog();
        }
    }
}