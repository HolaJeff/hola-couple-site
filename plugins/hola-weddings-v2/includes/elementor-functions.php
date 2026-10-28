<?php

function add_elementor_widget_categories( $elements_manager ) {

	$elements_manager->add_category(
		'hola',
		[
			'title' => esc_html__( 'Hola Weddings', 'hola-weddings' ),
			'icon' => 'fas fa-rings-wedding',
		]
	);
	
	$elements_manager->add_category(
		'hola-hotel',
		[
			'title' => esc_html__( 'Hola Hotel', 'hola-weddings' ),
			'icon' => 'fas fa-rings-wedding',
		]
	);

}
add_action( 'elementor/elements/categories_registered', 'add_elementor_widget_categories' );