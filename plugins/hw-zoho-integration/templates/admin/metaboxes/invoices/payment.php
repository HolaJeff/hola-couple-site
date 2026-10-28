<div class="field">
    <div class="label mb-2"><?= __('Zoho Payment ID') ?></div>
    <div class="value">
        <div class="input-group">
            <input class="form-control" type="text" name="zoho_payment_id" value="<?= $payment_id ?>">
            <?php if(!empty($payment_id)): ?>
                <a href="<?= hwzi_get_zoho_payment_url($payment_id) ?>" class="btn btn-outline-primary" title="Go to Zoho" target="_blank" >
                    <span class="dashicons dashicons-external"></span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php // don't allow to create a payment while no reservation id is set
if(!empty($quote_id) && empty($payment_id)): ?> 
    <p class="my-3">If a payment doesn't exist on Zoho use the bottom below to create a new one and link it to this invoice</p>
    <button class="btn btn-zoho w-100 btn-has-spinner" type="button" data-create-payment data-post-id="<?= $post->ID ?>">
        <div class="spinner-border text-light spinner-border-sm align-middle" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <span class="dashicons dashicons-plus align-middle"></span>
        <span class="text"><?= __('Export to Zoho', 'hola-weddings') ?></span>
    </button>
<?php else: ?>
    <div class="form-check form-switch mt-3 ps-0">
        <label class="form-check-label d-block mb-1" for="hwziUpdatePayment">Update on Zoho</label>
        <input class="form-check-input float-none ms-0" name="hwzi_update_on_zoho" value="1" type="checkbox" role="switch" id="hwziUpdatePayment">
        
    </div>
<?php endif; ?>