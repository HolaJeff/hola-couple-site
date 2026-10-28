<?php

$is_administrator = hw_user_has_role('administrator');

// Per-page limit — admins can override via the ?per_page dropdown; everyone else gets the default.
$allowed_per_page = [ 20, 40, 100, 200, 500 ];
$per_page         = 20;

if ( $is_administrator && isset( $_GET['per_page'] ) ) {

    $requested = sanitize_text_field( $_GET['per_page'] );

    if ( $requested === 'all' ) {
        $per_page = -1;
    } elseif ( in_array( (int) $requested, $allowed_per_page, true ) ) {
        $per_page = (int) $requested;
    }
}

// 'All' disables pagination, so pin to the first page.
$paged = ( $per_page === -1 ) ? 1 : max( 1, (int) get_query_var('paged'), (int) get_query_var('page') );

$quotes = hw_get_user_quotes( $per_page, $paged );
?>
<h2><?= __('Your Reservations', 'hola-weddings') ?></h2>
<?php if($quotes->have_posts()):

    $total = (int) $quotes->found_posts;

    if ( $per_page === -1 ) {
        $count_label = sprintf( _n( '%s reservation', '%s reservations', $total, 'hola-weddings' ), number_format_i18n( $total ) );
    } else {
        $start       = ( $paged - 1 ) * $per_page + 1;
        $end         = $start + $quotes->post_count - 1;
        $count_label = sprintf( __( 'Showing %1$s–%2$s of %3$s', 'hola-weddings' ), number_format_i18n( $start ), number_format_i18n( $end ), number_format_i18n( $total ) );
    }
    ?>
    <div class="reservations-header">
        <div class="reservations-header__count"><?= esc_html( $count_label ) ?></div>

        <?php if( $is_administrator ):
            $per_page_options = [ 'all' => __( 'All', 'hola-weddings' ), 20 => '20', 40 => '40', 100 => '100', 200 => '200', 500 => '500' ];
            $current_option   = ( $per_page === -1 ) ? 'all' : $per_page;
            ?>
            <div class="reservations-header__perpage">
                <label for="reservations-per-page"><?= __( 'Per page', 'hola-weddings' ) ?></label>
                <select id="reservations-per-page" class="form-control" onchange="window.location.href = this.value;">
                    <?php foreach( $per_page_options as $value => $label ):
                        $url = add_query_arg( 'per_page', $value, home_url( '/account/reservations/' ) );
                        ?>
                        <option value="<?= esc_url( $url ) ?>" <?= selected( $current_option, $value, false ) ?>><?= esc_html( $label ) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
    </div>

    <div id="quote-list">
        <?php while($quotes->have_posts()): 

            $quotes->the_post();

            $ci = get_post_meta(get_the_ID(), 'hw_checkin', true);
            $co = get_post_meta(get_the_ID(), 'hw_checkout', true);
            $guests = get_post_meta(get_the_ID(), 'hw_guests', true);
            $main_guest = isset($guests[0]) ? $guests[0] : $guests[1]; // fix wrongly saved guest index
            $status = hw_get_quote_status();
            $room = hw_get_room(hw_get_quote_room_id());
            $upcoming_invoice = hw_get_quote_upcoming_invoice(get_the_ID());
            $model = hw_get_quote(get_the_ID());
            $insurance_plan = $model->getInsurancePlan();

            $ci_dt = $ci ? new \DateTime($ci) : null;
            $co_dt = $co ? new \DateTime($co) : null;
            ?>
            <div class="card card__reservation">
                <div class="card__body d-flex flex-column flex-md-row">
                    <!-- Thumb -->
                    <div class="reservation__thumb position-relative" style="background-image:url(<?= esc_url($room['main_image']) ?>)">
                        <!-- Mobile Status -->    
                        <span class="absolute mt-2 ml-2 d-md-none reservation__status <?= esc_attr($status) ?>"><?= hw_quote_status_label($status) ?></span>
                    </div>
                    <!-- Overview -->
                    <div class="reservation__overview position-relative flex-grow-1">
                        <div class="reservation__header d-flex align-items-center">
                            <!-- Desktop Status -->    
                            <span class="d-none d-md-inline-flex reservation__status <?= esc_attr($status) ?>"><?= hw_quote_status_label($status) ?></span>
                            <!-- Admin Meta -->
                            <?php if($is_administrator): ?>
                                <div class="reservation__admin-meta-wrapper ml-md-auto d-flex align-items-center flex-wrap">
                                    <?php do_action('hw_account_reservations_before_admin_meta') ?>
                                    
                                    <!-- Owner -->
                                    <span class="reservation__meta-item reservation__owner">
                                        <i class="far fa-user-circle"></i>
                                        <?= sprintf(__('Owner: %s', 'hola-weddings'), get_the_author()) ?>
                                    </span>

                                    <!-- Edit Link -->
                                    <a href="<?= get_edit_post_link() ?> " target="_blank" class="reservation__meta-item">
                                        <i class="fas fa-pen"></i>
                                        <?= __('Edit', 'hola-weddings') ?>
                                    </a>

                                    <?php do_action('hw_account_reservations_after_admin_meta') ?>
                                </div>
                            <?php endif; ?> 
                        </div>
                        <div class="reservation__body">
                            <!-- Title -->
                            <h3 class="reservation__title"><?= get_the_title() ?></h3>
                            <!-- Facts -->
                            <div class="reservation__facts flex-column flex-md-wrap flex-md-row mt-3">
                                <div class="reservation__fact">
                                    <span class="reservation__fact-icon"><i class="fas fa-bed"></i></span>
                                    <span class="reservation__fact-text">
                                        <span class="reservation__fact-label"><?= __('Room Type', 'hola-weddings') ?></span>
                                        <span class="reservation__fact-value"><?= esc_html($room['name']) ?></span>
                                    </span>
                                </div>
                                <div class="reservation__fact">
                                    <span class="reservation__fact-icon"><i class="far fa-calendar-check"></i></span>
                                    <span class="reservation__fact-text">
                                        <span class="reservation__fact-label"><?= __('Travel Dates', 'hola-weddings') ?></span>
                                        <span class="reservation__fact-value"><?= $ci_dt ? $ci_dt->format('M j, Y') : '—' ?> → <?= $co_dt ? $co_dt->format('M j, Y') : '—' ?></span>
                                    </span>
                                </div>
                                <div class="reservation__fact">
                                    <span class="reservation__fact-icon"><i class="fas fa-users"></i></span>
                                    <span class="reservation__fact-text">
                                        <span class="reservation__fact-label"><?= __('Guests', 'hola-weddings') ?></span>
                                        <span class="reservation__fact-value dropdown">
                                            <span class="reservation__guests-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
                                                <?= sprintf(_n('%d guest', '%d guests', count($guests), 'hola-weddings'), count($guests)) ?>
                                                <i class="fas fa-chevron-down"></i>
                                            </span>
                                            <div class="dropdown-menu">
                                                <?php foreach($guests as $gi => $guest): ?>
                                                    <li class="dropdown-item <?= $gi < count($guests) - 1 ? 'border-bottom' : '' ?>">
                                                        <?= esc_html(sprintf('%s %s', $guest['title'], $guest['name'])) ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </div>
                                        </span>
                                    </span>
                                </div>
                            </div>
                            <?php do_action('hw_account_reservation_after_title') ?>
                        </div>

                        <!-- Upcoming Payment -->
                        <?php hw_get_template('template-parts/account/partials/upcoming-payment-alert', ['invoice' => $upcoming_invoice]); ?>

                        <!-- Actions -->
                        <div class="card__footer">
                            <div class="reservation__actions ml-md-auto">
                                <a class="btn btn-primary" href="<?= hw_get_reservation_permalink(get_the_ID()) ?>">
                                    <i class="far fa-eye mr-1"></i>
                                    <?= __('View Details', 'hola-weddings' ) ?>
                                </a>
                            </div>
                        </div>
                    </div>

                    
                </div>
            </div>
        <?php endwhile; wp_reset_postdata() ?>
    </div>

    <?php if( $quotes->max_num_pages > 1 ):
        $pagination_args = [
            'base'      => home_url( '/account/reservations/' ) . '%_%',
            'format'    => '?paged=%#%',
            'current'   => $paged,
            'total'     => $quotes->max_num_pages,
            'type'      => 'array',
            'mid_size'  => 1,
            'end_size'  => 1,
            'prev_text' => '<i class="fas fa-chevron-left"></i>',
            'next_text' => '<i class="fas fa-chevron-right"></i>',
        ];

        // Keep the selected per-page limit across pages.
        if ( $per_page !== 20 && $per_page !== -1 ) {
            $pagination_args['add_args'] = [ 'per_page' => $per_page ];
        }

        $links = paginate_links( $pagination_args );
        ?>
        <nav aria-label="<?= esc_attr__( 'Reservations pages', 'hola-weddings' ) ?>">
            <ul class="pagination justify-content-center mt-4 mb-0">
                <?php foreach( $links as $link ):
                    $classes = 'page-item';
                    if ( strpos( $link, 'current' ) !== false ) { $classes .= ' active'; }
                    if ( strpos( $link, 'dots' ) !== false ) { $classes .= ' disabled'; }
                    $link = str_replace( 'page-numbers', 'page-link', $link );
                    ?>
                    <li class="<?= $classes ?>"><?= $link ?></li>
                <?php endforeach; ?>
            </ul>
        </nav>
    <?php endif; ?>
<?php else: ?>
    <div class="reservations-empty">
        <div class="reservations-empty__icon"><i class="far fa-calendar"></i></div>
        <div class="reservations-empty__title"><?= __('No reservations yet', 'hola-weddings') ?></div>
        <p><?= __("You don't have any reservation yet.", 'hola-weddings') ?></p>
    </div>
<?php endif;

do_action('hw_account_reservations_view_end');