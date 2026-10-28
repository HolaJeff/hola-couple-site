<?php

use HW\UI\Forms\Controls\Editor;
use HW\UI\Forms\Controls\Select;
use HW\UI\Forms\Controls\Text;

Select::make('hw_invoice_payment_btn_action')
    ->label('Invoice Payment Button Action')
    ->value(hw_get_invoice_payment_btn_action())
    ->options([
        'payment-form' => 'Payment Form',
        'request-payment-link' => 'Request payment link'
    ])
    ->attributes([
        'x-model' => 'invoicePaymentBtnAction'
    ])
    ->render();

Editor::make('hw_invoice_payment_custom_message')
    ->value(hw_invoice_payment_custom_message())
    ->label('Custom Message')
    ->show("invoicePaymentBtnAction == 'request-payment-link'")
    ->render();

Text::make('hw_invoice_payment_message_alternative_email')
    ->value(hw_invoice_payment_message_alternative_email())
    ->label('Alternative email')
    ->show("invoicePaymentBtnAction == 'request-payment-link'")
    ->placeholder('Example: hola@holaweddings.com')
    ->description('Override default email: hola@holaweddings.com')
    ->render();
