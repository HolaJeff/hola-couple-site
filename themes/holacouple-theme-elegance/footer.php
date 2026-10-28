<?php
global $hw_theme;
$display_separator = hw_evaluate_string_to_boolean($hw_theme['header_logo_display_separator'] ?? '1');
$separator = $hw_theme['header_logo_separator_char'] ?? '';
$wedding = hw()->getWedding();
$hotel = hw_get_hotel();
?>
			<footer id="footer" class="site-footer ">
				
				<div class="inner">

					<div id="footer-top" class="py-5">
					
						<div class="container">
						
							<div class="row align-items-center">
								<div class="col-sm-4 text-center text-md-left">
									<?= $wedding->getFormattedDate('l F d, Y') ?>
									<?php if( !is_wp_error($hotel) ): ?>
										<br>
										<?= $hotel['name'] ?? '' ?>
									<?php endif; ?>
								</div>
								<div class="col-sm-4 text-center">
									<div class="footer-logo">
										<?= $wedding->getHerName() ?>
										<br>
										<?php if($display_separator && !empty($separator)): ?>
											<small class="separator">&</small>
										<?php endif; ?>
										<br>
										<?= $wedding->getHisName() ?>
									</div>
								</div>
								<div class="col-sm-4 text-center text-md-right mt-5 mt-md-0">
									<?php /* tenant-support */ $hs = function_exists('hw_tenant_support') ? hw_tenant_support() : array('email' => get_option('admin_email'), 'phone' => '', 'facebook' => '', 'instagram' => '', 'twitter' => '', 'pinterest' => ''); ?><?php if ($hs['phone'] !== ''): ?><p class="mb-0"><a href="tel:<?= esc_attr(preg_replace('/[^0-9+]/', '', $hs['phone'])) ?>"><?= esc_html($hs['phone']) ?></a></p><?php endif; ?>
									<p class="mb-0"><a href="mailto:<?= esc_attr($hs['email']) ?>"><?= esc_html($hs['email']) ?></a></p>
									<?= get_template_part('template-parts/partials/social-icons') ?>
								</div>
							</div>
						
						</div><!-- container-->
					
					</div><!-- #footer-top -->

					<div id="footer-bottom" class="pt-4">
						<div class="container">
							<div class="row align-items-center">
								<div class="col-sm-6">
								<?php
									if( has_nav_menu( 'main-menu' ) ):
												
										wp_nav_menu([
											'theme_location' => 'main-menu',
											'menu_class' => 'flex-column flex-md-row text-center text-md-left',
											'container_class' => ''
										]);
										
									endif;
									?>
								</div>
								<div class="footer-copyright col-sm-6 text-center text-md-right">
										
									<?= sprintf('© %s All rights Reserved. <a target="_blank" href="%s">Design by Hola Weddings</a>',  date('Y'), 'https://holaweddings.com'); ?>
		
									<?php
										if ( function_exists( 'the_privacy_policy_link' ) ) {
											the_privacy_policy_link( '', '<span role="separator" aria-hidden="true"></span>' );
										}
									?>
								</div><!-- .site-info -->
							</div>
						</div>
					</div>
				</div><!-- .inner -->
				
			</footer><!-- #footer -->
		</div><!-- #body-wrapper -->
		
		<?php wp_footer(); ?>
		
	</body>
</html>
