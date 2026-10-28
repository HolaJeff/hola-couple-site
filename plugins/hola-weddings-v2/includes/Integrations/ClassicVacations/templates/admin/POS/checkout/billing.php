
<div id="billing-form" class="mt-3 bg-white p-3 rounded" style="display: none;">
    <h3><?= __('Billing Information', 'hola-weddings') ?></h3>

    <button id="pos-load-billing-data" type="button" class="btn btn-link p-0 text-small mb-3" style="font-size: 0.9em">
        <?= __('Fill fields with billing information from the quote', 'hola-weddings') ?>
    </button>

    <?= hw_cv_billing_template() ?>

    <button id="clear-billing-fields" type="button" class="btn btn-link p-0 text-small" style="font-size: 0.9em">
        <?= __('Clear data', 'hola-weddings') ?>
    </button>
</div>