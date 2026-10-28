<?php

use HW\Helpers\Countries;
?>
<div class="row">
    <div class="form-group col-5 mb-2">
        <label for="first_name" class="form-label"><?= __('First Name', 'hola-weddings') ?></label>
        <input type="text" name="billing[first_name]" value="<?= $billing_information ? $billing_information->getFirstName() : '' ?>" class="form-control d-inline-block" id="first_name" placeholder="<?= __('First Name', 'hola-weddings') ?>">
    </div>
    <div class="form-group col-5 mb-2">
        <label for="last_name" class="form-label"><?= __('Last Name', 'hola-weddings') ?></label>
        <input type="text" name="billing[last_name]" value="<?= $billing_information ? $billing_information->getLastName() :'' ?>" class="form-control d-inline-block" id="last_name" placeholder="<?= __('Last Name', 'hola-weddings') ?>">
    </div>
</div>
<div class="row">
    <div class="form-group col-10 mb-2">
        <label for="email" class="form-label"><?= __('Email', 'hola-weddings') ?></label>
        <input type="text" name="billing[email]" value="<?= $billing_information ? $billing_information->getEmail() : '' ?>" class="form-control d-inline-block" id="email" placeholder="<?= __('Email', 'hola-weddings') ?>">
    </div>
</div>
<div class="row">
    <div class="form-group col-8 mb-2">
        <label for="address" class="form-label"><?= __('Address', 'hola-weddings') ?></label>
        <input type="text" name="billing[address1]" value="<?= $billing_information ? $billing_information->getAddress() : '' ?>" class="form-control d-inline-block" id="address" placeholder="<?= __('Address', 'hola-weddings') ?>">
    </div>
    <div class="form-group col-2 mb-2">
        <label for="postal_code" class="form-label"><?= __('Postal Code', 'hola-weddings') ?></label>
        <input type="text" name="billing[postal_code]" value="<?= $billing_information ? $billing_information->getPostalCode() : '' ?>" class="form-control d-inline-block" id="postal_code" placeholder="<?= __('Postal Code', 'hola-weddings') ?>">
    </div>
</div>
<div class="row mb-2">
    <div class="col-md-10 form-group">
        <label for="country" class="form-label"><?= __('Country', 'hola-weddings') ?></label>
        <select id="country" type="text" class="form-select" placeholder="<?= __('Country', 'hola-weddings') ?>" name="billing[country]" >
            <optgroup label="<?= __('Most Used', 'hola-weddings') ?>">
                <option value="US">United States</option>
            </optgroup>
            <optgroup label="<?= __('All Countries', 'hola-weddings') ?>">
                <?php foreach(Countries::getList() as $country):
                    // skip us
                    if($country['code'] == 'US'){
                        continue;
                    }
                    ?>
                    <option value="<?= $country['code'] ?>" <?= $billing_information && $country['code'] == $billing_information->getCountry() ? 'selected' : '' ?>><?= $country['name'] ?></option>
                <?php endforeach; ?>
            </optgroup>
        </select>
    </div>
</div>
<div class="row">
    <div class="form-group col-5 mb-2">
        <label for="city" class="form-label"><?= __('City', 'hola-weddings') ?></label>
        <input type="text" name="billing[locality]" value="<?= $billing_information ? $billing_information->getCity() : '' ?>" class="form-control d-inline-block" id="city" placeholder="<?= __('City', 'hola-weddings') ?>">
    </div>
    <div class="form-group col-5 mb-2">
        <label for="state" class="form-label"><?= __('State', 'hola-weddings') ?></label>
        <input type="text" name="billing[administrative_area]" value="<?= $billing_information ? $billing_information->getState() : '' ?>" class="form-control d-inline-block" id="state" placeholder="<?= __('State', 'hola-weddings') ?>">
        <div class="form-text">
            Use 2 letters state code
        </div>
    </div>
</div>