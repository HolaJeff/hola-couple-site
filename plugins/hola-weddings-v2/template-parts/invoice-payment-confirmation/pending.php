<img src="<?= HW_ASSETS_URL ?>/img/pending-payment.png" style="height: 64px" />
<p class="title text-warning mt-3"><?= __('Awaiting Confirmation', 'hola-weddings') ?></p>
<p class="mt-3"><?= __('Your payment is currently under reviewed and may take up to 5 minutes to update. Please <span class="font-weight-bold">DO NOT</span> submit another payment.', 'hola-weddings') ?></p>
<?= hw_get_template( 'template-parts/invoice-payment-confirmation/details', [ 'invoice' => $invoice, 'transaction' => $transaction ] ) ?>
<script type="text/javascript">
    jQuery(function($){

        // URL to your AJAX handler—adjust as needed
        var ajaxUrl = '<?php echo admin_url("admin-ajax.php"); ?>';
        
        // Function to poll WP
        function pollForReload() {
            $.ajax({
                url: ajaxUrl,
                method: 'POST',
                dataType: 'json',
                data: {
                    action: 'hw_confirmation_transaction_ticker',
                    transactionID: <?= $transaction->getID() ?>
                },
                success: function(response) {
                    
                    if (response != 0 && response.reload) {
                        location.reload();
                    }
                },
                error: function(xhr, status, err) {
                    console.error('Reload check failed:', status, err);
                }
            });
        }

        // Start polling every 10 seconds (10,000 ms)
        setInterval(pollForReload, 10000);
    })
</script>