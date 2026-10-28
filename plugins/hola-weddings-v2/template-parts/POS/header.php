<div class="d-flex flex-column flex-md-row mb-3">
    <div class="metadata mb-1 mb-md-0">
        <!-- title -->
        <h3>
            <?= sprintf(__('Invoice #%d - %s', 'hola-weddings'), $invoice->getID(), $invoice->getTitle()); ?>
        </h3>
        <!-- end title -->
    </div>

    <!-- actions -->
    <div class="actions ml-md-auto">
        <a href="<?= hw_get_reservation_permalink($quote->getID()) ?>" class="btn btn-outline-primary">
            <i class="fas fa-eye"></i>
            <?= __('View Reservation', 'hola-weddings') ?>
        </a>
    </div>
</div>