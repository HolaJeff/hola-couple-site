<!-- Payment link request confirmation modal -->
<div class="modal fade" id="invoicePaymentMessageModal" tabindex="-1" aria-labelledby="invoicePaymentMessageModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">
                    <?= __('Payment link requested', 'hola-weddings') ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-5">
                <?= hw_invoice_payment_custom_message() ?>
            </div>
            <div class="modal-footer">
                <button data-dismiss="modal" aria-label="Close" type="button"  class="btn btn-secondary">
                    <?= __('Close', 'hola-weddings') ?>
                </button>
            </div>
        </div>
    </div>
</div>