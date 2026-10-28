<?php
$checkin = !empty($idata['ci']) ? (new DateTime($idata['ci'])) : null;
$checkout = !empty($idata['co']) ? (new DateTime($idata['co'])) : null;
?>
<div id="search-form" class="card dates-selector p-3 mb-3">
            
    <h4><?=  __('Search Rooms', 'hola-weddings'); ?></h4>
    <form id="booking-search-form" class="mt-2 <?= $hotel->adults_only ? 'd-flex flex-column flex-md-row' : '' ?>" method="get">
        
        <?php if(hw_get_mode() == 'room'): ?>
            <?php if(!$hotel->adults_only): ?>
                <div class="row flex-column flex-md-row">
            <?php endif; ?>
                <div class="<?= !$hotel->adults_only ? 'col-md-6' : 'mr-2' ?> form-group">
                    <label for="checkin"><?= __('Check-in', 'hola-weddings') ?></label>
                    <input id="checkin" readonly required class="checkin form-control" type="text" placeholder="<?= __('Check-in Date', 'hola-weddings') ?>" value="" />
                    <input id="ci-alt" type="hidden" value="<?= $checkin ? $checkin->format('Y-m-d') : '' ?>" name="ci" />
                </div>
                <div class="<?= !$hotel->adults_only ? 'col-md-6' : 'mr-2' ?> form-group">
                    <label for="checkout"><?= __('Check-out', 'hola-weddings') ?></label>
                    <input id="checkout" readonly required class="checkout form-control" type="text" placeholder="<?= __('Check-out Date', 'hola-weddings') ?>" value="" />
                    <div id="checkout-Feedback" class="invalid-feedback">
                        <?= $errors['checkout'] ?? '' ?>
                    </div>
                    <input id="co-alt" type="hidden" value="<?= $checkout ? $checkout->format('Y-m-d') : '' ?>" name="co" />
                </div>
            <?php if(!$hotel->adults_only): ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if(!$hotel->adults_only): ?>
            <div class="d-flex flex-column flex-md-row">
        <?php endif; ?>
            <div class="adults mr-2 form-group">
                <label><?= __('Adults', 'hola-weddings') ?></label>
                <select id="adults-selector" name="adults" class="form-control">
                    <?php for($i = 1; $i <= hw_get_cart_max_adults(); $i++): ?>
                        <option value="<?= $i ?>" <?= $idata['adults'] == $i ? 'selected' : '' ?>><?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <?php if(hw_is_teen_enabled() && !$hotel->adults_only): ?>
                <div class="teens mr-2 form-group position-relative">
                    <label><?= __('Teens', 'hola-weddings') ?></label>
                    <select id="teens-selector" name="teens" class="form-control">
                        <?php for($i = 0; $i <= hw_get_cart_max_teen(); $i++): ?>
                            <option value="<?= $i ?>" <?= $idata['teens'] == $i ? 'selected' : '' ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                    <small class="sub-label"><?= hw_get_teen_label('') ?></small>
                </div>
            <?php endif; ?>
            <?php if(!$hotel->adults_only): ?>
                <div class="children mr-2 form-group position-relative">
                    <label><?= __('Children', 'hola-weddings') ?></label>
                    <select id="child-selector" name="children" class="form-control">
                        <?php for($i = 0; $i <= hw_get_cart_max_children(); $i++): ?>
                            <option value="<?= $i ?>" <?= $idata['children'] == $i ? 'selected' : '' ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                    <small class="sub-label"><?= hw_get_child_label('') ?></small>
                </div>
                <div class="infant mr-2 form-group position-relative">
                    <label><?= __('Infants', 'hola-weddings') ?></label>
                    <select id="infants-selector" name="infants" class="form-control">
                        <?php for($i = 0; $i <= hw_get_cart_max_children(); $i++): ?>
                            <option value="<?= $i ?>" <?= $idata['infants'] == $i ? 'selected' : '' ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                    <small class="sub-label"><?= hw_get_infant_label('') ?></small>
                </div>
            <?php endif; ?>
            <div class="form-group d-flex flex-column justify-content-end ml-auto">
                <button type="submit" class="btn btn-primary"><?= __('Submit', 'hola-weddings') ?></button>
            </div>
        <?php if(!$hotel->adults_only): ?>
            </div>
        <?php endif; ?>
    </form>
    <?php if(hw_get_min_nights() > 1): ?>
        <small><?= sprintf(__('A minimum of %s nights is required.', 'hola-weddings'), hw_get_min_nights()) ?></small>
    <?php endif; ?>
</div>