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

        <?php $decline_text = hw_get_insurance_decline_text(); if($decline_text !== ''): ?>
        <div id="insurance-decline" class="form-check form-group" <?= $selected_plan ? 'style="display:none;"' : '' ?>>
            <input class="form-check-input <?= isset($errors['insurance_decline']) ? 'is-invalid' : '' ?>" type="checkbox" id="insuranceDecline"
                   name="insurance_decline" value="1" <?= !empty($data['insurance_decline']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="insuranceDecline"><?= esc_html($decline_text) ?></label>
            <div class="invalid-feedback"><?= __('Please choose a plan, or confirm that you decline travel protection.', 'hola-weddings') ?></div>
        </div>
        <script>
        (function(){
            var sel = document.getElementById('insurance-plan'), box = document.getElementById('insurance-decline');
            if(!sel || !box){ return; }
            function sync(){
                var none = !parseInt(sel.value, 10);
                box.style.display = none ? '' : 'none';
                if(!none){ document.getElementById('insuranceDecline').checked = false; }
            }
            sel.addEventListener('change', sync); sync();
        })();
        </script>
        <?php endif; ?>

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