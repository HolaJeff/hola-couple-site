<?php

namespace HWZoho\REST\Operations\Import\Importers;

use HWZoho\REST\Operations\Import\Importer;

class Invoice extends Importer{

    /**
     * update existing record
     *
     * @param \WP_Post $post
     * @param Array $data
     * @return void
     */
    public function create(Array $data)
    {

        $zoho_payment_id = $data['id'];

        // if is new is not passed always treat the request as an update, if the record is not found it will be created
        $post_id = null;
        $post_data = [];

        $invoice = hwzi_get_invoice_by_zoho_id( $zoho_payment_id );
        // invoice already exists, should use PUT verb
        if($invoice instanceof \WP_Post){
            
            return new \WP_Error(400, "A payment already exists for id {$zoho_payment_id}", ['code' => 'DUPLICATED_RECORD']);
        }

        // get the quote, check for reservation id
        $quote = hwzi_get_quote_by_zoho_id($data['zoho_reservation_id']);
        if(!$quote instanceof \WP_Post){
            
            return new \WP_Error(400, 'Invalid reservation ID', ['code' => 'INVALID_DATA']);
        }

        
        // initial post data
        $post_data = [
            'post_title' => $data['payment_name'],
            'post_type' => $data['post_type'],
            'post_status' => 'publish',
        ];

        $post_id = wp_insert_post($post_data, true);

        // an error ocurred
        if(is_wp_error($post_id)){

            return new \WP_Error(500, $post_id->get_error_message(), [ 'code' => 'NOT_CREATED'] );
        }

        $meta_data = [
            'hw_reservation_id' => $quote->ID,
            'hw_amount'=> $data['amount'],
            'hw_due_date'=> $data['payment_date'],
            'hw_paid_date'=> $data['date_paid'],
            'hw_invoice_status'=> $data['status'],
            'hw_type'=> $data['payment_number'],
            'zoho_payment_id' => $zoho_payment_id,
        ];

        // update fields with ACF function
        foreach($meta_data as $key => $value){
            update_field( $key, $value, $post_id);
        }

        return [ 'id' => $post_id ];
        
    }

    /**
     * update existing record
     *
     * @param \WP_Post $post
     * @param Array $data
     * @return void
     */
    public function update(Array $data)
    {

        $zoho_payment_id = $data['id'];

        // if is new is not passed always treat the request as an update, if the record is not found it will be created
        $post_id = null;
        $post_data = [];

        $invoice = hwzi_get_invoice_by_zoho_id( $zoho_payment_id );
        // invoice already exists, should use PUT verb
        if(!$invoice instanceof \WP_Post){
            
            return new \WP_Error(404, 'Payment record not found', ['code' => 'NOT_FOUND']);
        }

        $post_id = $invoice->ID;
        
        if(isset($data['payment_name']) && !empty($data['payment_name'])){
            // update the post
            $invoice->post_title = $data['payment_name'];
            $invoice->post_name = sanitize_title($post_data['post_title']);
            $invoice->post_status = $invoice->post_status;

            $save = wp_update_post($invoice, true);

            // an error ocurred
            if(is_wp_error($save)){

                return new \WP_Error(500, $save->get_error_message(), [ 'code' => 'NOT_UPDATED'] );
            }
        }

        $meta_data = [];

        if(isset($data['amount'])){
            
            $meta_data['hw_amount'] = $data['amount'];
        }

        if(isset($data['payment_date'])){
            
            $meta_data['hw_due_date'] = $data['payment_date'];
        }

        if(isset($data['status'])){
            // fix for ACF, put the value inside array
            $meta_data['hw_invoice_status'] = [ 'value' => $data['status'] ];
        }

        if(isset($data['payment_number'])){
            
            $meta_data['hw_type'] = [ 'value' => $data['payment_number'] ];
        }

        if(isset($data['date_paid'])){
            
            $meta_data['hw_paid_date'] = $data['date_paid'];
        }

        // update fields with ACF function
        foreach($meta_data as $key => $value){
            update_field( $key, $value, $post_id);
        }

        return [ 'id' => $post_id ];
        
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
        if(($post = hwzi_get_invoice_by_zoho_id($id)) == false){

            return new \WP_Error(404, "Record not found", ['code' => 'NOT_FOUND']);
        }

        // trash the post
        wp_trash_post($post->ID);

        return true;
    }
}