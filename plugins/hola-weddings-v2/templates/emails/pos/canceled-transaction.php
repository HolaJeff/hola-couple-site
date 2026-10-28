<p>Hello {{username}}</p>

<p>We regret to inform you that your recent payment attempt for invoice <strong>{{invoice_title}} (#{{invoice_id}})</strong>was not successful and has been canceled.</p>

<p>This may have occurred due to one of the following reasons:</p>

<ul>
    <li>The bank declined or blocked the transaction</li>
    <li>Insufficient funds or card restrictions</li>
    <li>A temporary issue with the payment system</li>
</ul>

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

<p>We recommend reaching out to your card issuer for more information. You may also try submitting the payment again or contact us if you need help completing the transaction.</p>

<p>Thank you for your attention, and we apologize for the inconvenience.</p>

<p>Warm regards,</p>
<p>The Hola Weddings Team</p>