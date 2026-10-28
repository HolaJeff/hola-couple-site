<?php

use HW\Payment\Gateway\PaymentGatewayManager;

$invoice = $transaction->getInvoice();
$paymentGateway = PaymentGatewayManager::getInstance()->get($transaction->getGateway());
?>
<table class="box">
    <tr>
        <td>
            <div style="font-weight: bold;"><?= __('Transaction Details', 'hola-weddings') ?></div>
            <table style="width: 100%;">
                <tr>
                    <td style="width: 50%;">
                        <div class="label" style="font-size: 12px;"><?= __('Authorized Amount', 'hola-weddings') ?></div>
                        <?=  hw_format_price($transaction->getAmount(), $transaction->getCurrency()) ?>
                    </td>
                    <!-- <td style="width: 50%;">
                        <div class="label" style="font-size: 12px;"><?= __('Status', 'hola-weddings') ?></div>
                        <?= $transaction->getStatus() ?>
                    </td> -->
                    <td style="width: 50%;">
                        <div class="label" style="font-size: 12px;"><?= __('Merchant of Record', 'hola-weddings') ?></div>
                        <?= $paymentGateway->getName() ?>
                    </td>
                </tr>
                <tr>
                    <td style="width: 50%;">
                        <div class="label" style="font-size: 12px;"><?= __('Transaction ID', 'hola-weddings') ?></div>
                        <?= $transaction->getTransactionID() ?>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>