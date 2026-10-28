<div class="leftside-menu pt-3">

    <div class="text-right text-white pr-3 d-lg-none">
        <i id="leftside-close-btn" class="uil-times"></i>
    </div>

    <!-- Brand Logo Light -->
    <a href="<?= home_url() ?>" class="logo text-center d-none d-lg-block">
        <img src="<?= HW_ASSETS_URL ?>/img/logo.png" alt="logo" style="height: 70px">
    </a>

    <hr class="mt-lg-5">

    <!-- Sidebar -left -->
    <div class="mt-3 mb-3 d-flex flex-column" id="leftside-menu-container" data-simplebar>
        <div class="side-nav nav flex-column">

            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="<?= hw_get_account_url() ?>" aria-expanded="false" aria-controls="sidebarDashboards" class="side-nav-link">
                    <i class="uil-dashboard"></i>
                    <!--<span class="badge bg-success float-end">5</span>-->
                    <span><?= __('Dashboard', 'hola-weddings') ?></span>
                </a>
            </li>

            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="<?= hw_get_reservations_url() ?>" aria-expanded="false" aria-controls="sidebarDashboards" class="side-nav-link">
                    <i class="uil-calendar-alt"></i>
                    <!--<span class="badge bg-success float-end">5</span>-->
                    <span><?= __('Reservations', 'hola-weddings') ?></span>
                </a>
                <!--<div class="collapse" id="sidebarDashboards">
                    <ul class="side-nav-second-level">
                        <li>
                            <a href="dashboard-analytics.html">Analytics</a>
                        </li>
                    </ul>
                </div>-->
            </li>

        </div>
    </div><!--- End Sidemenu -->

    <hr>

    <!-- Help Box -->
    <div class="help-box alert alert-info text-center text-white">
        <div class="text-white text-uppercase fw-bold mb-3"><i class="mdi mdi-lifebuoy mr-1"></i>Need Assistance?</div>
        <?php /* tenant-support */ $hs = function_exists('hw_tenant_support') ? hw_tenant_support() : array('email' => get_option('admin_email'), 'phone' => '', 'facebook' => '', 'instagram' => '', 'twitter' => '', 'pinterest' => ''); ?>
        <?= function_exists('hw_tenant_support_lines') ? hw_tenant_support_lines() : '<div><a href="mailto:' . esc_attr(get_option('admin_email')) . '">' . esc_html(get_option('admin_email')) . '</a></div>' ?>
    </div>
    <!-- end Help Box -->

    <div class="socials mt-auto">
        <ul class="nav justify-content-center">
            <?php if (!empty($hs['facebook'])): ?><li class="nav-item"><a class="nav-link" href="<?= esc_url($hs['facebook']) ?>">
                    <i class="fab fa-facebook"></i>
                </a>
            </li><?php endif; ?>
            <?php if (!empty($hs['twitter'])): ?><li class="nav-item"><a class="nav-link" href="<?= esc_url($hs['twitter']) ?>">
                    <i class="fab fa-twitter"></i>
                </a>
            </li><?php endif; ?>
            <?php if (!empty($hs['instagram'])): ?><li class="nav-item"><a class="nav-link" href="<?= esc_url($hs['instagram']) ?>">
                    <i class="fab fa-instagram"></i>
                </a>
            </li><?php endif; ?>
            <?php if (!empty($hs['pinterest'])): ?><li class="nav-item"><a class="nav-link" href="<?= esc_url($hs['pinterest']) ?>">
                    <i class="fab fa-pinterest"></i>
                </a>
            </li><?php endif; ?>
        </ul>
    </div>
</div>