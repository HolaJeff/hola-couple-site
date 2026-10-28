<?php
$insurance = hw_get_insurance();
$current_insurance_plan = $quote->getInsurancePlanID();
$insurance_rates = $quote->getInsuranceRates();
$toc = $quote->get('hw_toc_accepted');
$deposit = $quote->getDepositDetails();
$room_blocks = hw_get_room_blocks();
$selected_room_block = $quote->getRoomBlock();
$deposit = $quote->getDeposit();
$deposit_options = hw_get_deposit_options();
?>
<!-- Dates -->
<div class="hw-metabox">
    <div class="section">
        <h3><?= __('Dates', 'hola-weddings') ?></h3>
        <div class="row fields">
            <div class="col">
                <div class="field date-field">
                    <div class="label"><?= __('Check-in', 'hola-weddings') ?></div>
                    <div class="value input-group">
                        <input id="checkin-field" class="form-control" type="text" value="<?= $quote->getCheckin()->format('m-d-Y') ?>">
                        <span class="input-group-text"><span class="dashicons dashicons-calendar-alt"></span></span>
                        <input id="checkin-alt-field" type="hidden" name="data[hw_checkin]" value="<?= $quote->getCheckin()->format('Y-m-d') ?>" />
                    </div>
                </div>
                <div class="field date-field">
                    <div class="label"><?= __('Nights', 'hola-weddings') ?></div>
                    <div class="value">
                        <input disabled class="form-control" type="number" value="<?= $quote->getNights() ?>">
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="field date-field">
                    <div class="label"><?= __('Check-out', 'hola-weddings') ?></div>
                    <div class="value input-group">
                        <input id="checkout-field" class="form-control" type="text" value="<?= $quote->getCheckout()->format('m-d-Y') ?>">
                        <span class="input-group-text"><span class="dashicons dashicons-calendar-alt"></span></span>
                        <input id="checkout-alt-field" type="hidden" name="data[hw_checkout]" value="<?= $quote->getCheckout()->format('Y-m-d') ?>" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Occupancy -->
    <div class="section">
        <h3><?= __('Occupancy', 'hola-weddings') ?></h3>
        <div class="row fields">
            <div class="col">
                <div class="field date-field">
                    <div class="label"><?= __('Adults', 'hola-weddings') ?></div>
                    <div class="value">
                        <input class="form-control" type="text" name="data[hw_adults]" value="<?= $quote->getAdults() ?>">
                    </div>
                </div>
                <div class="field date-field">
                    <div class="label"><?= __('Teen', 'hola-weddings') ?></div>
                    <div class="value">
                        <input class="form-control" type="number" name="data[hw_teen]" value="<?= $quote->getTeen() ?>">
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="field date-field">
                    <div class="label"><?= __('Children', 'hola-weddings') ?></div>
                    <div class="value">
                        <input class="form-control" type="text" name="data[hw_children]" value="<?= $quote->getChildren() ?>">
                    </div>
                </div>
                <div class="field date-field">
                    <div class="label"><?= __('Infants', 'hola-weddings') ?></div>
                    <div class="value">
                        <input class="form-control" type="text" name="data[hw_infants]" value="<?= $quote->getInfants() ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Room Block -->
    <div class="section">
        <h3><?= __('Room Details', 'hola-weddings') ?></h3>
        <div class="row fields">
            <div class="col">
                <div class="field date-field">
                    <div class="label"><?= __('Room Block', 'hola-weddings') ?></div>
                    <div class="value">
                        <select id="room-block" name="data[hw_room_block_id]" class="form-select">
                            <?php foreach($room_blocks as $room_block): ?>
                                <option value="<?= $room_block->getID() ?>" <?= $room_block->getID() == $selected_room_block->getID() ? 'selected' : '' ?>>
                                    <?= $room_block->getTitle() ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <!--<div data-room-block-recalculate class="field date-field" style="display: none;">
                    <div class="label"><?= __('Recalculate Totals?', 'hola-weddings') ?></div>
                    <div class="value">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="recalculate-room-block" role="switch" value="1">
                        </div>
                    </div>
                </div>-->
            </div>
            <div class="col">
                <div class="field date-field">
                    <div class="label"><?= __('Room total', 'hola-weddings') ?></div>
                    <div class="value input-group">
                        <input class="form-control" type="text" disabled value="<?= $quote->getRoomTotal() ?>">
                        <span class="input-group-text"><?= hw_get_currency() ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Insurance -->
    <div class="section">
        <h3><?= __('Insurance', 'hola-weddings') ?></h3>
        <div class="row fields">
            <div class="col">
                <div class="field">
                    <div class="label"><?= __('Plan', 'hola-weddings') ?></div>
                    <div class="value">
                        <select name="data[hw_insurance_plan_id]" class="form-select">
                            <option value="0"><?= __('-- No Insurance --','hola-weddings') ?></option>
                            <?php foreach($insurance['plans'] as $plan): ?>
                                <option value="<?= $plan['id'] ?>" <?= $plan['id'] == $current_insurance_plan ? 'selected' : '' ?>>
                                    <?= $plan['name'] ?>
                                    </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <?php if($insurance_rates): ?>
                    <div class="field">
                        <div class="label"><?= __('Rates', 'hola-weddings') ?></div>
                        <div class="value">
                            <table class="wp-list-table widefat fixed striped table-view-list posts">
                                <thead>
                                    <tr>
                                        <td><?= __('Adult', 'hola-weddings') ?></td>
                                        <td><?= __('Child', 'hola-weddings') ?></td>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><?= hw_format_price( $insurance_rates['adult'] ) ?></td>
                                        <td><?= isset($insurance_rates['child']) ? hw_format_price( $insurance_rates['child'] ) : '-' ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col">
                <div class="field">
                    <div class="label"><?= __('Insurance Total', 'hola-weddings') ?></div>
                    <div class="value input-group">
                        <input class="form-control" type="text" disabled value="<?= $quote->getInsuranceTotal() ?>">
                        <span class="input-group-text"><?= hw_get_currency() ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Fees -->
    <div class="section">
        <h3><?= __('Fees', 'hola-weddings') ?></h3>
        <div class="row fields">
            <div class="col">
                <div class="field">
                    <div class="label"><?= __('Service Fee', 'hola-weddings') ?></div>
                    <div class="value input-group">
                        <input class="form-control" type="text" name="data[hw_service_fee]" value="<?= $quote->getServiceFee() ?>">
                        <span class="input-group-text"><?= hw_get_currency() ?></span>
                    </div>
                </div>
            </div>
            <div class="col"></div>
        </div>
    </div>

    <!-- Deposit -->
    <div class="section">
        <h3><?= __('Deposit', 'hola-weddings') ?></h3>
        <div class="row fields">
            <div class="col">
                <div class="field">
                    <div class="label"><?= __('Type', 'hola-weddings') ?></div>
                    <div class="value">
                        <select class="form-control" name="data[deposit][amount]">
                            <option><?= __('- Select Deposit Type -') ?></option>
                            <?php foreach($deposit_options as $option): ?>
                                <option value="<?= $option ?>" <?= $option == ($deposit['amount'] ?? '') ? 'selected' : '' ?>>
                                    <?= hw_get_deposit_label($option); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="field">
                    <div class="label"><?= __('Amount', 'hola-weddings') ?></div>
                    <div class="value input-group">
                        <input class="form-control" type="text" name="data[deposit][custom_amount]" value="<?= $deposit['custom_amount'] ?? '' ?>">
                        <span class="input-group-text"><?= hw_get_currency() ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Meta Information -->
    <div class="section">
        <h3><?= __('Other Information', 'hola-weddings') ?></h3>
        <div class="row fields">
            <div class="col">
                <div class="field">
                    <div class="label"><?= __('T&C Acceptance', 'hola-weddings') ?></div>
                    <div class="value">
                        <input class="form-control" type="text" disabled value="<?= (new \DateTime($toc))->format('l j, F Y g:m:s a') ?>"/>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="field">
                    <div class="label"><?= __('IP Address', 'hola-weddings') ?></div>
                    <div class="value">
                        <input class="form-control" type="text" disabled value="<?= $deposit['ip_address'] ?? ''; ?>"/>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>