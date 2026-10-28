<img src="<?= HW_ASSETS_URL ?>/img/success.gif" />
<p class="title text-success mt-3"><?= __('Payment Successful', 'hola-weddings') ?></p>
<?= hw_get_template( 'template-parts/invoice-payment-confirmation/details', [ 'invoice' => $invoice, 'transaction' => $transaction ] ) ?>