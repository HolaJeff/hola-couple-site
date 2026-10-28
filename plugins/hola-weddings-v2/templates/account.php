<?php
/**
 * Template: account template
 */

get_header('account');
?>

	<div id="primary" class="content-area">
		<main id="main" class="site-main">

            <!-- Navbar -->
            <?= hw_get_template( 'template-parts/account/navbar'); ?>
            <!-- End Navbar -->

            <!-- Sidebar -->
            <?= hw_get_template( 'template-parts/account/sidebar'); ?>
            <!-- End sidebar -->

			<div class="content-page">
                <!-- Start Content-->
                <div class="content py-5 px-3 p-lg-5">

                    <?php do_action('hw_account_before_content') ?>
                    
                    <?= hw_get_template( 'template-parts/account/views/' . hw_get_account_view() ); ?>

                </div>
                <!-- content -->

                <?= hw_get_template('template-parts/account/footer') ?>
            </div>
		</main><!-- #main -->
	</div><!-- #primary -->

<?php
get_footer('account');