<?php

namespace HW\Email\Templates;

use HW\Email\AbstractTemplate;
use HW\Models\Transaction;

class CanceledTransaction extends AbstractTemplate {

    protected Transaction $transaction;

    /**
     * constructor
     *
     * @param Transaction $transaction
     */
    public function __construct(Transaction $transaction) {

        $this->transaction = $transaction;
    }

    public function getSubject(): string {

        return __('Your Payment was Canceled', 'hola-weddings');
    }

    public function getTemplate(): string {

        // was pointing at the PENDING body — guests read "being processed"
        // under a "canceled" subject (found on the 2026-09-01 test drive)
        return 'templates/emails/pos/canceled-transaction.php';
    }

    public function getData(): array {

        $invoice = $this->transaction->getInvoice();
        $user = get_user_by('ID', $this->transaction->getAuthorID());

        return [
            'username' => $user->display_name,
            'invoice_id' => $invoice->getID(),
            'invoice_title' => $invoice->getTitle(),
            'transaction_id' => $this->transaction->getID(),
            'transaction_status' => hw_get_transaction_status_label($this->transaction->getStatus()),
            'card_digits' => substr($this->transaction->getCard()?->getNumber(), -4),
            'amount' => hw_format_price($this->transaction->getAmount())
        ];
    }

    /**
     * return array of recipients
     *
     * @return array
     */
    public function getRecipients(): array {

        $user = get_user_by('ID', $this->transaction->getAuthorID());
        
        $recipient = $user instanceof \WP_User ? $user->user_email : $this->transaction->getQuote()?->getBillingInformation()?->getEmail();

        return [
            $recipient
        ];
    }
}