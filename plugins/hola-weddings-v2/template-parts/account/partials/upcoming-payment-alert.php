<?php if($invoice): $pay_action = hw_get_invoice_payment_btn_action(); ?>
    <div class="reservation__upcoming-payment p-3 p-md-2 d-flex flex-column flex-md-row align-items-md-center <?= $classes ?: '' ?>">
        <div class="reservation__upcoming-payment--icon-wrapper d-none d-lg-block">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div vclass="reservation__upcoming-payment--info">
            <span class="reservation__upcoming-payment--label mb-1 text-uppercase d-flex flex-items-center">
                <i class="fas fa-exclamation-triangle mr-1 d-inline-block d-lg-none"></i>
                <?= __('Upcoming Payment', 'hola-weddings' ) ?>
            </span>
            <div class="reservation__upcoming-payment--title mb-2"><?= esc_html($invoice->getTitle()) ?></div>
            <div class="reservation__upcoming-payment--meta mt-0">
                <i class="far fa-clock"></i>    
                <?= sprintf(__('Due %s', 'hola-weddings'), $invoice->getDueDate('M d, Y')) ?>
                <?php if($pay_action !== 'payment-form'): ?>
                    <span class="reservation__upcoming-payment--amount"><?= hw_format_price( $invoice->getDueAmount() ) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="ml-md-auto">
            <?php if($pay_action === 'payment-form'): ?>
                <a class="btn btn-primary reservation__upcoming-payment--pay-btn" href="<?= hw_get_invoice_payment_permalink($invoice->getID()) ?>" target="_blank">
                    <span class="reservation__pay-text">
                        <span class="reservation__pay-label"><?= __('Pay', 'hola-weddings' ) ?></span>
                        <span class="reservation__pay-amount"><?= hw_format_price( $invoice->getDueAmount() ) ?></span>
                    </span>
                    <i class="fas fa-arrow-right"></i> 
                </a>
            <?php elseif($pay_action === 'request-payment-link'): ?>
                <button 
                    x-data="requestPaymentLinkBtnController(<?= $invoice->getID() ?>)"
                    class="btn btn-primary reservation__upcoming-payment--pay-btn" 
                    type="button" 
                    data-invoice-id="<?= $invoice->getID() ?>"
                    :disabled="loading"
                    @click="request()"
                    >
                        <span x-show="!loading" style="width: 100%; display: flex;" class="align-items-center justify-content-between">
                            <?= __('Request Payment Link', 'hola-weddings') ?>
                                <i class="fas fa-arrow-right"></i> 
                        </span>
                        <span style="display: none;" x-show="loading">
                            <span class="spinner-border spinner-border-sm mr-1" role="status" aria-hidden="true"></span>
                            <?= __('Sending Request ...', 'hola-weddings') ?>
                        </span>
                </button>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>