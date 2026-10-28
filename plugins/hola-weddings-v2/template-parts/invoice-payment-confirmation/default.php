<?php
$status = $transaction->getStatus();
$invoice = $transaction->getInvoice();
$quote = $invoice->getQuote();
?>
<div class="row">
    <div class="col-md-6 offset-md-3">
        <div id="payment-processing-message" class="p-5 bg-white shadow text-center">
            <?= hw_get_template( 'template-parts/invoice-payment-confirmation/' . $status,  [ 'invoice' => $invoice, 'transaction' => $transaction ] ) ?>
            <?= hw_get_template( 'template-parts/invoice-payment-confirmation/footer', [ 'quote' => $quote ] ) ?>
        </div>
    </div>
</div>