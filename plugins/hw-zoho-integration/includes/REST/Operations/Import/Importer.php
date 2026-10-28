<?php

namespace HWZoho\REST\Operations\Import;

use HWZoho\REST\Operations\Import\AbstractAdapter;

abstract class Importer implements ImporterInterface{

    public function updateMetaData(array $meta_data, $post_id){

        // update fields with ACF function
        foreach($meta_data as $key => $value){
            update_post_meta($post_id, $key, $value);
        }
    }
}