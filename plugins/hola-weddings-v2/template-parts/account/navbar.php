<div class="navbar-custom">
    <div class="topbar container-fluid">
        <div class="d-md-flex align-items-center gap-lg-2 gap-1">

            <!-- Topbar Brand Logo -->
            <div class="logo-topbar">
                <!-- Logo here -->
            </div>

            <!-- Sidebar Menu Toggle Button -->
            <button id="leftside-menu-btn" class="button-toggle-menu d-inline-block d-lg-none">
                <i class="mdi mdi-menu"></i>
            </button>
        </div>

        <div id="navbar-logo">
            <a href="<?= home_url() ?>" class="logo text-center d-block d-lg-none">
                <img src="<?= HW_ASSETS_URL ?>/img/logo.png" alt="logo" style="height: 35px">
            </a>
        </div>

        <ul class="topbar-menu d-flex align-items-center gap-3">

            <li class="dropdown">
                <a class="nav-link dropdown-toggle arrow-none nav-user px-2" data-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                    <!--<span class="account-user-avatar">
                        <img src="assets/images/users/avatar-1.jpg" alt="user-image" width="32" class="rounded-circle">
                    </span>-->
                    <span class="d-md-flex flex-column gap-1 d-none">
                        <span class="my-0"><?= sprintf(__('Hello <strong>%s</strong>', 'hola-weddings'), wp_get_current_user()->display_name) ?></span>
                    </span>
                </a>
                <div class="dropdown-menu dropdown-menu-right dropdown-menu-animated profile-dropdown shadow-sm rounded-0 border-0">

                    <!-- item-->
                    <a href="<?= hw_get_account_profile_url() ?>" class="dropdown-item">
                        <i class="mdi mdi-account-circle"></i>
                        <span><?= __('My Profile', 'hola-weddings') ?></span>
                    </a>

                    <!-- item-->
                    <!--<a href="javascript:void(0);" class="dropdown-item">
                        <i class="mdi mdi-lifebuoy"></i>
                        <span>Support</span>
                    </a>-->

                    <!-- item-->
                    <a href="<?= wp_logout_url( home_url() ); ?>" class="dropdown-item">
                        <i class="mdi mdi-logout"></i>
                        <span><?= __('Logout', 'hola-weddings') ?></span>
                    </a>
                </div>
            </li>
        </ul>
    </div>
</div>