<?php
$quote = hw_get_quote($id);
$paid_amount = $quote->getPaidAmount();
$quote_total = hw_get_quote_total($id);
?>
<h3><?= __('Payment Information', 'hola-weddings') ?></h3>
<h6 class="uppercase"><?= __('Balance', 'hola-weddings') ?></h6>
<table id="payment-table" class="box lined-table" style="width: 100%">
    <tbody>
        <tr>
            <td style="width:70%">
                <?= __('Room Total', 'hola-weddings' ) ?>
            </td>
            <td class="align-right" style="width:30%;">
                <?= hw_format_price( hw_get_quote_room_total($id) ) ?>
            </td>
        </tr>
        <tr>
            <td>
                <?= __('Insurance Total', 'hola-weddings' ) ?>
            </td>
            <td class="align-right">
                <?= hw_format_price( hw_get_quote_insurance_total($id) ) ?>
            </td>
        </tr>
        <?php if($transfer = hw_get_quote_transfer_fee($id)): ?>
            <tr class="transfer-row">
                <td>
                    <?= __('Transfer Fee', 'hola-weddings' ) ?>
                </td>
                <td class="align-right">
                    <?= hw_format_price( $transfer ) ?>
                </td>
            </tr>
        <?php endif; ?>
        <tr style="font-weight:bold; background-color: #eee;">
            <td>
                <?= __('Total', 'hola-weddings' ) ?>
            </td>
            <td class="align-right">
                <?= hw_format_price( $quote_total ) ?>
            </td>
        </tr>
        <tr>
            <td>
                <?= __('Paid Amount', 'hola-weddings' ) ?>
            </td>
            <td class="align-right">
                <?= hw_format_price( $paid_amount ); ?>
            </td>
        </tr>
        <tr>
            <td style="border-bottom: 0;">
                <?= __('Due Amount', 'hola-weddings' ) ?>
            </td>
            <td class="align-right" style="border-bottom: 0;">
                <?= hw_format_price( $quote_total - $paid_amount ); ?>
            </td>
        </tr>
    </tbody>
</table>