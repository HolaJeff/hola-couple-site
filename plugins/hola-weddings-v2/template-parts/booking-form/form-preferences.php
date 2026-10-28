<?php
/**
 * Guest choices that are switched on per site (Settings > Labels & texts):
 * a bed preference (a request, never a guarantee) and optional airport transfers.
 */
$beds = hw_get_bed_preferences();
$optional_transfer = hw_is_transfer_optional();

if(empty($beds) && !$optional_transfer){
    return;
}
$chosen_bed = $data['bed_preference'] ?? '';
$wants_transfer = !isset($data['transfer_opt_in']) || !empty($data['transfer_opt_in']);
?>
<div id="guest-preferences" class="mt-3 card p-3">
    <h4><?= __('Your preferences', 'hola-weddings') ?></h4>

    <?php if(!empty($beds)): ?>
    <div class="form-group">
        <label for="bed-preference"><?= __('Bed preference', 'hola-weddings') ?></label>
        <select id="bed-preference" name="bed_preference" class="form-control">
            <option value=""><?= __('No preference', 'hola-weddings') ?></option>
            <?php foreach($beds as $bed): ?>
                <option value="<?= esc_attr($bed) ?>" <?= $bed === $chosen_bed ? 'selected' : '' ?>><?= esc_html($bed) ?></option>
            <?php endforeach; ?>
        </select>
        <small class="form-text text-muted"><?= __('We pass your preference to the resort as a request. Bed types are assigned at check-in and cannot be guaranteed.', 'hola-weddings') ?></small>
    </div>
    <?php endif; ?>

    <?php if($optional_transfer): ?>
    <div class="form-check form-group mb-0">
        <input type="hidden" name="transfer_opt_in" value="0">
        <input class="form-check-input" type="checkbox" id="transferOptIn" name="transfer_opt_in" value="1" <?= $wants_transfer ? 'checked' : '' ?>>
        <label class="form-check-label" for="transferOptIn"><?= __('Add round-trip airport transfers to my booking', 'hola-weddings') ?></label>
        <small class="form-text text-muted"><?= __('Untick this if you are arranging your own way to the resort. The price in your summary updates.', 'hola-weddings') ?></small>
    </div>
    <?php endif; ?>
</div>
