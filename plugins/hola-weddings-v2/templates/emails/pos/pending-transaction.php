<p>Hello {{username}}</p>

<p>Thank you for your recent payment attempt for invoice <strong>{{invoice_title}} (#{{invoice_id}})</strong>. We wanted to let you know that the transaction has been received and is currently under review for verification.</p>

<p>You will receive a follow-up email as soon as the payment is either approved or declined.</p>

<div  style="background-color:#fafafa; padding: 20px;">
    <table style="width: 100%;">
        <tbody>
            <tr class="border-bottom">
                <td class="text-left font-weight-bold py-2" style="width: 50%;"><?= __('Transaction Reference', 'hola-weddings') ?></td>
                <td class="text-right" style="width: 50%;">#{{transaction_id}}</td>
            </tr>
            <tr class="border-bottom">
                <td class="text-left font-weight-bold py-2" style="width: 50%;"><?= __('Card', 'hola-weddings') ?></td>
                <td class="text-right" style="width: 50%;">XXXX-XXXX-XXXX-{{card_digits}}</td>
            </tr>
            <tr>
                <td class="text-left font-weight-bold py-2" style="width: 50%;"><?= __('Amount', 'hola-weddings') ?></td>
                <td class="text-right" style="width: 50%;">{{amount}}</td>
            </tr>
        </tbody>
    </table>
</div>

<p>If you have any questions or need assistance in the meantime, feel free to contact us.</p>

<p>Warm regards,</p>
<p>The Hola Weddings Team</p>