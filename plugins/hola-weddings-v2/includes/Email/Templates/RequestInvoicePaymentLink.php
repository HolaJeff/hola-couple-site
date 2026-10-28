<?php

namespace HW\Email\Templates;

use HW\Email\AbstractTemplate;
use HW\Models\Invoice;

class RequestInvoicePaymentLink extends AbstractTemplate {

    protected Invoice $invoice;

    /**
     * constructor
     *
     * @param Invoice $invoice
     */
    public function __construct(Invoice $invoice) {

        $this->invoice = $invoice;
    }

    public function getSubject(): string {

        return sprintf( __('Invoice payment link request for website %s', 'hola-weddings'), home_url() );
    }

    public function getTemplate(): string {

        return 'templates/emails/account/request-invoice-payment-link.php';
    }

    public function getData(): array {

        $user = wp_get_current_user();

        return [
            'username' => $user?->display_name ?? 'undefined_username',
            'user_email' => $user?->user_email ?? 'undefined_email',
            'invoice_id' => $this->invoice->getID(),
            'invoice_title' => $this->invoice->getTitle(),
            'invoice_link' => $this->invoice->getEditLink(),
            'current_website' => home_url()
        ];
    }

    /**
     * return array of recipients
     *
     * @return array
     */
    public function getRecipients(): array {

        $recipient = hw_invoice_payment_message_alternative_email() ?: (function_exists('hw_agency_ops_email') ? hw_agency_ops_email() : get_option('admin_email')); // phase4-ops-email
        
        return [
            $recipient
        ];
    }
}