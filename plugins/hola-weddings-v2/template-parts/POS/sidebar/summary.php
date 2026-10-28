<div class="mt-3 bg-white p-3 rounded">
    <p class="mt-0 text-uppercase font-weight-bold"><?= __('summary', 'hola-weddings') ?></p>  
    <table class="table">
        <tbody>
            <tr>
                <td><?= __('Total Amount', 'hola-weddings') ?></td>
                <td class="text-right"><?= hw_format_price($invoice->getAmount(), $invoice->getCurrency()) ?></td>
            </tr>
            <tr>
                <td><?= __('Paid Amount', 'hola-weddings') ?></td>
                <td class="text-right"><?= hw_format_price($invoice->getPaidAmount(), $invoice->getCurrency()) ?></td>
            </tr>
            <tr>
                <th><?= __('Due Amount', 'hola-weddings') ?></th>
                <td class="text-right font-weight-bold"><?= hw_format_price($invoice->getDueAmount(), $invoice->getCurrency()) ?></td>
            </tr>
        </tbody>
    </table>
</div>