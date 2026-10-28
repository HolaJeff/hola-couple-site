<div id="offcanvas">
    <span class="toggle-close">
        <i class="fa fa-times"></i>
    </span>
    <div class="offcanvas-inner">
        <?php if ( is_active_sidebar( 'offcanvas' ) ) : ?>

            <?php dynamic_sidebar( 'offcanvas' ); ?>
										
        <?php endif; ?>

        <hr>

        <?php if(!is_user_logged_in()): ?>
            <div class="login-wrapper px-3">
                <button class="btn btn-primary d-block w-100" type="button" data-toggle="modal" data-target="#loginModal"><?= __('Log In', 'hola-weddings') ?></button>
            </div>
        <?php else: ?>
            <ul class="nav flex-column">
                <!-- item-->
                <li class="nav-item">
                    <a class="nav-link" href="<?= hw_get_reservations_url(); ?>" class="dropdown-item">
                        <i data-feather="calendar" class="mr-1"></i>
                        <span><?= __('My Reservations', 'hola-weddings') ?></span>
                    </a>
                </li>

                <!-- item-->
                <li class="nav-item">
                    <a class="nav-link" href="<?= hw_get_account_profile_url() ?>" class="dropdown-item">
                    <i data-feather="user" class="mr-1"></i>
                        <span><?= __('My Profile', 'hola-weddings') ?></span>
                    </a>
                </li>

                <!-- item-->
                <li class="nav-item">
                    <a class="nav-link" href="<?= wp_logout_url( home_url() ); ?>" class="dropdown-item">
                        <i data-feather="log-out" class="mr-1"></i>
                        <span><?= __('Logout', 'hola-weddings') ?></span>
                    </a>
                </li>
            </ul>
        <?php endif; ?>
    </div>
</div>
<div id="offcanvas-overlay"></div>