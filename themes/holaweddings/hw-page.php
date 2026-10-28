<?php
/**
 * The template for displaying archive pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package WordPress
 * @subpackage Holawedding
 * @since 1.0.0
 */

$route = get_query_var('view', false);

get_header();
?>

	<div id="primary" class="content-area">
		<main id="main" class="site-main">
			<div class="inner">
                <?= hw_get_template( 'endpoints/' . $route ); ?>
			</div>
		</main><!-- #main -->
	</div><!-- #primary -->

<?php
get_footer();