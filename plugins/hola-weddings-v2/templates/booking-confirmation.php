<?php
/**
 * Template: booking confirmation
 */

$session = hw_get_session();
$id = $session->get('confirmation_endpoint_booking_id');
get_header();
?>

	<div id="primary" class="content-area">
		<main id="main" class="site-main">

			<div class="content-page">
                <!-- Start Content-->
                <div class="content">

                    <?php if(!empty($id)): 

                        $quote = hw_get_quote($id); 
                        if($quote->exists()): ?>
                            <?= hw_get_template( 'template-parts/booking/confirmation/default', ['quote' => $quote] ); ?>
                        <?php else: ?>
                            <?= hw_get_template( 'template-parts/booking/confirmation/error' ); ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <?= hw_get_template( 'template-parts/common/session-expired', ['message' => sprintf(__('<a class="btn btn-primary" href="%s">Go to My Reservations</a>'), hw_get_reservations_url())] ); ?>
                    <?php endif; ?>

                </div>
                <!-- End content -->
            </div>
		</main><!-- #main -->
	</div><!-- #primary -->

<?php
get_footer();