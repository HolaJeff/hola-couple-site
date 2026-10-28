<div class="page-header">
    <div class="container">
        <h1 class="page-title text-center text-white mb-3">
            <?= __('Ops! Something went wrong.', 'hola-weddings' ); ?>
        </h1>
    </div>
</div>
<div class="page-body text-center py-5">
    <div class="container">
        <p class="mb-3" style="font-size: 1.2em;">
            <?= __('Please contact us at', 'hola-weddings') ?> 
            <a href="mailto:hola@holaweddings.com">hola@holaweddings.com</a>
        </p>
        <p><?= __('OR', 'hola-weddings') ?></p>
        <a class="btn btn-primary" href="<?= hw_get_reservations_url() ?>">
            <?= __('Go to My Reservations', 'hola-weddings') ?>
        </a>
    </div>
</div>