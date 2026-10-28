<?php
global $hw_theme;

$display_separator = hw_evaluate_string_to_boolean($hw_theme['header_logo_display_separator'] ?? '1');
$separator = $hw_theme['header_logo_separator_char'] ?? '';
$wedding = hw()->getWedding();
?>
			<footer id="footer" class="site-footer ">
				
				<div class="upper-footer">
					
					<div class="container">
					
						<div class="row align-items-center">
							<div class="column col-sm-4">
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
							<div class="column col-sm-4 text-center">
								<div class="footer-logo">
									<div class="name"><?= $wedding->getHerName() ?></div>
									<?php if($display_separator && !empty($separator)): ?>
										<div class="separator"><?= $separator ?></div>
									<?php endif; ?>
									<div class="name"><?= $wedding->getHisName() ?></div>
								</div>
							</div>
							<div class="column col-sm-4 text-center text-md-right mt-5 mt-md-0">
								<p class="mb-0">
									<a href="tel: <?= '1-888-472-7077' ?>">
										1-888-472-7077
									</a>
								</p>
								<p class="mb-0">
									<a href="mailto: hola@holaweddings.com">
										hola@holaweddings.com
									</a>
								</p>
								<p>
									<?= get_template_part('template-parts/partials/social-icons') ?>
								</p>
							</div>
						</div>
					
					</div><!-- container-->
				
				</div><!-- uppper footer -->

				<div class="lower-footer py-3">
					<div class="container">
						<div class="footer-copyright text-center">
							
							<small class="copyright">
								<?= sprintf('© %s All rights Reserved. <a target="_blank" href="%s">Design by Hola Weddings</a>', date('Y'), 'https://holaweddings.com'); ?>

								<?php
									if ( function_exists( 'the_privacy_policy_link' ) ) {
										the_privacy_policy_link( '', '<span role="separator" aria-hidden="true"></span>' );
									}
								?>
							</small>
						</div><!-- .site-info -->
					</div>
				</div>
			
			</footer><!-- #footer -->
		</div><!-- #body-wrapper -->
		
		<?php wp_footer(); ?>
		
	</body>
</html>
