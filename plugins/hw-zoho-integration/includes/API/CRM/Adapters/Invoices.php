<?php

namespace HWZoho\API\CRM\Adapters;

use com\zoho\crm\api\util\Choice;
use HW\Models\DecoratorModel;
use HW\Models\Invoice;
use HWZoho\API\CRM\Fields\Payment as FieldsPayment;
use HWZoho\API\Utility\Field;

class Invoices implements AdapterInterface{

    protected $model;

    /**
     * constructor
     *
     * @param DecoratorModel $model
     */
    public function __construct(Invoice $model){
        
        $this->model = $model;
    }

    /**
     * return a list or corresponding fields
     *
     * @param array $invoiceFields
     * @return array
     */
    public function getFields( array $invoiceFields ) {

        $fields = [];

        foreach($invoiceFields as $fieldKey){

            $field = $this->getField($fieldKey);
            if($field){
                $fields[$field->getFieldName()] = $field->getValue();
            }
        }

        return $fields;
    }

    /**
     * convert a single field to zoho field
     *
     * @param string $name
     * @return Field
     */
    public function getField( $name ){

        switch($name){
            case 'hw_invoice_status':
                return new Field( FieldsPayment::$STATUS , new Choice($this->model->getStatusLabel()));
                break;
            case 'hw_amount':
                return new Field( FieldsPayment::$PAYMENTAMOUNT , floatval($this->model->getAmount()));
                break;
            case 'hw_due_date':
                return new Field( FieldsPayment::$PAYMENTDATE, $this->model->getDueDate());
                break;
            case 'hw_paid_date':
                return new Field( FieldsPayment::$PAIDDATE, $this->model->getPaidDate());
                break;
            case 'hw_type':
                return new Field( FieldsPayment::$PAYMENTNUMBER , new Choice($this->model->getType()['label']));
                break;
            default:
                return null;
                break;
        };
    }
}