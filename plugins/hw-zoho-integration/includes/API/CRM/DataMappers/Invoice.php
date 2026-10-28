<?php

namespace HWZoho\API\CRM\DataMappers;

use com\zoho\crm\api\record\Record;
use com\zoho\crm\api\util\Choice;
use HW\Models\DecoratorModel;
use HW\Models\DynamicPrice;
use HW\Models\Invoice as ModelsInvoice;
use HWZoho\API\CRM\DataMappers\DynamicPrice as DataMappersDynamicPrice;

class Invoice extends DataMapper{

    public function getAttributesMap()
    {
        return [
            'id' => 'zoho_payment_id',
            'Payment_Amount' => 'hw_amount',
            'Payment_Date' => 'hw_due_date',
            'Date_Paid' => 'hw_paid_date',
            'Status' => [
                'hw_invoice_status',
                fn( $value ) => $value instanceof Choice ? $value->getValue() : $value
            ],
            'Payment_Number' => [
                'hw_type',
                fn( $value ) => $value instanceof Choice ? $value->getValue() : $value
            ]
        ];
    }
}
