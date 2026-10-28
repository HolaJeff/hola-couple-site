<div id="mobile-cart-summary-nav" class="d-block d-lg-none position-fixed fixed-bottom bg-white p-3 w-100">
    <div class="inner d-flex justify-content-between align-items-center">
        <div class="total-wrapper">
            <small><?= __('Total', 'hola-weddings') ?></small>
            <div class="total font-weight-bold">
                <?= hw_format_price(0) ?>
            </div>
        </div>
        <div class="action-wrapper">
            <button data-display-cart class="btn btn-outline-primary"><?= __('View Details', 'hola-weddings') ?></button>
        </div>
    </div>
</div>
<div id="mobile-cart-summary" class="position-fixed bg-white fixed-top">
    <div class="inner">
        <div class="header border-bottom text-right p-3 d-flex justify-content-between">
            <h4 class="mb-0"><?= __('Your Reservation', 'hola-weddings') ?></h4>
            <i data-close data-feather="x-circle"></i>
        </div>
        <div class="body p-3">
            <div data-cart class="cart-holder">
                <p><?= __('No rooms selected', 'hola-weddings') ?></p>
            </div>
        </div>
    </div>
</div>