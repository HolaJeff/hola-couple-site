<?php
$quote = $invoice->getQuote();
$billing_information = $quote->getBillingInformation();
?>
<div id="billing-form" class="mt-3 bg-white p-3 p-md-5 rounded">
    <p class="mt-0 text-uppercase font-weight-bold"><?= __('Billing Information', 'hola-weddings') ?></p>  

    
    <button id="pos-load-billing-data" type="button" class="btn btn-link p-0 text-small mb-3" style="font-size: 0.9em">
        <i class="fas fa-pen mr-1"></i>
        <?= __('Use billing information saved with reservation', 'hola-weddings') ?>
    </button>

    <?= hw_get_template('template-parts/partials/forms/billing-form', ['billing_information' => $billing_information, 'readonly' => false]) ?>

    <button id="clear-billing-fields" type="button" class="btn btn-link p-0 text-small" style="font-size: 0.9em">
        <?= __('Clear Fields', 'hola-weddings') ?>
    </button>
</div>