
			<footer id="footer" class="site-footer ">
				
				<div class="inner">
					
					<div class="container">
					
						<div class="row">
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
							<div class="col-sm-6 text-center text-md-right mt-5 mt-md-0">
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
							</div>
						</div>
						
						<hr class="mt-5 mb-3" style="border-color: #fafafa;">
						
						<div class="row">
							<div class="footer-copyright col-sm-6 text-center text-md-left">
								
								<?= sprintf('© 2021 All rights Reserved. <a target="_blank" href="%s">Design by Hola Weddings</a>', 'https://holaweddings.com'); ?>
	
								<?php
									if ( function_exists( 'the_privacy_policy_link' ) ) {
										the_privacy_policy_link( '', '<span role="separator" aria-hidden="true"></span>' );
									}
								?>
							</div><!-- .site-info -->
							<div class="col-sm-6">
								<ul class="nav justify-content-center justify-content-md-end">
									<li class="nav-item">
										<a class="nav-link" href="https://www.facebook.com/holaweddings/">
											<i class="fab fa-facebook"></i>
										</a>
									</li>
									<li class="nav-item">
										<a class="nav-link" href="https://twitter.com/holaweddings?lang=en">
											<i class="fab fa-twitter"></i>
										</a>
									</li>
									<li class="nav-item">
										<a class="nav-link" href="https://www.instagram.com/holawedding/?hl=en">
											<i class="fab fa-instagram"></i>
										</a>
									</li>
									<li class="nav-item">
										<a class="nav-link" href="https://www.pinterest.com.mx/holaweddings/">
											<i class="fab fa-pinterest"></i>
										</a>
									</li>
								</ul>
							</div>
						</div>
					
					</div><!-- container-->
				
				</div><!-- inner -->
				
			</footer><!-- #footer -->
		</div><!-- #body-wrapper -->
		
		<?php wp_footer(); ?>
		
	</body>
</html>
