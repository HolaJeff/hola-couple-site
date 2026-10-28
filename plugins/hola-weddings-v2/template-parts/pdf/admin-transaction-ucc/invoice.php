<?php

use HW\Payment\Gateway\PaymentGatewayManager;

$invoice = $transaction->getInvoice();
?>
<table class="box">
    <tr>
        <td>
            <div style="font-weight: bold; margin-bottom: 10px;"><?= __('Invoice Details', 'hola-weddings') ?></div>
            <?= sprintf('%s (#%d)', $invoice->getTitle(), $invoice->getID()); ?>
            <br><br>
            <div style="font-weight: bold;"><?= __('Balance', 'hola-weddings') ?></div>
            <table class="lined-table">
                <tbody>
                    <tr>
                        <td style="width:70%">
                            <?= __('Invoice Amount', 'hola-weddings' ) ?>
                        </td>
                        <td class="align-right" style="width:30%;">
                            <?= hw_format_price( $invoice->getAmount() ) ?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?= __('Paid Amount', 'hola-weddings' ) ?>
                        </td>
                        <td class="align-right">
                            <?= hw_format_price( $invoice->getPaidAmount() ) ?>
                        </td>
                    </tr>
                    <tr style="font-weight:bold; background-color: #eee;">
                        <td>
                            <?= __('Due Amount', 'hola-weddings' ) ?>
                        </td>
                        <td class="align-right">
                            <?= hw_format_price( $invoice->getDueAmount() ) ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </td>
    </tr>
</table>