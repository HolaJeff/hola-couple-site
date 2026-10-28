<?php
$user = wp_get_current_user();
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