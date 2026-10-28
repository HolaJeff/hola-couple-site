<?php

use HW\Enums\TransactionStatus;

$quote = $invoice->getQuote();
$last_transaction = $invoice->getLastTransaction();
?>
<div id="pos">

    <?= hw_get_template('template-parts/POS/header', ['invoice' => $invoice, 'quote' => $quote]) ?>

    <?php if($invoice->getStatus() == 'paid' || $invoice->getDueAmount() <= 0): ?>
        <div class="alert alert-success mb-3"><?= __('This invoice is fully paid.', 'hola-weddings') ?></div>
    <?php elseif($last_transaction && $last_transaction->getStatus() == TransactionStatus::PENDING->value): ?>
        <div class="alert alert-warning mb-3"><?= __('This invoice has a pending payment, please allow us to review it and we\'ll get back to you soon. ', 'hola-weddings') ?></div>
    <?php else: ?>  
        <!-- error alert -->
        <div data-error-message class="alert alert-danger" style="display: none;" >
            <div class="header font-weight-bold mb-2">
                <?= __('Payment Failed', 'hola-weddings') ?>
            </div>
            <div class="message"></div>
        </div>
        
        <div class="row flex-column-reverse flex-lg-row">
            <div class="col-lg-8">
                <form id="pos-form">
                    <div id="invoice-checkout" >

                        <?php if($invoice->getDueAmount() > 0): ?>

                            <?php if(current_user_can('administrator')): ?>
                                <!-- Test options -->
                                <?= hw_get_template('template-parts/POS/checkout/test') ?>
                            <?php endif; ?>
                        
                            <!-- card options -->
                            <?= hw_get_template('template-parts/POS/checkout/payment', ['invoice' => $invoice]) ?>

                            <!-- billing options -->
                            <?= hw_get_template('template-parts/POS/checkout/billing', ['invoice' => $invoice]) ?>

                            <!-- billing options -->
                            <?= hw_get_template('template-parts/POS/checkout/terms', ['invoice' => $invoice]) ?>

                            <noscript>
                                <div class="alert alert-warning">
                                    <?= __('This form requires Javascript to work. Please, enable Javascript and reload the page.', 'hola-weddings') ?>
                                </div>
                            </noscript>

                            <!-- submit -->
                            <div class="text-right mt-3">
                                <span class="text-success mr-4 small align-middle">
                                    <i class="fa fa-lock mr-2" aria-hidden="true"></i>
                                    <?= __('Secured Payment', 'hola-weddings') ?>
                                </span>
                                <button id="submit" class="btn btn-primary px-5" type="button" disabled>
                                    <?= __('Pay Now', 'hola-weddings') ?>
                                </button>
                            </div>
                        
                        <?php endif; ?>
                    </div>
                    <input type="hidden" name="invoice_id" value="<?= $invoice->getID() ?>" />
                    <input type="hidden" name="action" value="hw_pos_process_payment" />
                    <?php wp_nonce_field( 'hw-pos-invoice-' . $invoice->getID() ); ?>
                </form>
            </div>
            <div id="sidebar" class="col-lg-4">

                <?= hw_get_template('template-parts/POS/sidebar/reservation-details', ['quote' => $quote]) ?>

                <?= hw_get_template('template-parts/POS/sidebar/summary', ['invoice' => $invoice]) ?>

                <?= hw_get_template('template-parts/POS/sidebar/help-box') ?>
            </div>
        </div>
    <?php endif; ?>  
</div>

<?= hw_get_template('template-parts/modals/terms-conditions'); ?>

<div id="payment-processing-message" class="p-5 bg-white shadow" style="display: none;">
    <div class="inner text-center">
        <div class="view" data-view="processing">
            <img src="<?= HW_ASSETS_URL ?>/img/credit-card.gif" />
            <p class="title"><?= __('Processing Payment', 'hola-weddings') ?></p>
            <p class="message"><?= __('Please wait. Don\'t close this window.', 'hola-weddings') ?></p>
        </div>
    </div>
</div>

<?= hw_get_template('template-parts/partials/ui/help-bubble') ?>