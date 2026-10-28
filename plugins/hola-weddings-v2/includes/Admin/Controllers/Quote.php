<?php

namespace HW\Admin\Controllers;

use HW\Quotes;
use HW\Traits\Singleton;

class Quote{

    use Singleton;

    public function __construct()
    {
        //add_action( 'wp_ajax_get_ccv', [ $this, 'get_ccv' ] );
    }

    /**
     * handle ajax request for ccv
     *
     * @return void
     */
    public function get_ccv(){
        
        $user = wp_get_current_user();

        if( !current_user_can('administrator') && !in_array('quote-manager', $user->roles) ){
            return 0;
        }
        
        $quote = hw_get_quote($_POST['id']);
        $card = $quote->getCardData();
        
        if($quote->hasCardData()){
            
            $card = $quote->getCardData();
            
            die($card->getCCV());
        }else{
            
            return 0;
        }
    }
}