<?php

$cancellation = hw_get_cancellation_policy();
$terms = hw_get_terms();

if(!empty($cancellation)): ?>
    <h4 class="mb-3"><?= __('Cancellation Policy', 'hola-weddings') ?></h4>
    <div class="terms-box">
        <?= $cancellation ?>
    </div>
<?php endif; ?>

<?php if(!empty($terms)): ?>
    <h4 class="mb-3"><?= __('Payment Policy', 'hola-weddings') ?></h4>
    <div class="terms-box">
        <?= $terms ?>
    </div>
<?php endif; ?>


<p id="submit-acceptance" class="alert alert-warning">
    <?= __('By submitting, you certify that you have verified that all information contained in the confirmation you received is accurate. You also certify you have read the Terms & Conditions and the appropriate Travel Protection Plan details. Cancellation penalties may apply. <span class="insurance-sentence">Insurance is not refundable.</span>', 'hola-weddings') ?>
</p>

<div class="form-check form-group">
    <input class="form-check-input <?= isset($errors['toc']) ? 'is-invalid' : '' ?>" type="checkbox" id="tcCheck" name="terms_and_conditions" value="1" >
    <label class="form-check-label" for="tcCheck">
        <?= __('I Accept the <a href="#" data-toggle="modal" data-target="#termsConditionsModal"><u>Terms & Conditions</u></a>', 'hola-weddings')  ?>
    </label>
    <div class="invalid-feedback">
        <?= __('Please, read and accept the terms and conditions.', 'hola-weddings') ?>
    </div>
</div>

<?php foreach(hw_get_extra_checkboxes() as $cb): // configurable per-site checkboxes (hw_extra_checkboxes option) ?>
<div class="form-check form-group">
    <input class="form-check-input <?= isset($errors['extra_cb_' . $cb['index']]) ? 'is-invalid' : '' ?>" type="checkbox"
           id="extraCb<?= $cb['index'] ?>" name="extra_checkboxes[<?= $cb['index'] ?>]" value="1"
           <?= !empty($data['extra_checkboxes'][$cb['index']]) ? 'checked' : '' ?>>
    <label class="form-check-label" for="extraCb<?= $cb['index'] ?>">
        <?= esc_html($cb['label']) ?>
    </label>
    <div class="invalid-feedback">
        <?= __('This confirmation is required.', 'hola-weddings') ?>
    </div>
</div>
<?php endforeach; ?>

 <!-- Payment -->
 <?= hw_get_template('template-parts/booking-form/form-signature', ['errors' => $errors, 'data' => $data]) ?>