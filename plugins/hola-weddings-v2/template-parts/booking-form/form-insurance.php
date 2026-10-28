<!-- Insurance -->
<?php

$insurance = hw_get_insurance();
$selected_plan = $data['insurance_plan_id'] ?? '';

if(!is_wp_error( $insurance ) && !empty($insurance['plans'])): ?>
    <div id="insurance-section" class="mt-3 card p-3">
        <h4><?= __('Insurance', 'hola-weddings') ?></h4>
        <p><?= $insurance['description'] ?></p>
        <?php if(!empty($insurance['main_image'])): ?>
            <img src="<?= $insurance['main_image'] ?>" />
        <?php endif; ?>
        
        <div class="form-group">
            <label for="insurance-plan"><?= __('Choose Plan', 'hola-weddings') ?></label>
            <select id="insurance-plan" name="insurance_plan" class="form-control" data-mode="<?= $insurance['mode'] ?>" data-plans="<?= htmlspecialchars( json_encode($insurance['plans']) , ENT_QUOTES, 'UTF-8'); ?>">
                <option value="0"><?= __('No, I do not need an insurance plan.','hola-weddings') ?></option>
                <?php foreach($insurance['plans'] as $plan): ?>
                    <option value="<?= $plan['id'] ?>" <?= $plan['id'] == $selected_plan ? 'selected' : '' ?>>
                        <?= $plan['name'] ?>
                     </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="alert alert-warning d-flex align-items-center" role="alert">
            <i class="fas fa-exclamation-triangle"></i>
            <div class="pl-3">
                <?= __('NOTE: Without insurance the trip is 100% non refundable after a certain date.', 'hola-weddings') ?>
            </div>
        </div>

        <div class="alert alert-warning d-flex align-items-center" role="alert">
            <i class="fas fa-exclamation-triangle"></i>
            <div class="pl-3">
                <?= __('Please note that the cost of the insurance policy is non-refundable.', 'hola-weddings') ?>
            </div>
        </div>
    </div>
    <!-- Modal -->
    <?php if(isset($insurance['image'])): ?>
        <div class="modal fade" id="insuranceModal" tabindex="-1" aria-labelledby="insuranceModal" aria-hidden="true">
            <div class="modal-dialog modal-dialog-scrollable modal-lg">
                <div class="modal-content">
                    <div class="modal-body">
                        <img src="<?= $insurance['main_image'] ?>" />
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>