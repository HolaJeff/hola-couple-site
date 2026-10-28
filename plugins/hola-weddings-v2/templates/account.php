<?php
/**
 * Template: account template
 */

get_header('account');
?>

	<div id="primary" class="content-area">
		<main id="main" class="site-main"
			x-data
			x-effect="document.body.classList.toggle('leftside-collapsed', $store.accountSidebar.collapsed); document.body.classList.toggle('leftside-open', $store.accountSidebar.open)">

            <!-- Navbar -->
            <?= hw_get_template( 'template-parts/account/navbar'); ?>
            <!-- End Navbar -->

            <!-- Sidebar -->
            <?= hw_get_template( 'template-parts/account/sidebar'); ?>
            <!-- End sidebar -->

            <!-- Mobile drawer backdrop -->
            <div class="account-backdrop" @click="$store.accountSidebar.close()"></div>

			<div class="content-page">
                <!-- Start Content-->
                <div class="content">

                    <?php do_action('hw_account_before_content') ?>

                    <?= hw_get_template( 'template-parts/account/views/' . hw_get_account_view() ); ?>

                </div>
                <!-- content -->

                <?php // hw_get_template('template-parts/account/footer') ?>
            </div>
		</main><!-- #main -->
	</div><!-- #primary -->

<?php
get_footer('account');