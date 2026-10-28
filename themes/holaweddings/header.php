<!DOCTYPE html>
<html class="no-js" <?php language_attributes(); ?>>

	<head>

		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1.0" >

		<link rel="profile" href="https://gmpg.org/xfn/11">

		<?php wp_head(); ?>
		
		<link rel="preconnect" href="https://fonts.googleapis.com">
		<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
		<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

	</head>

	<body <?php body_class(); ?>>
	
		<div id="body-wrapper">

			<?php wp_body_open(); ?>
	
			<header id="header" class="header-footer-group position-absolute w-100" role="banner">
				
					<div class="header-inner">
						
						<div class="clearfix">
							
							<div class="container" >
							
								
								<div class="main-nav clearfix" role="navigation">
							
									<?php get_template_part('template-parts/header/main-nav'); ?>
															
								</div><!-- .main-nav-->
								
							</div><!-- Container -->
							
						</div><!-- .top-header -->

					</div><!-- header-inner -->
	
			</header><!-- #site-header -->
			
	