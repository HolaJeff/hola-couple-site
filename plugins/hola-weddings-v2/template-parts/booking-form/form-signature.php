<div id="cart-signature" class="mb-2 alert alert-warning">
    <h4 class="mb-3"><?= __('Signature', 'hola-weddings') ?></h4>
    <p><?= __('Please add your electronic signature in order to complete your reservation.', 'hola-weddings') ?></p>
    <div data-placeholder class="mb-3 signature-placeholder" style="display: none"></div>
    <input data-hidden-input type="hidden" name="signature" value="" />
    <button data-trigger type="button" class="btn btn-outline-warning">
        <?= __('Sign now!', 'hola-weddings'); ?>
    </button>
</div>