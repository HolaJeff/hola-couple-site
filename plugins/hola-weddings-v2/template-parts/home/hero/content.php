<?php
/**
 * Hero content area.
 *
 * Desktop layout is driven by the `layout` ACF sub-field:
 *  - full : single centered column (default legacy behaviour)
 *  - 2_1  : two columns, 2/3 left + 1/3 right
 *  - 1_1  : two columns, 1/3 left + 2/3 right
 *
 * In the two-column layouts the hero content lives in the larger column and the
 * booking search bar (forced vertical) lives in the smaller one. Mobile always
 * stacks to a single column with the content first.
 *
 * @var array $options Hero ACF group values.
 */

$layout = $options['layout'] ?? 'full';

// Search bar defaults to off: only an explicit true shows it.
$show_search = ! empty( $options['display_search_bar'] );

// Search bar orientation for the full-width layout (the two-column layouts force
// vertical). The field already stores 'horizontal'|'vertical'.
$search_bar_layout = $options['search_bar_layout'] ?? 'horizontal';

// Wrap the hero content in a boxed `container` (default) or a full-width
// `container-fluid` when add_container is switched off.
$add_container   = ! isset( $options['add_container'] ) || $options['add_container'];
$container_class = $add_container ? 'container' : 'container-fluid';

// Horizontal alignment of the (vertically stacked) hero content. The field
// stores modern flex values (start|center|end); the content column is a flex
// column, so `align-items-*` is the axis that maps to Left/Center/Right.
// Bootstrap 4.6 has no text-start/text-end, so map to its text-* utilities too.
$justify = $options['justify_content'] ?? 'center';
$justify = in_array( $justify, array( 'start', 'center', 'end' ), true ) ? $justify : 'center';

$text_class = array(
    'start'  => 'text-left',
    'center' => 'text-center',
    'end'    => 'text-right',
)[ $justify ];

$inner_class = 'd-flex flex-column align-items-' . $justify . ' ' . $text_class;

// [left span, right span] on Bootstrap's 12-column grid + which side is the larger
// (content) column. Anything not listed here falls back to the full-width layout.
$layouts = array(
    '2_1' => array( 'left' => 8, 'right' => 4, 'content' => 'left' ),
    '1_1' => array( 'left' => 4, 'right' => 8, 'content' => 'right' ),
);

// Full-width single column only for the 'full' layout. The defined columns are
// kept even when the search bar is hidden — its column just renders empty.
if ( ! isset( $layouts[ $layout ] ) ) :
    ?>
    <div class="<?= $container_class ?> pl-lg-0 position-relative">
        <div class="<?= $inner_class ?>">
            <?php do_action( 'hw_home_hero_content', $options ) ?>

            <?php /* Search bar at the end of the content, orientation per field. */ ?>
            <?php if ( $show_search ) : ?>
                <div class="hero-content-search w-100 hero-content-search--<?= esc_attr( $search_bar_layout ) ?> mt-4">
                    <?= do_shortcode( '[hw-search-bar layout="' . esc_attr( $search_bar_layout ) . '"]' ) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return;
endif;

$cols        = $layouts[ $layout ];
$content_col = $cols['content'];                          // 'left' | 'right'
$search_col  = 'left' === $content_col ? 'right' : 'left';
?>
<div class="<?= $container_class ?> position-relative">
    <div class="row align-items-center hero-columns hero-columns--<?= esc_attr( $layout ) ?>">

        <?php /* Hero content — the larger column. */ ?>
        <div class="col-12 col-lg-<?= $cols[ $content_col ] ?> order-lg-<?= 'left' === $content_col ? 1 : 2 ?> hero-col hero-col--content">
            <div class="<?= $inner_class ?>">
                <?php do_action( 'hw_home_hero_content', $options ) ?>
            </div>
        </div>

        <?php /* Booking search bar — the smaller column, forced vertical. */ ?>
        <div class="col-12 col-lg-<?= $cols[ $search_col ] ?> order-lg-<?= 'left' === $search_col ? 1 : 2 ?> hero-col hero-col--search">
            <?php if ( $show_search ) : ?>
                <?= do_shortcode( '[hw-search-bar layout="vertical"]' ) ?>
            <?php endif; ?>
        </div>

    </div>
</div>
