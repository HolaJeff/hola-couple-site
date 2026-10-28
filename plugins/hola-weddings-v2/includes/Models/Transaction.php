<?php

namespace HW\Models;

use HW\Email\EmailSender;
use HW\Email\Templates\CanceledTransaction;
use HW\Traits\hasNotes;
use HW\Enums\TransactionStatus;
use HW\Models\CRM\BillingInformation;
use HW\Utils\CardBuilder;

class Transaction extends DecoratorModel{

    use hasNotes;

    public static $post_type = 'transactions';

    public static $STATUS_SUCCESS = 'confirmed';

    public static $STATUS_FAILED = 'failes';

    /**
     * return the status
     *
     * @return string|bool
     */
    public function getStatus(){
        return $this->hw_status;
    }

    /**
     * return the amount
     *
     * @return string|bool
     */
    public function getAmount(){
        return $this->hw_amount;
    }

    /**
     * return invoice id
     *
     * @return string|bool
     */
    public function getInvoiceID(){
        return $this->hw_invoice_id;
    }

    /**
     * return the gateway
     *
     * @return string|bool
     */
    public function getGateway(){
        return $this->get('hw_gateway');
    }

    /**
     * return transaction reference id
     *
     * @return string|bool
     */
    public function getTransactionID(){
        return $this->get('hw_transaction_id');
    }

    /**
     * return transaction currency
     *
     * @return string|bool
     */
    public function getCurrency(){
        return $this->get('hw_currency');
    }

    /**
     * return the invoice instance
     *
     * @return Invoice
     */
    public function getInvoice(){
        return hw_get_invoice($this->getInvoiceID());
    }

     /**
     * return the invoice instance
     *
     * @return Quote
     */
    public function getQuote(){
        return $this->getInvoice()->getQuote();
    }

    /**
     * return card
     *
     * @return \HW\Utils\Card
     */
    public function getCard(){
        
        $card = CardBuilder::empty();

        if($value = $this->get('hw_card')){

            $data = json_decode( $value, true );
            $card->setNumber($data['number'] ?: '')
                ->setExpMonth($data['exp_month'] ?: '')
                ->setExpYear($data['exp_year'] ?: '')
                ->setCCV($data['ccv'] ?: '')
                ->setType($data['type'] ?: '')
                ->setCardHolder($data['holder'] ?: '');
        }
        
        return $card;
    }

    /**
     * return signature
     *
     * @return string
     */
    public function getSignature(){

        return $this->hw_signature;
    }

    /**
     * return the IP address
     *
     * @return string
     */
    public function getIPAddress():string{

        return $this->hw_ip_address;
    }

    /**
     * return transaction billing information
     *
     * @return HW\Models\CRM\BillingInformation
     */
    public function getBillingInformation(){

        $data = json_decode($this->get('hw_billing_information'), true );
        return new BillingInformation($data);
    }

    /**
     * set status
     *
     * @param TransactionStatus $value
     * @return static
     */
    public function setStatus(TransactionStatus $status){

        $this->set('hw_status', $status->value);

        return $this;
    }

    /**
	 * property was updated
	 *
	 * @param string $property
	 * @param mixed $value
	 * @return void
	 */
	public function propertyUpdated($property, $value, $previous){

        if($property == 'hw_status'){
            
            if($previous == TransactionStatus::PENDING->value && $value == TransactionStatus::CANCELED->value){

                $emailTemplate = new CanceledTransaction( $this );
                $sender = new EmailSender();

                // send pending notification email
                $sender->send($emailTemplate);
            }
        }
    }
}