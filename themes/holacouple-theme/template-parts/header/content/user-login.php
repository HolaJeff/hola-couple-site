<?php
global $hw_theme;

$hide_login = hw_evaluate_string_to_boolean($hw_theme['hide_login'] ?? '0');
?>
<div class="<?= hw_is_pos() ? 'ml-auto' : '' ?>">
    <?php if(!is_user_logged_in()): ?>
        <?php if(!$hide_login): ?>
            <div class="login-wrapper d-none d-lg-block">
                <button class="btn btn-link" type="button" data-toggle="modal" data-target="#loginModal">
                    <i data-feather="log-in"></i>   
                    <?= __('Sign In', 'hola-weddings') ?>
                </button>
            </div>
        <?php endif ?>
    <?php else: ?>
        <div class="user-profile-dropdown dropdown login-wrapper d-none d-lg-block">
            <a class="nav-link d-flex dropdown-toggle arrow-none nav-user px-2" data-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                <span>
                    <i data-feather="user"></i>
                </span>
            </a>
            <div class="dropdown-menu dropdown-menu-right dropdown-menu-animated profile-dropdown shadow-sm rounded-0 border-0 pt-0">

                <!-- greeting -->
                <a href="<?= hw_get_account_profile_url() ?>" class="d-md-flex flex-column gap-1 d-none border-bottom py-3 dropdown-item greeting"> 
                    <div class="my-0"><?= sprintf(__('Hello <strong>%s</strong>', 'hola-weddings'), wp_get_current_user()->display_name) ?></div>
                    <small>
                        <i data-feather="edit-2"></i>
                        <?= __('Edit Profile', 'hola-weddings') ?>
                    </small>
                </a>

                <!-- item-->
                <a href="<?= hw_get_reservations_url(); ?>" class="dropdown-item">
                    <i data-feather="calendar"></i>
                    <span><?= __('My Reservations', 'hola-weddings') ?></span>
                </a>

                <!-- item-->
                <a href="<?= wp_logout_url( home_url() ); ?>" class="dropdown-item">
                    <i data-feather="log-out"></i>
                    <span><?= __('Logout', 'hola-weddings') ?></span>
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>