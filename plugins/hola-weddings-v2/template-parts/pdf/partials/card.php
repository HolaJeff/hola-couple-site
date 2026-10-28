<?php 
$card = hw_get_card_information($id);
$signature = hw_get_quote_signature($id);

if($card): ?>
    <h6 class="uppercase"><?= __('Card Information', 'hola-weddings') ?></h6>
    <table id="card-table" class="box">
        <tr>
            <td style="width: 60%;">
                <strong><?= hw_get_card_type_label($card['card-type']) ?></strong>
                <br>
                <?= sprintf('XXXX-XXXX-XXXX-%s', substr($card['card-number'], 12, 4)) ?>
            </td>
            <td style="width: 40%;">
                <strong><?= __('Card Holder Name', 'hola-weddings'); ?></strong>
                <br>
                <?= $card['card-owner'] ?>
            </td>
        </tr>
    </table>
    <p style="margin-top: 30px;">
        <?= __('You certify that you are the cardholder and are authorizing the travel agency or its chosen Tour Operator/Supplier to charge the listed amount to the credit card. You certify that you have verified that all information contained in the confirmation you received is accurate. You also certify you have read the Terms & Conditions and the appropriate Travel Protection Plan details. Cancellation penalties may apply. Insurance is not refundable.
            <br><br>This charge will be manually applied by the agency to your reservation.  If there are any issues, an agent will get back to you. Please note that you may not see a charge from the travel agency on your credit card statement; the charge will come from our supplier directly. Payment may take 3-5 business days to fully process and be reflected on your statement.
            <br><br>I authorize Hola Weddings to use the above information to charge my credit card the stated amount. Completion of this form and the initialing of this box signifies acceptance in lieu of my signature. By checking the box below and adding your signature, you indicate that the above amount is correct, you authorize the travel agency to charge your credit card for the specified amount, and you agree to the terms and conditions.', 'hola-weddings') ?>
    </p>
    <?php if(!empty($signature)): ?>
        <div class="signature" style="display: block;">
            <div style="text-align: center;">
                <img src="<?= $signature ?>" />
            </div>
            <div style="text-align: center;">
                <p style="font-size: 11px"><?= sprintf(__('Signature: %s %s', 'hola-weddings'), $customer['first_name'], $customer['last_name'] ) ?></p>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>