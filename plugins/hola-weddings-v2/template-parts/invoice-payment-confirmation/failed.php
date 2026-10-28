<div class="text-center text-danger" style="font-size: 64px">
    <i class="fas fa-times-circle"></i>
</div>
<p class="title text-warning mt-3 text-danger"><?= __('Transaction Failed', 'hola-weddings') ?></p>
<p class="mt-3"><?= __('Your payment has failed. Please, try again or contact our support team at <a href="mailto:hola@holaweddings.com">hola@holaweddings.com</a>', 'hola-weddings') ?></p>
<?= hw_get_template( 'template-parts/invoice-payment-confirmation/details', [ 'invoice' => $invoice, 'transaction' => $transaction ] ) ?>
