<div class="mt-3 bg-white p-3 rounded">
    <h3 class="mt-0"><?= __('Invoice Overview', 'hola-weddings') ?></h3>
    <table class="wp-list-table widefat fixed striped table-view-list">
        <tbody>
            <tr>
                <th><?= __('Total Amount', 'hola-weddings') ?></th>
                <td class="text-end"><?= hw_format_price($invoice->getAmount(), $invoice->getCurrency()) ?></td>
            </tr>
            <tr>
                <th><?= __('Paid Amount', 'hola-weddings') ?></th>
                <td class="text-end"><?= hw_format_price($invoice->getPaidAmount(), $invoice->getCurrency()) ?></td>
            </tr>
            <tr>
                <th><?= __('Due Amount', 'hola-weddings') ?></th>
                <td class="text-end"><?= hw_format_price($invoice->getDueAmount(), $invoice->getCurrency()) ?></td>
            </tr>
        </tbody>
    </table>
    <?php if($invoice->getDueAmount() > 0): ?>
        <p class="fw-bold"><?= __('Amount to pay', 'hola-weddings') ?></p>
        <div class="form-group">
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="amount" value="full" id="pos-amount-full" checked>
                <label class="form-check-label" for="pos-amount-full">
                    <?= hw_format_price($invoice->getDueAmount(), $invoice->getCurrency()); ?>
                </label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="amount" value="custom" id="pos-amount-custom">
                <label class="form-check-label" for="pos-amount-custom">
                    <?= __('Custom Amount', 'hola-weddings') ?>
                </label>
            </div>
            <div id="pos-payment-custom-amount" class="form-group col-3" style="display: none;">
                <label class="form-label" for="pos-custom-amount-value">
                    <?= __('Insert Custom Amount', 'hola-weddings') ?>
                </label>
                <input class="form-control" type="text" name="custom_amount" value="" id="pos-custom-amount-value">
                <small><?= sprintf(__('Min 1 USD, Max Amount %s', 'hola-weddings'), hw_format_price($invoice->getDueAmount()) ) ?></small>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-success mt-3"><?= __('This invoice is fully paid.', 'hola-weddings') ?></div>
    <?php endif; ?>    
</div>