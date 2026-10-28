<?php

/**
 * check pending transactions and update them
 */

use HW\Enums\TransactionStatus;
use HW\Integrations\ClassicVacations\CRM\Payment\Payment;
use HW\Integrations\ClassicVacations\CRM\Payment\PaymentRecord;

/**
 * review classic vacations pending transactions
 *
 * @param \HW\Models\Transaction $transaction
 * @param \HW\Utils\Logger $logger
 * @return bool
 */
function hw_cv_review_pending_transactions( $keep_status, $transactionModel, $logger ){

    $quote = $transactionModel->getQuote();
    $group_id = $quote->get('hw_cv_group_id');
    $booking_id = $quote->get('hw_cv_booking_id');
    $transaction_ref_id = $transactionModel->getTransactionID();

    // if is hola weddings test group remove booking id
    /* if($group_id == '54552'){

        $booking_id = '';
    } */
    $logger->debug(sprintf('Classic Vacations: Ref %s Booking %d group %d', $transaction_ref_id, $booking_id, $group_id));

    try{

        $payment_record = Payment::getRecord($transaction_ref_id, $booking_id, $group_id);
        
    }catch(Exception $e){
        
        $logger->error(sprintf('Classic Vacations: An error ocurred retrieving payment record: %s', $e->getMessage()));
        return true;
    }

    // if payment was successful get payment info from CV API
    if($payment_record instanceof PaymentRecord){

        $status = false;

        switch($payment_record->getStatus()){
            case 'SUCCESS':
                $transactionModel->setStatus(TransactionStatus::CONFIRMED);
                $status = TransactionStatus::CONFIRMED->value;
                break;
            case 'FAILED':
                $transactionModel->setStatus(TransactionStatus::FAILED);
                $status = TransactionStatus::FAILED->value;
                break;
        }

        if($status != false){
            $logger->debug(sprintf('Classic Vacations: Status updated to %s', $status), 'Cron Job hw_cv_review_pending_transactions' );
            $transactionModel->addNote(sprintf('Status updated to %s', $status) , 'hw_cv_review_pending_transactions');
        }
    }

    return $keep_status;
}