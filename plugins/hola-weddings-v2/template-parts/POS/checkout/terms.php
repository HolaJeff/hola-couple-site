<?php

$cancellation = hw_get_cancellation_policy();
$terms = hw_get_terms();

if(!empty($cancellation)): ?>
    <div id="cancellation-policy" class="mt-3 bg-white p-3 p-md-5 rounded">
        <p class="mt-0 text-uppercase font-weight-bold"><?= __('Cancellation Policy', 'hola-weddings') ?></p>
        <div class="terms-box mb-0">
            <?= $cancellation ?>
        </div>
    </div>
<?php endif; ?>

<?php if(!empty($terms)): ?>
    <div id="payment-policy" class="mt-3 bg-white p-3 p-md-5 rounded">
        <p class="mt-0 text-uppercase font-weight-bold"><?= __('Payment Policy', 'hola-weddings') ?></p>
        <div class="terms-box mb-0">
            <?= $terms ?>
        </div>
    </div>
<?php endif; ?>

<div class="mb-2 mt-3 alert alert-warning form-group">
    <p class="mt-0 text-uppercase font-weight-bold">
        <?= hw_get_svg_icon('alert') ?>
        <span class="align-middle ml-2"><?= __('Signature', 'hola-weddings') ?></span>
    </p>
    <p><?= __('Please add your electronic signature in order to complete the payment.', 'hola-weddings') ?></p>
    <div id="signature">
        <div data-placeholder class="mb-3 signature-placeholder" style="display: none"></div>
        <input data-hidden-input type="hidden" name="signature" value="" />
        <button data-trigger type="button" class="btn btn-primary">
            <i class="fas fa-pen-fancy mr-1"></i>
            <?= __('Add Signature', 'hola-weddings'); ?>
        </button>
    </div>
</div>

<p class="mt-3">
    <?= __('By submitting, you certify that you have verified that all information contained in the confirmation you received is accurate. You also certify you have read the Terms & Conditions and the appropriate Travel Protection Plan details. Cancellation penalties may apply. Insurance is not refundable.', 'hola-weddings') ?>
</p>

<div class="form-check form-group">
    <input class="form-check-input" type="checkbox" id="terms_and_conditions" name="terms_and_conditions" value="1">
    <label class="form-check-label" for="terms_and_conditions">
        <?= __('I Accept the <a href="#" data-toggle="modal" data-target="#termsConditionsModal">Terms & Conditions</a>, <a href="#cancellation-policy">Cancellation Policy</a> and <a href="#payment-policy">Payment Policy</a>', 'hola-weddings') ?>
    </label>
</div>