<?php
use \HW\Helpers\Countries;

$user    = wp_get_current_user();
$billing = hw_get_user_billing_information( $user->ID );

$billing_country = $billing ? $billing->getCountry() : '';
?>
<h2><?= __('My Profile', 'hola-weddings') ?></h2>
<div id="profile" class="profile-details">
    <div class="panel">
        <div class="title"><?= __('Profile Details', 'hola-weddings') ?></div>
        <div class="row mb-3">
            <div class="col-md-6">
                <div class="label"><?= __('First Name', 'hola-weddings') ?></div>
                <div class="value"><?= $user->first_name ?></div>
            </div>
            <div class="col-md-6">
                <div class="label"><?= __('Last  Name', 'hola-weddings') ?></div>
                <div class="value"><?= $user->last_name ?></div>
            </div>
        </div> 
        <div class="row">
            <div class="col-12">
                <div class="label"><?= __('Username', 'hola-weddings') ?></div>
                <div class="value"><?= $user->display_name ?></div>
            </div>
        </div>
    </div>
    <div class="panel">
        <div class="title"><?= __('Billing Information', 'hola-weddings') ?></div>
        <p class="text-muted"><?= __('The name and details used on your bookings and invoices. This can differ from your account name.', 'hola-weddings') ?></p>
        <form id="billing-information-form" method="post" action="">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="billing-first-name"><?= __('First Name', 'hola-weddings') ?></label>
                        <input type="text" id="billing-first-name" name="first_name" class="form-control" value="<?= esc_attr( $billing ? $billing->getFirstName() : '' ) ?>" />
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="billing-last-name"><?= __('Last Name', 'hola-weddings') ?></label>
                        <input type="text" id="billing-last-name" name="last_name" class="form-control" value="<?= esc_attr( $billing ? $billing->getLastName() : '' ) ?>" />
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="billing-phone"><?= __('Phone Number', 'hola-weddings') ?></label>
                        <input type="tel" id="billing-phone" name="phone_number" class="form-control" value="<?= esc_attr( $billing ? $billing->getPhone() : '' ) ?>" />
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="billing-address"><?= __('Address', 'hola-weddings') ?></label>
                        <input type="text" id="billing-address" name="address" class="form-control" value="<?= esc_attr( $billing ? $billing->getAddress() : '' ) ?>" />
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="billing-city"><?= __('City', 'hola-weddings') ?></label>
                        <input type="text" id="billing-city" name="city" class="form-control" value="<?= esc_attr( $billing ? $billing->getCity() : '' ) ?>" />
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="billing-zip"><?= __('ZIP Code', 'hola-weddings') ?></label>
                        <input type="text" id="billing-zip" name="zip" class="form-control" value="<?= esc_attr( $billing ? $billing->getPostalCode() : '' ) ?>" />
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="billing-state"><?= __('State / Province', 'hola-weddings') ?></label>
                        <input type="text" id="billing-state" name="state" class="form-control" value="<?= esc_attr( $billing ? $billing->getState() : '' ) ?>" />
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="billing-country"><?= __('Country', 'hola-weddings') ?></label>
                        <select id="billing-country" name="country" class="form-control">
                            <option value=""><?= __('- Select Country -', 'hola-weddings') ?></option>
                            <optgroup label="<?= __('Most Used', 'hola-weddings') ?>">
                                <option value="US" <?= $billing_country === 'US' ? 'selected' : '' ?>>United States</option>
                            </optgroup>
                            <optgroup label="<?= __('All Countries', 'hola-weddings') ?>">
                                <?php foreach ( Countries::getList() as $country ):
                                    if ( $country['code'] === 'US' ) continue; ?>
                                    <option value="<?= $country['code'] ?>" <?= $billing_country === $country['code'] ? 'selected' : '' ?>><?= $country['name'] ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><?= __('Update Billing Information', 'hola-weddings') ?></button>
            <?php wp_nonce_field( 'hw_update_billing_' . $user->ID ); ?>
            <input type="hidden" name="action" value="hw_update_billing" />
        </form>
    </div>
    <div class="panel">
        <div class="title"><?= __('Change Password', 'hola-weddings') ?></div>
        <form id="change-password-form" method="post" action="">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="old-password"><?= __('Old Password', 'hola-weddings') ?></label>
                        <input type="password" id="old-password" required name="old-password"  class="form-control"/>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="old-password"><?= __('New Password', 'hola-weddings') ?></label>
                        <input type="password" id="new-password" required name="new-password" class="form-control"/>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="old-password"><?= __('Confirm New Passord', 'hola-weddings') ?></label>
                        <input type="password" id="confirm-new-password" required name="confirm-password"  class="form-control"/>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><?= __('Change Password', 'hola-weddings') ?></button>
            <?php wp_nonce_field( 'change-password_'.$user->ID ); ?>
            <input type="hidden" name="action" value="change_user_password" />
        </form>
    </div>
</div>