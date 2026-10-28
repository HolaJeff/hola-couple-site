<?php

namespace HWZoho;

use HWZoho\API\Auth\Configuration;

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

        // Card-authorization flag for Zoho (computed BEFORE the card is stripped:
        // a real stored card + not the card-less fallback = a hold the TO answer
        // will charge). Only the boolean travels — never the card itself.
        $card_on_file = (!empty($data['hw_card']) && empty($data['hw_or_no_card'])) ? 1 : 0;

        // Unset data we don't want to send
        unset($data['hw_card']);
        unset($data['hw_iv']);

        if(!empty($data['hw_insurance_plan_id'])){

            $plan = hw_api_get_insurance_plan($data['hw_insurance_plan_id']);
            $data['hw_insurance_plan_name'] = $plan['name'];
        }else{
            $data['hw_insurance_plan_name'] = 'No insurance selected';
        }

        // Why is this booking PENDING? Stamp the reason at the source so Zoho
        // gets an authoritative Pending_Reason (the platform displays it
        // read-only). Single occupancy pends by policy (set in the booking
        // engine's quote-status logic — Quotes.php); otherwise check the room
        // block's real availability for the stay (guests can book even when the
        // block is full — that's exactly the over-allotment case).
        $pending_reason = '';
        $status = strtolower((string) ($data['hw_quote_status'] ?? ''));
        if (strpos($status, 'pending') !== false) {

            $adults = (int) ($data['hw_adults'] ?? 0);
            if ($adults === 1) {
                $pending_reason = 'Single occupancy';
            } elseif (function_exists('hw_is_room_available')
                      && !hw_is_room_available($room_block, (string) ($data['hw_checkin'] ?? ''), (string) ($data['hw_checkout'] ?? ''))) {
                $pending_reason = 'Room not available in room block';
            } else {
                $pending_reason = 'Other';
            }
        }

        $data = array_merge($data, [
            'quote_id' => $post_id,
            'zoho_wedding_id' => $wedding_id,
            'zoho_room_block_id' => $zoho_room_block_id,
            'wedding_group_type' => hw_get_option('hwzi_wedding_group_type', ''),
            'language' => explode( '_', get_locale() )[0],
            'hw_pending_reason' => $pending_reason,
            'hw_card_on_file' => $card_on_file
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

        // Make the request. Host + zapikey come from config (HWZI_ZOHO_API_HOST /
        // HWZI_DELUGE_ZAPIKEY in wp-config.php), never hardcoded. Unconfigured =
        // integration off: log it, don't post.
        $url = Configuration::get_deluge_function_url('create_reservation_and_contact');
        if(empty($url)){

            zoho_log(sprintf('Quote #%s NOT sent to Zoho: no Deluge zapikey configured (set HWZI_DELUGE_ZAPIKEY in wp-config.php)', $post_id), 'ERROR');
            return;
        }
        $response = wp_remote_post($url, $args);

        //Check for success. A Deluge function answers HTTP 200 even when it failed
        // (unauthenticated connection, execution error, empty output) - only a
        // decoded details.output carrying reservation_id is a real success.
        $code = is_wp_error($response) ? 0 : (int) wp_remote_retrieve_response_code($response);
        $raw = is_wp_error($response) ? '' : wp_remote_retrieve_body($response);
        $body = $raw !== '' ? json_decode($raw, true) : null;
        $out = (is_array($body) && isset($body['details']['output']) && is_string($body['details']['output'])) ? json_decode($body['details']['output'], true) : null;
        if(!is_wp_error($response) && ($code == 200 || $code == 201) && is_array($out) && !empty($out['reservation_id'])) {

            $res_id = $out['reservation_id'];
            $contact_id = isset($out['contact_id']) ? $out['contact_id'] : '';

            update_post_meta($post_id, 'zoho_reservation_id', $res_id);
            update_post_meta($post_id, 'zoho_contact_id', $contact_id);
            delete_post_meta($post_id, 'zoho_sync_error');

            zoho_log(sprintf('Quote #%s uploaded to ZOHO with Reservation ID %s', $post_id, $res_id ));

        }elseif(!is_wp_error($response) && ($code == 200 || $code == 201)){ // 200 but no reservation: the function failed

            $why = '';
            if (is_array($body) && !empty($body['message'])) { $why = (string) $body['message']; }
            if ($why === '' && is_array($out) && !empty($out['message'])) { $why = (string) $out['message']; }
            if ($why === '' && is_array($body) && !empty($body['details']['userMessage']) && is_array($body['details']['userMessage'])) {
                foreach ($body['details']['userMessage'] as $um) {
                    if (is_string($um) && stripos($um, 'error_info') !== false) { $why = substr($um, 0, 400); break; }
                }
            }
            if ($why === '') { $why = 'function returned no reservation (output: ' . substr((string) (isset($body['details']['output']) ? $body['details']['output'] : $raw), 0, 200) . ')'; }
            zoho_log(sprintf('Quote #%s NOT created in Zoho: %s', $post_id, $why), 'ERROR');
            error_log(sprintf('[HWZI] quote #%s NOT created in Zoho: %s', $post_id, $why));
            update_post_meta($post_id, 'zoho_sync_error', substr($why, 0, 600));
            if (function_exists('hw_sync_dead_letter')) {
                hw_sync_dead_letter('booking', (string) $post_id, 'booking did not create a Zoho reservation: ' . $why, array('quote_id' => $post_id, 'http' => $code));
            }

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