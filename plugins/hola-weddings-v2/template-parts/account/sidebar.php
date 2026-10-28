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
        <div><a href="mailto:hola@holaweddings.com">hola@holaweddings.com</a></div>
        <div><a href="tel:+1 888 472 7077">+1 888 472 7077</a></div>
    </div>
    <!-- end Help Box -->

    <div class="socials mt-auto">
        <ul class="nav justify-content-center">
            <li class="nav-item">
                <a class="nav-link" href="https://www.facebook.com/holaweddings/">
                    <i class="fab fa-facebook"></i>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="https://twitter.com/holaweddings?lang=en">
                    <i class="fab fa-twitter"></i>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="https://www.instagram.com/holawedding/?hl=en">
                    <i class="fab fa-instagram"></i>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="https://www.pinterest.com.mx/holaweddings/">
                    <i class="fab fa-pinterest"></i>
                </a>
            </li>
        </ul>
    </div>
</div>