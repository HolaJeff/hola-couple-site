<?php
/**
 * Template: POS
 */
$id = $_GET['invoiceID'];
$model = hw_get_invoice($id);
$quote = $model->getQuote();
$user_has_access = hw_pos_can_user_access_invoice( $model );

get_header();
?>

	<div id="primary" class="content-area">
		<main id="main" class="site-main bg-light pt-5">

			<div class="content-page container">
                <!-- Start Content-->
                <div class="content">
                    <?php if($user_has_access == true): ?>
                        <?= hw_get_template( 'template-parts/POS/default', ['invoice' => $model] ); ?>
                    <?php else: ?>
                        <?= hw_get_template( 'template-parts/POS/email-auth' ); ?>
                    <?php endif; ?>
                </div>
                <!-- End content -->
            </div>
		</main><!-- #main -->
	</div><!-- #primary -->

<?php
get_footer();