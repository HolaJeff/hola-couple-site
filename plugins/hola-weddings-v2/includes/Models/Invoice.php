<?php

namespace HW\Models;

use DateTime;
use HW\Traits\hasACFFields;

class Invoice extends DecoratorModel{

    use hasACFFields;

    protected $_paid_amount;

    protected $_quote;

    public static $post_type = 'invoices';

    public static $STATUS_PAID = 'paid';

    public static $STATUS_NOTPAID = 'not-paid';

    public static $STATUS_PARTIALLY_PAID = 'partially-paid';

    public static $STATUS_CANCELED = 'canceled';
    
    /**
     * constructor
     *
     * @param WP_Post|integer $post
     */
    public function __construct( $post )
    {
        parent::__construct($post);

        $reservation_id = $this->getQuoteID();

        $this->_quote = hw_get_quote($reservation_id);
    }

    /**
     * return quote id
     *
     * @return int
     */
    public function getQuoteID(){

        return intval($this->get('hw_reservation_id'));
    }

    /**
     * return a quote if reservation id is set
     *
     * @return Quote|false
     */
    public function getQuote(){

        return $this->_quote;
    }

    /**
     * return the amount
     *
     * @return string
     */
    public function getAmount(){

        return intval($this->hw_amount);
    }

    /**
     * return the due date, if format is passed the date is formatted
     *
     * @param string $format
     * @return string|DateTime
     */
    public function getDueDate( $format = null){

        $date = $this->get('hw_due_date');

        if(is_string($format) && $format == 'raw'){

            return $date;
        }

        $date = new DateTime($date);

        if(is_string($format)){

            return $date->format($format);
        }

        return $date;
    }

    /**
     * return the paid date, if format is passed the date is formatted
     *
     * @param string $format
     * @return string|DateTime
     */
    public function getPaidDate( $format = null){

        $date = $this->get('hw_paid_date');

        if(is_string($format) && $format == 'raw'){

            return $date;
        }

        $date = new DateTime($date);

        if(is_string($format)){

            return $date->format($format);
        }

        return $date;
    }

    /**
     * return the invoice currency
     *
     * @return string
     */
    public function getCurrency(){

        return $this->get('hw_currency');
    }

    /**
     * return the status
     *
     * @return string
     */
    public function getStatus(){
        
        $value = $this->getACFField('hw_invoice_status');
        
        return is_array($value) ? $value['value'] : $value;
    }

    /**
     * return the status
     *
     * @return string
     */
    public function getStatusLabel(){

        return $this->getACFField('hw_invoice_status')['label'];
    }

    /**
     * set the status
     *
     * @return void
     */
    public function setStatus($value){

        $this->setACFField('hw_invoice_status', $value);
    }

    /**
     * return the type
     *
     * @return string
     */
    public function getType(){

        return $this->getACFField('hw_type');
    }

    /**
     * return the due amount for the current invoice
     *
     * @return void
     */
    public function getDueAmount(){

        $invoice_amount = $this->getAmount();
        $paid_amount = $this->getPaidAmount();
        
        return $invoice_amount - $paid_amount;
    }

    /**
     * return the paid amout according to successfull transactions
     *
     * @param boolean $forceCalculation
     * @return int
     */
    public function getPaidAmount($forceCalculation = false){

        if($this->_paid_amount != null && !$forceCalculation){
            return $this->_paid_amount;
        }

        $paid = 0;
        $transactions = \hw_get_transactions_by_invoice($this->getID());

        if(!empty($transactions)){
            foreach($transactions as $transaction){
                if($transaction->getStatus() == Transaction::$STATUS_SUCCESS){
                    $paid += $transaction->getAmount();
                }
            }
        }

        return $paid;
    }

    /**
     * return invoice's last transaction
     *
     * @return Transaction|null
     */
    public function getLastTransaction(){

        $transactions = hw_get_transactions_by_invoice($this->getID());

        if(!empty($transactions)){
            return array_shift($transactions);
        }

        return null;
    }

    /**
     * return billing information
     *
     * @return HW\Models\CRM\BillingInformation
     */
    public function getBillingInformation(){

        return $this->_quote->getBillingInformation();
    }

    /**
     * return the quote owner
     *
     * @return int|null
     */
    public function getOwner(){

        return $this->_quote->getOwner();
    }

    /**
     * set the paid date
     *
     * @param DateTime $date
     * @return void
     */
    public function setPaidDate(DateTime $date){

        $this->setACFField('hw_paid_date', $date->format('Ymd'));
    }

    /**
     * maginc methods
     */
    public function toArray(){

        return [
            'title' => $this->getTitle(),
            'amount' => $this->getAmount(),
            'due_amount' => $this->getDueAmount(),
            'paid_amount' => $this->getPaidAmount(),
            'due_date' => $this->getDueDate(),
            'status' => $this->getStatus(),
            'type' => $this->getType()
        ];
    }
}