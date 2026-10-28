<?php

namespace HW\Elementor\Widgets;

class SearchBar extends \Elementor\Widget_Base {

	public function get_name() {

		return 'hw_search_bar';
	}

	public function get_title() {
		return esc_html__( 'Booking Search Bar', 'hola-weddings' );
	}

	public function get_icon() {
		return 'eicon-search';
	}

	public function get_categories() {
		return [ 'hola' ];
	}

	public function get_keywords() {
		return [ 'hola', 'wedding', 'booking', 'search', 'availability' ];
	}

	public function get_style_depends() {
		return [ 'hw-main' ];
	}

	public function get_script_depends() {
		return [ 'hw-alpinejs', 'hw-search-bar' ];
	}

	/**
	 * Build the list of published pages for the page-select control.
	 *
	 * @return array
	 */
	protected function get_page_options() {

		$options = [ '' => esc_html__( 'Auto-detect booking page', 'hola-weddings' ) ];

		foreach ( get_pages( [ 'post_status' => 'publish' ] ) as $page ) {

			$options[ $page->ID ] = $page->post_title !== '' ? $page->post_title : sprintf( '#%d', $page->ID );
		}

		return $options;
	}

	/**
	 * Register widget controls.
	 *
	 * @access protected
	 */
	protected function register_controls() {

		$this->start_controls_section(
			'section_search_bar',
			[
				'label' => esc_html__( 'Booking Search Bar', 'hola-weddings' ),
			]
		);

		$this->add_control(
			'important_note',
			[
				'type'            => \Elementor\Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Display the booking search bar.', 'hola-weddings' ),
				'content_classes' => 'alert',
			]
		);

		$this->add_control(
			'layout',
			[
				'label'   => esc_html__( 'Layout', 'hola-weddings' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'horizontal',
				'options' => [
					'horizontal' => esc_html__( 'Horizontal', 'hola-weddings' ),
					'vertical'   => esc_html__( 'Vertical', 'hola-weddings' ),
				],
			]
		);

		$this->add_control(
			'page_id',
			[
				'label'       => esc_html__( 'Booking Page', 'hola-weddings' ),
				'type'        => \Elementor\Controls_Manager::SELECT2,
				'default'     => '',
				'options'     => $this->get_page_options(),
				'description' => esc_html__( 'Page to redirect to on search. Leave empty to auto-detect the booking-form page.', 'hola-weddings' ),
			]
		);

		$this->end_controls_section();

		$this->register_style_controls();
	}

	/**
	 * Register the Style-tab controls (container / fields / button).
	 *
	 * All selectors are scoped under `{{WRAPPER}} .hw-search-bar` so they
	 * out-specify the base `.hw-search-bar .sb-*` rules from _shortcodes.scss.
	 *
	 * @access protected
	 */
	protected function register_style_controls() {

		// ── Container ──────────────────────────────────────────────────────────
		$this->start_controls_section(
			'section_style_container',
			[
				'label' => esc_html__( 'Search Bar', 'hola-weddings' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'container_bg_color',
			[
				'label'     => esc_html__( 'Background Color', 'hola-weddings' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .hw-search-bar .sb-bar' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name'     => 'container_border',
				'selector' => '{{WRAPPER}} .hw-search-bar .sb-bar',
			]
		);

		$this->add_responsive_control(
			'container_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'hola-weddings' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .hw-search-bar .sb-bar' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'container_box_shadow',
				'selector' => '{{WRAPPER}} .hw-search-bar .sb-bar',
			]
		);

		$this->end_controls_section();

		// ── Fields ─────────────────────────────────────────────────────────────
		$this->start_controls_section(
			'section_style_fields',
			[
				'label' => esc_html__( 'Fields', 'hola-weddings' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'field_padding',
			[
				'label'      => esc_html__( 'Padding', 'hola-weddings' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .hw-search-bar .sb-field' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'field_label_heading',
			[
				'label'     => esc_html__( 'Label', 'hola-weddings' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'field_label_color',
			[
				'label'     => esc_html__( 'Label Color', 'hola-weddings' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .hw-search-bar .sb-label' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'field_label_font_size',
			[
				'label'      => esc_html__( 'Label Font Size', 'hola-weddings' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'range'      => [
					'px' => [ 'min' => 6, 'max' => 30 ],
				],
				'selectors'  => [
					'{{WRAPPER}} .hw-search-bar .sb-label' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'field_value_heading',
			[
				'label'     => esc_html__( 'Value', 'hola-weddings' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'field_value_color',
			[
				'label'     => esc_html__( 'Value Color', 'hola-weddings' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .hw-search-bar .sb-value' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'field_value_font_size',
			[
				'label'      => esc_html__( 'Value Font Size', 'hola-weddings' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'range'      => [
					'px' => [ 'min' => 8, 'max' => 40 ],
				],
				'selectors'  => [
					'{{WRAPPER}} .hw-search-bar .sb-value' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// ── Button ─────────────────────────────────────────────────────────────
		$this->start_controls_section(
			'section_style_button',
			[
				'label' => esc_html__( 'Button', 'hola-weddings' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'button_bg_color',
			[
				'label'     => esc_html__( 'Background Color', 'hola-weddings' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .hw-search-bar .sb-search-btn' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_text_color',
			[
				'label'     => esc_html__( 'Text Color', 'hola-weddings' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .hw-search-bar .sb-search-btn' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name'     => 'button_border',
				'selector' => '{{WRAPPER}} .hw-search-bar .sb-search-btn',
			]
		);

		$this->add_responsive_control(
			'button_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'hola-weddings' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .hw-search-bar .sb-search-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Render widget output on the frontend.
	 *
	 * @access protected
	 */
	protected function render() {

		$layout  = $this->get_settings_for_display( 'layout' );
		$page_id = $this->get_settings_for_display( 'page_id' );

		$layout = in_array( $layout, [ 'horizontal', 'vertical' ], true ) ? $layout : 'horizontal';

		$shortcode = sprintf(
			'[hw-search-bar layout="%s"%s]',
			esc_attr( $layout ),
			$page_id ? ' page_id="' . absint( $page_id ) . '"' : ''
		);

		?>
		<div class="elementor-hw-search-bar">
			<?= do_shortcode( $shortcode ) ?>
		</div>
		<?php
	}
}
