<!-- Terms and conditions -->
<?php
    $text = get_network_option(null, 'hw_global_terms_conditions');
	wp_editor(
		$text ?: '',
		'hw_global_terms_conditions',
		array(
            'textarea_name' => 'data[hw_global_terms_conditions]',
			'_content_editor_dfw' => false,
			'drag_drop_upload'    => true,
			'tabfocus_elements'   => 'content-html,save-post',
			'editor_height'       => 350,
			'tinymce'             => array(
				'resize'                  => false,
				'wp_autoresize_on'        => false,
				'add_unload_trigger'      => false,
			),
		)
	);