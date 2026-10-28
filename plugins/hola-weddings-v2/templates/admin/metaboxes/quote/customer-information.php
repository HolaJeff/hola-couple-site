<?php

use HW\Helpers\Countries;

$billing_information = $quote->getBillingInformation();
$current_country = $billing_information->getCountry();
$state = $billing_information->getState();
$is_US = $current_country == 'US';
?>
<div class="row fields">
    <div class="col">
        <h3><?= __('Details') ?></h3>
        <div class="field">
            <div class="label"><?= __('First Name') ?></div>
            <div class="value">
                <input class="form-control" type="text" name="data[billing][first_name]" value="<?= $billing_information->getFirstName() ?>">
            </div>
        </div>
        <div class="field">
            <div class="label"><?= __('Last Name') ?></div>
            <div class="value">
                <input class="form-control" type="text" name="data[billing][last_name]" value="<?= $billing_information->getLastName() ?>">
            </div>
        </div>
        <div class="field">
            <div class="label"><?= __('Email') ?></div>
            <div class="value">
                <?php $email = $billing_information->getEmail() ?>
                <div class="input-group">
                    <input class="form-control" type="text" name="data[billing][email]" value="<?= $email ?>">
                    <a href="mailto: <?= $email ?>" class="btn btn-outline-primary" ><?= __('Send Email', 'hola-weddings') ?></a>
                </div>
            </div>
        </div>
        <div class="field">
            <div class="label"><?= __('Phone') ?></div>
            <div class="value">
                <?php $phone = $billing_information->getPhone(); ?>
                <input class="form-control" type="text" name="data[billing][phone_number]" value="<?= $phone ?>">
            </div>
        </div>
    </div>
    <div class="col">
        <h3><?= __('Billing Information') ?></h3>
        <div class="field">
            <div class="label"><?= __('Address') ?></div>
            <div class="value">
                <input class="form-control" type="text" name="data[billing][address]" value="<?= $billing_information->getAddress() ?>">
            </div>
        </div>
        <div class="field">
            <div class="label"><?= __('City') ?></div>
            <div class="value">
                <input class="form-control" type="text" name="data[billing][city]" value="<?= $billing_information->getCity() ?>">
            </div>
        </div>
        <div class="field">
            <div class="label"><?= __('Postal Code') ?></div>
            <div class="value">
                <input class="form-control" type="text" name="data[billing][zip]" value="<?= $billing_information->getPostalCode() ?>">
            </div>
        </div>
        <div class="field">
            <div class="label"><?= __('Country') ?></div>
            <div class="value">
                <select data-target="#state-field" id="country" type="text" class="form-select" placeholder="<?= __('Country', 'hola-weddings') ?>" name="data[billing][country]" >
                    <optgroup label="<?= __('Most Used', 'hola-weddings') ?>">
                        <option value="US" <?= $data['customer']['country'] == 'US' ? 'selected' : '' ?>><?= __('United States', 'hola-weddings') ?></option>
                    </optgroup>
                    <optgroup label="<?= __('All Countries', 'hola-weddings') ?>">
                        <?php foreach(Countries::getList() as $country):
                            // skip us
                            if($is_US){
                                continue;
                            }
                            ?>
                            <option value="<?= $country['code'] ?>" <?= $current_country == $country['code'] ? 'selected' : '' ?>><?= $country['name'] ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                </select>
            </div>
        </div>
        <div id="state-field" class="field">
            <div class="label"><?= __('State/Province') ?></div>
            <div class="value form-group">
                <select type="text" class="us-states form-select" name="data[billing][state]" <?= !$is_US ? 'style="display:none" disabled' : '' ?>>
                    <option value="0"><?= __('- Select State -') ?></option>
                    <?php foreach(Countries::getUSStates() as $code => $name): ?>
                        <option value="<?= $code ?>" <?= $is_US && $state == $code ? 'selected' : '' ?>><?= $name ?></option>
                    <?php endforeach; ?>
                </select>
                <input <?= $is_US ? 'style="display:none" disabled' : '' ?> autocomplete="off" type="text" class="other-state form-control" placeholder="<?= __('State / Province', 'hola-weddings') ?>" name="data[billing][state]" value="<?= !$is_US ? $state : '' ?>" />
            </div>
        </div>
    </div>
</div>