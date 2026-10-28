<button 
    x-data="requestPaymentLinkBtnController(<?= $invoice->getID() ?>)"
    class="d-block btn <?= $style ?? 'btn-link' ?> ?> text-right text-underline mt-2 <?= $class ?? '' ?>" 
    type="button" 
    data-invoice-id="<?= $invoice->getID() ?>"
    :disabled="loading"
    @click="request()"
    >
        <span x-show="!loading"><?= __('Request Payment Link', 'hola-weddings') ?></span>
        <span x-show="loading">
            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            <?= __('Sending Request ...', 'hola-weddings') ?>
        </span>
</button>