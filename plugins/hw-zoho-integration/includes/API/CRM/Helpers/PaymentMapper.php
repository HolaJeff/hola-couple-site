<?php

namespace HWZoho\API\CRM\Helpers;

use com\zoho\crm\api\record\Record;

class PaymentMapper{

    /**
     * map the record data for a post
     *
     * @param Record $record
     * @return array
     */
    public static function map(Record $record){

        return [
            'title' => $record->getKeyValue('Name'),
            'meta' => [
                'hw_amount' => $record->getKeyValue('Payment_Amount'),
                'hw_due_date' => $record->getKeyValue('Payment_Date'),
                'hw_invoice_status' => self::mapStatus($record->getKeyValue('Status')->getValue()), 
                'hw_type' => self::mapType($record->getKeyValue('Payment_Number')->getValue()),
                'hw_zoho_payment_id' => $record->getKeyValue('id')
            ]
        ];
    }

    /**
     * map the type with internal types
     *
     * @param string $type
     * @return string
     */
    private static function mapStatus($status){
        
        $statuses = [
            'Not Paid' => 'not-paid',
            'Paid' => 'paid'
        ];

        return isset($statuses[$status]) ? $statuses[$status] : '';
    }

    /**
     * map the payment number with internal types
     *
     * @param string $type
     * @return string
     */
    private static function mapType($type){
        
        $types = [
            'First' => 'first',
            'Second' => 'second',
            'Third' => 'third',
            'Final' => 'final',
            'Extra' => 'extra',
            'Transfers' => 'transfers',
            'Room Block Deposit' => 'room_block_deposit',
            'Insurance' => 'insurance',
            'Refund' => 'refund'
        ];

        return isset($types[$type]) ? $types[$type] : '';
    }
}