<?php

namespace HWZoho\REST\Operations\Import\Importers;

use com\zoho\crm\api\record\Record;
use HW\Models\Quote as ModelsQuote;
use HW\Models\RoomBlock;
use HWZoho\API\Auth\OAuth;
use HWZoho\API\CRM\Modules\Contacts;
use HWZoho\REST\Operations\Import\Adapters\Quote as AdaptersQuote;
use HWZoho\REST\Operations\Import\Importer;

class Quote extends Importer{

    /**
     * handle create CRUD operation
     *
     * @param array $data
     * @return bool|WP_Error
     */
    public function create(array $data){

        $zoho_reservation_id = $data['reservation_id'];

        if(!isset($data['reservation_name']) || empty(trim($data['reservation_name']))){

            return new \WP_Error(400, "Reservation name is required", ['code' => 'REQUIRED_FIELD']);
        }

        // get the quote, check for reservation id
        $quote = hwzi_get_post_by_zoho_record_id($zoho_reservation_id, 'zoho_reservation_id', 'quote');
        if($quote instanceof \WP_Post){
            
            return new \WP_Error(400, "A reservation already exists for id {$zoho_reservation_id}", ['code' => 'DUPLICATED_RECORD']);
        }

        $meta_data = [
            'hw_nights' => getNights($data['check_in'], $data['check_out']),
            'zoho_reservation_id' => $zoho_reservation_id,
            'zoho_contact_id' => $data['contact_id'],
            'hw_quote_status' => 'pending',
            'hw_checkin' => $data['check_in'],
            'hw_checkout' => $data['check_out'],
            'hw_adults' => $data['adults'],
            'hw_teen' => $data['teen'],
            'hw_children' => $data['children'],
            'hw_infants' => $data['infants'],
            'hw_insurance_total' => $data['insurance_total'],
            'hw_room_total' => $data['room_total'],
            'hw_service_fee' => $data['service_fee'],
            'hw_total' => $data['total_amount'],
            'hw_guests' => isset($data['guests']) ? $data['guests'] : []
        ];

        // change status
        if(!empty(trim($data['reservation_status']))){

            $status = sanitize_title(trim($data['reservation_status']));
            
            if($status == 'cancelled'){
                $status = 'canceled';
            }
            $meta_data['hw_quote_status'] = $status;
        }

        // set classic vacation data
        if(isset($data['booking_number']) && !empty($data['booking_number'])){

            list($group_id, $booking_id) = explode('-', $data['booking_number']);
            $meta_data['hw_cv_group_id'] = $group_id;
            $meta_data['hw_cv_booking_id'] = $booking_id;
        }

        if(!empty($data['contact_id'])){

            $contact = Contacts::getRecord($data['contact_id']);
            if($contact instanceof Record){

                $contact_data = $contact->getKeyValues();
                $customer_data = [
                    'first_name' => $contact_data['First_Name'],
                    'last_name' => $contact_data['Last_Name'],
                    'phone_number' => $contact_data['Phone'],
                    'email' => $contact_data['Email'],
                    'address' => $contact_data['Mailing_Street'],
                    'zip' => $contact_data['Mailing_Zip'],
                    'state' => $contact_data['Mailing_State'],
                    'country' => $contact_data['Mailing_Country'],
                ];

                $meta_data['hw_first_name'] = $customer_data['first_name'];
                $meta_data['hw_last_name'] = $customer_data['last_name'];
                $meta_data['hw_email'] = $customer_data['email'];
                $meta_data['hw_phone'] = $customer_data['phone_number'];
                $meta_data['hw_state'] = $customer_data['state'];
                $meta_data['hw_country'] = $customer_data['country'];
                $meta_data['hw_customer_data'] = $customer_data;
            }
        }

        if(isset($data['room_block_id']) && !empty(['room_block_id'])){

            $room_block = hwzi_get_post_by_zoho_record_id($data['room_block_id'], 'hwzi_room_block_id', 'room_blocks');
            
            if($room_block instanceof \WP_Post){

                $room_block = new RoomBlock($room_block);
                $meta_data['hw_room_block_id'] = $room_block->getID();
                $meta_data['hw_room_id'] = $room_block->getRoomId();
            }
        }

        // initial post data
        $post_data = [
            'post_title' => $data['reservation_name'],
            'post_type' => 'quote',
            'post_status' => 'publish',
        ];

        if(isset($meta_data['hw_email'])){
            $user_id = email_exists($meta_data['hw_email']);

            // check is user doesn't exists then create it
            if($user_id == false){

                $username = $meta_data['hw_email'];
                $password = wp_generate_password();
                
                $user_id = wp_insert_user([
                    'user_login' => $username,
                    'user_pass' => $password,
                    'user_email' => $meta_data['hw_email'],
                    'first_name' => $meta_data['hw_first_name'],
                    'last_name' => $meta_data['hw_last_name']
                ]);

                add_existing_user_to_blog( [ 'user_id' => $user_id, 'role' => 'subscriber' ]);
            }

            $post_data['post_author'] = $user_id;
        }

        $post_id = wp_insert_post($post_data, true);

        // an error ocurred
        if(is_wp_error($post_id)){

            return new \WP_Error(500, $post_id->get_error_message(), [ 'code' => 'NOT_CREATED'] );
        }

        $this->updateMetaData($meta_data, $post_id);

        return true;
    }

    /**
     * handle update CRUD operation
     *
     * @param array $data
     * @return bool|WP_Error
     */
    public function update(array $data){

        $zoho_reservation_id = $data['id'];

        // get the quote, check for reservation id
        $quote = hwzi_get_post_by_zoho_record_id($zoho_reservation_id, 'zoho_reservation_id', 'quote');
        if(!$quote instanceof \WP_Post){
            
            return new \WP_Error(404, "Record not found", ['code' => 'NOT_FOUND']);
        }

        // model
        $model = new ModelsQuote($quote);

        $meta_data = [];

        // insurance total
        if(isset($data['insurance_total']) && $model->getInsuranceTotal() != $data['insurance_total']){
            $meta_data['hw_insurance_total'] = $data['insurance_total'];
        }

        // service fee
        if(isset($data['service_fee']) && $model->getServiceFee() != $data['service_fee']){
            $meta_data['hw_service_fee'] = $data['service_fee'];
        }

        // insurance total
        if(isset($data['total_amount']) && $model->getTotal() != $data['total_amount']){
            $meta_data['hw_total'] = $data['total_amount'];
        }

        // adults
        if(isset($data['adults']) && $model->getAdults() != $data['adults']){
            $meta_data['hw_adults'] = $data['adults'];
        }

        // teen
        if(isset($data['teen']) && $model->getTeen() != $data['teen']){
            $meta_data['hw_teen'] = $data['teen'];
        }

        // children
        if(isset($data['children']) && $model->getChildren() != $data['children']){
            $meta_data['hw_children'] = $data['children'];
        }

        // infants
        if(isset($data['infants']) && $model->getInfants() != $data['infants']){
            $meta_data['hw_infants'] = $data['infants'];
        }

        // checkin
        if(isset($data['check_in']) && $model->getCheckin() != $data['check_in']){
            $meta_data['hw_checkin'] = $data['check_in'];
        }

        // checkout
        if(isset($data['check_out']) && $model->getCheckout() != $data['check_out']){
            $meta_data['hw_checkout'] = $data['check_out'];
        }

        // nights
        if((isset($data['check_in']) && $model->getCheckin() != $data['check_in']) 
            || (isset($data['check_out']) && $model->getCheckin() != $data['check_out'])){

            $meta_data['hw_nights'] = getNights($data['check_in'], $data['check_out']);
        }

        // set guests only if not empty
        if(isset($data['guests']) && !empty($data['guests'])){
            $meta_data['hw_guests'] = json_decode($data['guests'], true);
        }

        // change status
        if(!empty(trim($data['reservation_status'])) && $model->getStatus() != trim($data['reservation_status'])){

            $status = sanitize_title(trim($data['reservation_status']));
            
            if($status == 'cancelled'){
                $status = 'canceled';
            }
            $meta_data['hw_quote_status'] = $status;
        }

        // if room total has changed
        if($model->getRoomTotal() != $data['room_total']){

            $meta_data['hw_room_total'] = $data['room_total'];
            $meta_data['hw_rates_per_day'] = '';
        }

        // set classic vacation data
        if(isset($data['booking_number']) && !empty($data['booking_number'])){

            list($group_id, $booking_id) = explode('-', $data['booking_number']);
            $meta_data['hw_cv_group_id'] = $group_id;
            $meta_data['hw_cv_booking_id'] = $booking_id;
        }

        // change contact information only if the contact id has changed
        if(!empty($data['contact_id']) ){

            $contact = Contacts::getRecord($data['contact_id']);
            if($contact instanceof Record){

                $contact_data = $contact->getKeyValues();
                $customer_data = [
                    'first_name' => $contact_data['First_Name'],
                    'last_name' => $contact_data['Last_Name'],
                    'phone_number' => $contact_data['Phone'],
                    'email' => $contact_data['Email'],
                    'address' => $contact_data['Mailing_Street'],
                    'zip' => $contact_data['Mailing_Zip'],
                    'state' => $contact_data['Mailing_State'],
                    'country' => $contact_data['Mailing_Country'],
                ];

                $meta_data['zoho_contact_id'] = $data['contact_id'];
                $meta_data['hw_first_name'] = $customer_data['first_name'];
                $meta_data['hw_last_name'] = $customer_data['last_name'];
                $meta_data['hw_email'] = $customer_data['email'];
                $meta_data['hw_phone'] = $customer_data['phone_number'];
                $meta_data['hw_state'] = $customer_data['state'];
                $meta_data['hw_country'] = $customer_data['country'];
                $meta_data['hw_customer_data'] = $customer_data;
            }
        }

        // change room block information if the room block has changed
        if(isset($data['room_block_id']) && !empty(['room_block_id']) 
            && $model->get('hw_room_block_id') != $meta_data['hw_room_block_id']){

            $room_block = hwzi_get_post_by_zoho_record_id($data['room_block_id'], 'hwzi_room_block_id', 'room_blocks');
            
            if($room_block instanceof \WP_Post){

                $room_block = new RoomBlock($room_block);
                $meta_data['hw_room_block_id'] = $room_block->getID();
                $meta_data['hw_room_id'] = $room_block->getRoomId();
            }
        }

        // initial post data
        $post_data = [
            'ID' => $model->getID()
        ];

        if(isset($data['reservation_name']) && !empty(trim($data['reservation_name']))){
            $post_data['post_title']  = $data['reservation_name'];
        }

        // change post author only if the contact email has changed
        if(isset($meta_data['hw_email'])){

            $user_id = email_exists($meta_data['hw_email']);

            // check is user doesn't exists then create it
            if($user_id == false){

                $username = $meta_data['hw_email'];
                $password = wp_generate_password();
                
                $user_id = wp_insert_user([
                    'user_login' => $username,
                    'user_pass' => $password,
                    'user_email' => $meta_data['hw_email'],
                    'first_name' => $meta_data['hw_first_name'],
                    'last_name' => $meta_data['hw_last_name']
                ]);

                add_existing_user_to_blog( [ 'user_id' => $user_id, 'role' => 'subscriber' ]);
            }

            $post_data['post_author'] = $user_id;
        }
        
        // update the post only if there's an ID and a post title
        if(!empty($post_data['ID'])){
            
            $post_id = wp_update_post($post_data, true);
        }

        // an error ocurred
        if(is_wp_error($post_id)){

            return new \WP_Error(500, $post_id->get_error_message(), [ 'code' => 'NOT_UPDATED'] );
        }

        $this->updateMetaData($meta_data, $model->getID());
    }

    /**
     * handle delete CRUD operation
     *
     * @param array $data
     * @return bool|WP_Error
     */
    public function delete(array $data)
    {
     
        $id = $data['id'];

        // content not found
        if(($post = hwzi_get_quote_by_zoho_id($id)) == false){

            return new \WP_Error(404, "Record not found", ['code' => 'NOT_FOUND']);
        }

        // trash the post
        wp_trash_post($post->ID);

        return true;
    }
}