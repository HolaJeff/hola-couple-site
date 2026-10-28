<?php
/**
 * Template: Invoice Payment Confirmation
 */
$id = $_GET['transactionID'];
$transaction = hw_get_transaction($id);
$status = $transaction->getStatus();

get_header();
?>

	<div id="primary" class="content-area">
		<main id="main" class="site-main bg-light pt-5">

			<div class="content-page container">
                <!-- Start Content-->
                <div class="content">
                    <?= hw_get_template( 'template-parts/invoice-payment-confirmation/default', [ 'transaction' => $transaction ] ) ?>
                </div>
                <!-- End content -->
            </div>
		</main><!-- #main -->
	</div><!-- #primary -->

<?php
get_footer();