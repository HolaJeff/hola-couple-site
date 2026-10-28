<?php

use HW\Email\EmailSender;
use HW\Email\Templates\PendingTransaction;
use HW\Enums\TransactionStatus;
use HW\Models\Transaction;
use HW\Payment\PoS;

/**
 * send out notifications on payment processed
 *
 * @param Transaction $model
 * @return void
 */
function hw_emailing_send_payment_processed_notification($model){

    if($model->getStatus() == TransactionStatus::PENDING->value){

        $emailTemplate = new PendingTransaction( $model );
        $sender = new EmailSender();

        // send pending notification email
        $sender->send($emailTemplate);
    }
}
add_action('hw_pos_payment_processed', 'hw_emailing_send_payment_processed_notification', 10, 1);