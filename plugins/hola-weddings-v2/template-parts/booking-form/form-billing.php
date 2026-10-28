<?php
use \HW\Helpers\Countries;

// Pre-fill from the user's saved billing (user meta) on first load (no submitted data yet)
if ( is_user_logged_in() && empty( $data['customer'] ) ) {
    $billing = hw_get_user_billing_information( get_current_user_id() );
    if ( $billing ) {
        $data['customer'] = [
            'first_name'   => $billing->getFirstName(),
            'last_name'    => $billing->getLastName(),
            'email'        => $billing->getEmail(),
            'phone_number' => $billing->getPhone(),
            'address'      => $billing->getAddress(),
            'city'         => $billing->getCity(),
            'zip'          => $billing->getPostalCode(),
            'state'        => $billing->getState(),
            'country'      => $billing->getCountry(),
        ];
    }
}

$selected_country = $data['customer']['country'] ?? '';
$is_US = $selected_country == 'US';
$submitted_state = $data['customer']['state'] ?? '';
?>
<div class="row">
    <div class="col-md-6 form-group">
        <label for="first-name"><?= __('First Name', 'hola-weddings') ?></label>
        <input id="first-name" type="text" class="form-control <?= isset($errors['first_name']) ? 'is-invalid' : '' ?>" placeholder="<?= __('First Name', 'hola-weddings') ?>" name="customer[first_name]" value="<?= isset($data['customer']['first_name']) ? $data['customer']['first_name'] : '' ?>" />
        <?php if(isset($errors['first_name'])): ?>
            <div id="first-name-Feedback" class="invalid-feedback">
               <?= $errors['first_name'] ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="col-md-6 form-group">
        <label for="last-name"><?= __('Last Name', 'hola-weddings') ?></label>
        <input id="last-name" type="text" class="form-control <?= isset($errors['last_name']) ? 'is-invalid' : '' ?>" placeholder="<?= __('Last Name', 'hola-weddings') ?>" name="customer[last_name]" value="<?= isset($data['customer']['last_name']) ? $data['customer']['last_name'] : '' ?>" />
        <?php if(isset($errors['last_name'])): ?>
            <div id="last-name-Feedback" class="invalid-feedback">
               <?= $errors['last_name'] ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<div class="row">
    <div class="col-md-6 form-group">
        <label for="email"><?= __('Email', 'hola-weddings') ?></label>
        <input id="email" type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" placeholder="<?= __('Email', 'hola-weddings') ?>" name="customer[email]" value="<?= isset($data['customer']['email']) ? $data['customer']['email'] : '' ?>" />
        <?php if(isset($errors['email'])): ?>
            <div id="email-Feedback" class="invalid-feedback">
               <?= $errors['email'] ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="col-md-6 form-group">
        <label for="phone"><?= __('Phone Number', 'hola-weddings') ?></label>
        <input id="phone" type="tel" class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" placeholder="<?= __('Phone Number', 'hola-weddings') ?>" name="customer[phone_number]" value="<?= isset($data['customer']['phone_number']) ? $data['customer']['phone_number'] : '' ?>" />
        <?php if(isset($errors['phone'])): ?>
            <div id="phone-Feedback" class="invalid-feedback">
               <?= $errors['phone'] ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="row">

    <!-- Address -->
    <div class="form-group col-md-6">
        <label for="address"><?= __('Address', 'hola-weddings') ?></label>
        <input id="address" type="text" class="form-control <?= isset($errors['address']) ? 'is-invalid' : '' ?>" placeholder="<?= __('Street address', 'hola-weddings') ?>" name="customer[address]" value="<?= isset($data['customer']['address']) ? $data['customer']['address'] : '' ?>" />
        <?php if(isset($errors['address'])): ?>
            <div id="address-Feedback" class="invalid-feedback">
                <?= $errors['address'] ?>
            </div>
        <?php endif; ?>
    </div>
    <!-- city -->
    <div class="col-md-4 form-group">
        <label for="city"><?= __('City', 'hola-weddings') ?></label>
        <input id="city" type="text" class="form-control <?= isset($errors['city']) ? 'is-invalid' : '' ?>" placeholder="<?= __('City', 'hola-weddings') ?>" name="customer[city]" value="<?= isset($data['customer']['city']) ? $data['customer']['city'] : '' ?>" />
        <?php if(isset($errors['city'])): ?>
            <div id="city-Feedback" class="invalid-feedback">
                <?= $errors['city'] ?>
            </div>
        <?php endif; ?>
    </div>
    <!-- zip -->
    <div class="col-md-2 form-group">
        <label for="zip"><?= __('ZIP Code', 'hola-weddings') ?></label>
        <input id="zip" type="text" class="form-control <?= isset($errors['zip']) ? 'is-invalid' : '' ?>" placeholder="<?= __('ZIP Code', 'hola-weddings') ?>" name="customer[zip]" value="<?= isset($data['customer']['zip']) ? $data['customer']['zip'] : '' ?>" />
        <?php if(isset($errors['zip'])): ?>
            <div id="zip-Feedback" class="invalid-feedback">
                <?= $errors['zip'] ?>
            </div>
        <?php endif; ?>
    </div>
</div>
                    
<div class="row">
    <!-- state -->
    <div class="col-md-6 form-group">
        <label for="email"><?= __('State / Province', 'hola-weddings') ?></label>
        <select id="us-states" type="text" class="form-control <?= isset($errors['state']) ? 'is-invalid' : '' ?>" name="customer[state]">
            <option value="0"><?= __('- Select State -') ?></option>
            <?php foreach(Countries::getUSStates() as $code => $name): ?>
                <option value="<?= $code ?>" <?= $is_US && $submitted_state == $code ? 'selected' : '' ?>><?= $name ?></option>
            <?php endforeach; ?>
        </select>
        <input id="other-state" autocomplete="off" type="text" class="form-control <?= isset($errors['state']) ? 'is-invalid' : '' ?>" placeholder="<?= __('State / Province', 'hola-weddings') ?>" name="customer[state]" value="<?= isset($data['customer']['state']) && !$is_US ? $data['customer']['state'] : '' ?>" />
        <?php if(isset($errors['state'])): ?>
            <div id="state-Feedback" class="invalid-feedback">
               <?= $errors['state'] ?>
            </div>
        <?php endif; ?>
    </div>
    <!-- country -->
    <div class="col-md-6 form-group">
        <label for="country"><?= __('Country', 'hola-weddings') ?></label>
        <select id="country" type="text" class="form-control <?= isset($errors['country']) ? 'is-invalid' : '' ?>" placeholder="<?= __('Country', 'hola-weddings') ?>" name="customer[country]" >
            <optgroup label="<?= __('Most Used', 'hola-weddings') ?>">
                <option value="US" <?= $is_US == 'US' ? 'selected' : '' ?>>United States</option>
            </optgroup>
            <optgroup label="<?= __('All Countries', 'hola-weddings') ?>">
                <?php foreach(Countries::getList() as $country):
                    // skip us
                    if($country['code'] == 'US'){
                        continue;
                    }
                    ?>
                    <option value="<?= $country['code'] ?>" <?= $selected_country == $country['code'] ? 'selected' : '' ?>><?= $country['name'] ?></option>
                <?php endforeach; ?>
            </optgroup>
        </select>
        <!--<input id="country" autocomplete="off" type="text" class="form-control <?= isset($errors['country']) ? 'is-invalid' : '' ?>" placeholder="<?= __('Country', 'hola-weddings') ?>" name="customer[country]" value="<?= isset($data['customer']['country']) ? $data['customer']['country'] : '' ?>" />-->
        <?php if(isset($errors['country'])): ?>
            <div id="country-Feedback" class="invalid-feedback">
               <?= $errors['country'] ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php if ( is_user_logged_in() ): ?>
    <div class="form-check mt-3">
        <input class="form-check-input" type="checkbox" name="update_customer_data" id="update-customer-data" value="1" />
        <label class="form-check-label" for="update-customer-data">
            <?= __( 'Save my information for future bookings.', 'hola-weddings' ) ?>
        </label>
    </div>
<?php endif; ?>
<?php if(!is_user_logged_in() && !hw_booking_form_hide_create_account()): ?>
    <hr>
    <div class="sign-up">
        <h4><?= __('Create an Account (Optional)', 'hola-weddings') ?></h4>
        <p><?= __('Insert a password below to create an account and track this reservation. Leave it blank to book as a guest.', 'hola-weddings') ?></p>
        <div class="row">
            <div class="col-6 form-group">
                <input id="password" type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" placeholder="<?= __('Password', 'hola-weddings') ?>" name="password" value="<?= isset($data['customer']['password']) ? $data['customer']['password'] : '' ?>" />
                <?php if(isset($errors['password'])): ?>
                    <div id="password-Feedback" class="invalid-feedback">
                        <?= $errors['password'] ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-6 form-group">
                <input id="confirm-password" type="password" class="form-control <?= isset($errors['confirm-password']) ? 'is-invalid' : '' ?>" placeholder="<?= __('Confirm Password', 'hola-weddings') ?>" name="confirm-password" value="<?= isset($data['customer']['confirm-password']) ? $data['customer']['confirm-password'] : '' ?>" />
                <?php //if(isset($errors['confirm-password'])): ?>
                    <div id="confirm-password-Feedback" class="invalid-feedback">
                        <?= isset($errors['confirm-password']) ? $errors['confirm-password'] : '' ?>
                    </div>
                <?php //endif; ?>
            </div>
        </div>
        <?php if(!hw_hide_form_login_cta()): ?>
            <p><?= __('Already have an account? <a href="#" class="ml-2 text-primary" type="button" data-toggle="modal" data-target="#loginModal">Log In</a>', 'hola-weddings') ?></p>
        <?php endif; ?>
    </div>
<?php endif; ?>