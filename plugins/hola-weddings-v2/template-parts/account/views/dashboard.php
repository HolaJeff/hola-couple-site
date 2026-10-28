<h2><?= sprintf(__('Welcome %s', 'hola-weddings'), wp_get_current_user()->display_name) ?></h2>
<div id="dashboard-tiles" class="row">
    <div class="col-md-6 col-lg-3">
        <a href="<?= hw_get_reservations_url() ?>" class="d-block tile text-center">
            <div class="body">
                <i class="fas fa-calendar-alt icon"></i>
                <h4 class="title"><?= __('My Reservations', 'hola-weddings') ?></h4>
            </div>
        </a>
    </div>
</div>