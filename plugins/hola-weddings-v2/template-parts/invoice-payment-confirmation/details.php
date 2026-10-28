<div class="bg-light p-3">
    <table style="width: 100%;">
        <tbody>
            <tr class="border-bottom">
                <td class="text-left font-weight-bold py-2" style="width: 50%;"><?= __('Invoice', 'hola-weddings') ?></td>
                <td class="text-right" style="width: 50%;"><?= sprintf('%s (#%d)', $invoice->getTitle(), $invoice->getID()) ?></td>
            </tr>
            <tr class="border-bottom">
                <td class="text-left font-weight-bold py-2" style="width: 50%;"><?= __('Transaction Reference', 'hola-weddings') ?></td>
                <td class="text-right" style="width: 50%;"><?= $transaction->getTransactionID() ?></td>
            </tr>
            <tr class="border-bottom">
                <td class="text-left font-weight-bold py-2" style="width: 50%;"><?= __('Transaction Status', 'hola-weddings') ?></td>
                <td class="text-right" style="width: 50%;"><?= hw_get_transaction_status_label( $transaction->getStatus() ) ?></td>
            </tr>
            <tr class="border-bottom">
                <td class="text-left font-weight-bold py-2" style="width: 50%;"><?= __('Card', 'hola-weddings') ?></td>
                <td class="text-right" style="width: 50%;"><?= sprintf('XXXX-XXXX-XXXX-%s', substr($transaction->getCard()?->getNumber(), -4))  ?></td>
            </tr>
            <tr>
                <td class="text-left font-weight-bold py-2" style="width: 50%;"><?= __('Amount', 'hola-weddings') ?></td>
                <td class="text-right" style="width: 50%;"><?= hw_format_price($transaction->getAmount()) ?></td>
            </tr>
        </tbody>
    </table>
</div>
