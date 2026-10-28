<?php

namespace HWZoho;

class Frontend{

    use Traits\Singleton;

    /**
     * Initialize hooks
     *
     * @return void
     */
    public function __construct(){

        add_filter('hw_on_before_create_quote', [$this, 'add_quote_meta'], 10, 3);
        add_action('hw_after_create_quote', [$this, 'submit_to_webhook'], 10, 3);
    }

    /**
     * Add additional information to post meta before is created
     *
     * @param array $data
     * @param HW\Models\RoomBlock $room_block
     * @return array
     */
    public function add_quote_meta($data, $room_block){
        
        $data['meta_input']['zoho_wedding_id'] = hw_get_option('hwz_wedding_id', null);
        $data['meta_input']['zoho_room_block_id'] = $room_block->get('hwzi_room_block_id');

        return $data;
    }

    /**
     * Submit quote data to zoho webhook
     *
     * @param int $post_id
     * @param array $data
     * @return void
     */
    public function submit_to_webhook( $post_id, $data, $room_block ){

        $wedding_id = get_option('hwz_wedding_id', null);
        $zoho_room_block_id = $room_block->get('hwzi_room_block_id');

        // If wedding id wasn't set stop here. the webhook function refuses to create orphan records.
        // No need to waste Zoho credits
        if(empty($wedding_id) || empty($zoho_room_block_id)){

            return;
        }

        // Unset data we don't want to send
        unset($data['hw_card']);
        unset($data['hw_iv']);

        if(!empty($data['hw_insurance_plan_id'])){

            $plan = hw_api_get_insurance_plan($data['hw_insurance_plan_id']);
            $data['hw_insurance_plan_name'] = $plan['name'];
        }else{
            $data['hw_insurance_plan_name'] = 'No insurance selected';
        }

        $data = array_merge($data, [
            'quote_id' => $post_id,
            'zoho_wedding_id' => $wedding_id,
            'zoho_room_block_id' => $zoho_room_block_id,
            'wedding_group_type' => hw_get_option('hwzi_wedding_group_type', ''),
            'language' => explode( '_', get_locale() )[0]
        ]);
        
        // Request args
        $args = [
            'body' => json_encode($data),
            'method' => 'POST',
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
                'Connection' => 'keep-alive'
            ]
        ];

        // Make the request
        $response = wp_remote_post('https://www.zohoapis.com/crm/v2/functions/create_reservation_and_contact/actions/execute?auth_type=apikey&zapikey=1003.a84ea8a9d6907d17c79f8f1c0cd2ceb1.c781f6f2387c5c016b7eca7dd3e0a631', $args);

        //Check for success
        if(!is_wp_error($response) && ($response['response']['code'] == 200 || $response['response']['code'] == 201)) {
            
            $body = json_decode(wp_remote_retrieve_body( $response ), true);
            $data = json_decode($body['details']['output'], true);
            $res_id = $data['reservation_id'];
            $contact_id = $data['contact_id'];

            update_post_meta($post_id, 'zoho_reservation_id', $res_id);
            update_post_meta($post_id, 'zoho_contact_id', $contact_id);

            zoho_log(sprintf('Quote #%s uploaded to ZOHO with Reservation ID %s', $post_id, $res_id ));

        }else{ // Or failure

            if(is_wp_error($response)){

                //zoho_add_notice( 'Error.<br><br>' . print_r( $response->get_error_messages(), true ), 'error' );
                zoho_log(sprintf('An error ocurred for Quote #%s. Errors: %s', $post_id, print_r( $response->get_error_messages(), true ) ), 'ERROR');
            }else{

                zoho_log(sprintf('An error ocurred for Quote #%s. No data.', $post_id), 'ERROR');
            }
        }
    }
}