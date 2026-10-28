<?php

use HWZoho\API\CRM\Modules\Potentials;

$hotels = [ '' => '- Select Hotel -' ] + hw_api_get_hotel_list();
$website = explode('.', $potential->getKeyValue('Wedding_Page_URL'))[0];

$contact_name = '';
if($contact_name = $potential->getKeyValue('Contact_Name')){
    $contact_name = $contact_name->getKeyValue('name'); // this and previous line were split into 2 lines becuase server is still 7.4 and don't accept ?-> opetator
}
$spouse_name = '';
if($spouse_name = $potential->getKeyValue('Spouse_Name')){
    $spouse_name = $spouse_name->getKeyValue('name'); // this and previous line were split into 2 lines becuase server is still 7.4 and don't accept ?-> opetator
}
$date = $potential->getKeyValue('Wedding_Date') ? new DateTime($potential->getKeyValue('Wedding_Date')) : null;
$cut_off_date = $potential->getKeyValue('Cut_Off_Date') ? new DateTime($potential->getKeyValue('Cut_Off_Date')) : '';
$start_date = (clone $date)->modify('-5 days');
$end_date = (clone $date)->modify('+5 days');
$room_blocks = Potentials::getRelatedRecords( $potential->getId(), 'Room_Blocks');

ob_start();
hwzi_get_template('admin/pages/import-potential/import/deposit-payment', ['potential' => $potential]);
$deposit_and_payment = ob_get_clean();

ob_start();
hwzi_get_template('admin/pages/import-potential/import/cancellation-policy', ['potential' => $potential]);
$cancellation_policy = ob_get_clean();
?>
<div class="p-3 bg-white rounded mt-3">
    <h2><?= __('Review Information', 'hw-zoho') ?></h2>
    <form id="import-potential-form" action="<?= network_admin_url('admin.php?page=zoho-import-potential'); ?>" method="post">
        <input type="hidden" name="action" value="hwzi_import_wedding" />
        <input type="hidden" name="hwzi_import[potential_id]" value="<?= $id ?>" />
        <div class="row">
            <!-- Wedding Name -->
            <div class="col-lg-3">
                <label for="deal-name" class="form-label"><?= __('Wedding Name', 'hw-zoho') ?></label>
                <input type="text" value="<?= $potential->getKeyValue('Deal_Name') ?>" name="hwzi_import[wedding_name]" class="form-control" id="deal-name" placeholder="<?= __('Wedding Name', 'hw-zoho') ?>">
            </div>
            <!-- subdomain -->
            <div class="col-lg-3">
                <label for="subdomain" class="form-label"><?= __('Subdomain', 'hw-zoho') ?></label>
                <div class="input-group mb-3">
                    <input type="text" name="hwzi_import[subdomain]" class="form-control" value="<?= $website ?>" placeholder="<?= __('Subdomain', 'hw-zoho') ?>">
                    <span class="input-group-text"  id="subdomain"><?= __('.hola-weddings.com', 'hw-zoho') ?></span>
                </div>
            </div>
            <!-- Contact Name -->
            <div class="col-lg-3">
                <label for="contact-name" class="form-label"><?= __('Contact Name', 'hw-zoho') ?></label>
                <input type="text" name="hwzi_import[contact_name]" value="<?= $contact_name ?>" class="form-control" id="contact-name" placeholder="<?= __('Contact Name', 'hw-zoho') ?>">
            </div>
            <!-- Spouse Name -->
            <div class="col-lg-3">
                <label for="spouse-name" class="form-label"><?= __('Spouse Name', 'hw-zoho') ?></label>
                <input type="text" name="hwzi_import[spouse_name]" value="<?= $spouse_name ?>" class="form-control" id="spouse-name" placeholder="<?= __('Spouse Name', 'hw-zoho') ?>">
            </div>
        </div>
        <h3><?= __('Hotel', 'hw-zoho') ?></h3>
        <div class="row">
            <!-- Wedding Date -->
            <div class="col-lg-3">
                <select class="form-select" name="hwzi_import[hotel_id]">
                    <?php foreach($hotels as $id => $hotel): ?>
                        <option value="<?= $id ?>"><?= $hotel ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <h3><?= __('Dates', 'hw-zoho') ?></h3>
        <div class="row">
            <!-- Wedding Date -->
            <div class="col-lg-3">
                <label for="wedding-date" class="form-label"><?= __('Wedding Date', 'hw-zoho') ?></label>
                <input id="wedding-date" type="text"  value="<?= $date ? $date->format('m-d-Y') : '' ?>" class="form-control datepicker" placeholder="<?= __('Wedding Date', 'hw-zoho') ?>">
                <input id="wedding-date-alt" type="hidden" value="<?= $date ? $date->format('Y-m-d') : '' ?>" name="hwzi_import[wedding_date]" />
            </div>
            <!-- Start Booking Date -->
            <div class="col-lg-3">
                <label for="start-date" class="form-label"><?= __('Start Booking Date', 'hw-zoho') ?></label>
                <input id="start-date" type="text" value="<?= $start_date ? $start_date->format('m-d-Y') : '' ?>" class="form-control datepicker" placeholder="<?= __('Start Booking Date', 'hw-zoho') ?>">
                <input id="start-date-alt" type="hidden" value="<?= $start_date ? $start_date->format('Y-m-d') : '' ?>" name="hwzi_import[start_date]" />
            </div>
            <!-- End Booking Date -->
            <div class="col-lg-3">
                <label for="end-date" class="form-label"><?= __('End Booking Date', 'hw-zoho') ?></label>
                <input id="end-date" type="text" value="<?= $end_date ? $end_date->format('m-d-Y') : '' ?>" class="form-control datepicker" placeholder="<?= __('End Booking Date', 'hw-zoho') ?>">
                <input id="end-date-alt" type="hidden" value="<?= $end_date ? $end_date->format('Y-m-d') : '' ?>" name="hwzi_import[end_date]" />
            </div>
            <!-- Cut-Off Date -->
            <div class="col-lg-3 mt-2">
                <label for="cutoff-date" class="form-label"><?= __('Cut-Off Date', 'hw-zoho') ?></label>
                <input id="cutoff-date" type="text" value="<?= $cut_off_date ? $cut_off_date->format('m-d-Y') : '' ?>" class="form-control datepicker" placeholder="<?= __('Cut-Off Date', 'hw-zoho') ?>">
                <input id="cutoff-date-alt" type="hidden" value="<?= $cut_off_date ? $cut_off_date->format('Y-m-d') : '' ?>" name="hwzi_import[cut_off_date]" />
            </div>
        </div>
        <h3><?= __('Costs', 'hw-zoho') ?></h3>
        <div class="row mt-3">
            <!-- Enable transfer -->
            <div class="col-3">
                <label for="enable-transfer" class="form-label"><?= __('Enable Transfer', 'hw-zoho') ?></label>
                <select id="enable-transfer" class="form-select" name="hwzi_import[has_transfer]">
                    <option value="0"><?= __('No', 'hw-zoho') ?></option>
                    <option value="1" <?= intval($potential->getKeyValue('Transfer_Cost')) > 0 ? 'selected' : '' ?>>
                        <?= __('Yes', 'hw-zoho') ?>
                    </option>
                </select>            
            </div>
            <!-- Transfer type -->
            <div class="col-3">
                <label for="transfer-multiplier" class="form-label"><?= __('Service Fee', 'hw-zoho') ?></label>
                <select id="transfer-multiplier" class="form-select" name="hwzi_import[transfer_multiplier]">
                    <option value="room"><?= __('Per Room', 'hw-zoho') ?></option>
                    <option value="person"><?= __('Per Person', 'hw-zoho') ?></option>
                </select>            
            </div>
            <!-- Transfer Fee -->
            <div class="col-3">
                <label for="transfer-fee" class="form-label"><?= __('Service Fee', 'hw-zoho') ?></label>
                <input type="number" name="hwzi_import[transfer_fee]" value="<?= $potential->getKeyValue('Transfer_Cost') ?>" class="form-control" id="transfer-fee" placeholder="<?= __('Transfer Fee', 'hw-zoho') ?>">
            </div>
        </div>
        
        <h3><?= __('Deposit & Cancellation Policy', 'hw-zoho') ?></h3>
        <!-- Deposit & Payment Plan -->
        <div class="mt-3">
            <label for="deposit-payment-textarea" class="form-label"><?= __('Deposit & Payment Plan', 'hw-zoho') ?></label>
            <?php
            wp_editor(
                $deposit_and_payment,
                'terms_conditions',
                array(
                    'textarea_name' => 'hwzi_import[terms_conditions]',
                    '_content_editor_dfw' => false,
                    'drag_drop_upload'    => true,
                    'tabfocus_elements'   => 'content-html,save-post',
                    'editor_height'       => 300,
                    'tinymce'             => array(
                        'resize'                  => false,
                        'wp_autoresize_on'        => true,
                        'add_unload_trigger'      => false,
                    ),
                )
            );
            ?>
        </div>
        <!-- Cancellation Policy -->
        <div class="mt-3">
            <label for="cancellation-policy-textarea" class="form-label"><?= __('Cancellation Policy', 'hw-zoho') ?></label>
            <?php
            wp_editor(
                $cancellation_policy,
                'cancellation_policy',
                array(
                    'textarea_name' => 'hwzi_import[cancellation_policy]',
                    '_content_editor_dfw' => false,
                    'drag_drop_upload'    => true,
                    'tabfocus_elements'   => 'content-html,save-post',
                    'editor_height'       => 300,
                    'tinymce'             => array(
                        'resize'                  => false,
                        'wp_autoresize_on'        => true,
                        'add_unload_trigger'      => false,
                    ),
                )
            );
            ?>
        </div>
        <h3><?= __('Room Blocks', 'hw-zoho') ?></h3>
        <?php if(!empty($room_blocks)): ?>
            <div class="room-blocks">
                <?php foreach($room_blocks as $record): ?>
                    <div class="mb-2">
                        <input id="room-block-<?= $record->getId() ?>" type="checkbox" name="hwzi_import[room_blocks][]" value="<?= $record->getId() ?>" checked />
                        <label for="room-block-<?= $record->getId() ?>"><?= $record->getName() ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p><?= __('No room blocks found.') ?></p>
        <?php endif; ?>
        <button id="import-potential-btn" type="button" class="btn btn-primary mt-3"><?= __('Import', 'hw-zoho') ?></button>
        <?php wp_nonce_field( 'hwzi-import-wedding' ); ?>
    </form>
</div>