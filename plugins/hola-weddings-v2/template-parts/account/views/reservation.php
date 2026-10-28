<?php

global $post;

$reservation      = hw_get_quote(get_the_ID());
$status           = $reservation->getStatus();
$is_administrator = hw_user_has_role('administrator');
$tabs             = hw_get_single_reservation_tabs();

$upcoming_invoice = hw_get_quote_upcoming_invoice($reservation->getID());
$room   = $reservation->getRoom();
$guests = $reservation->getGuests();
$ci_dt     = $reservation->getCheckin();
$co_dt     = $reservation->getCheckout();
?>
<div id="reservation-<?= $reservation->getID() ?>" class="reservation">


    <!-- Upcoming Payment -->
    <?php hw_get_template('template-parts/account/partials/upcoming-payment-alert', ['invoice' => $upcoming_invoice, 'classes' => 'bg-white mb-4 p-md-3']); ?>
    
    <!-- Overview -->
    <div class="card card__reservation-overview">
        <div class="card__body d-flex flex-column flex-md-row align-items-md-start">

            <!-- Thumb -->
            <div class="reservation__thumb position-relative" style="background-image:url(<?= esc_url($room['main_image']) ?>)">
                <!-- Mobile status -->
                <span class="absolute mt-2 ml-2 d-md-none reservation__status <?= esc_attr($status) ?>"><?= hw_quote_status_label($status) ?></span>
            </div>

            <!-- Overview information -->
            <div class="position-relative flex-grow-1">
                <!-- Desktop status -->
                <span class="d-none d-md-inline-flex reservation__status <?= esc_attr($status) ?>"><?= hw_quote_status_label($status) ?></span>

                <h3 class="reservation__title"><?= get_the_title() ?></h3>

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
                            <span class="reservation__fact-label"><?= __('Occupation', 'hola-weddings') ?></span>
                            <span class="reservation__fact-value">
                                <?= sprintf( __('Adults: %s', 'hola-wedding'), $reservation->getAdults() ) ?>
                                <?= sprintf( __('Teens: %s', 'hola-wedding'), $reservation->getTeen() ) ?>
                                <?= sprintf( __('Children: %s', 'hola-wedding'), $reservation->getChildren() ) ?>
                                <?= sprintf( __('Infants: %s', 'hola-wedding'), $reservation->getInfants() ) ?>
                            </span>
                        </span>
                    </div>
                </div>

                <?php do_action('hw_account_reservation_after_title') ?>

                <?php if($is_administrator): ?>
                    <!-- Owner -->
                    <span class="reservation__owner">
                        <i class="far fa-user-circle"></i>
                        <?php
                        // Ownership lives on the meta — guest bookings have none.
                        $owner_id = hw_get_quote($post->ID)->getOwnerID();
                        ?>
                        <?= sprintf(
                            __('Owner: %s', 'hola-weddings'),
                            $owner_id ? get_the_author_meta('display_name', $owner_id) : __('Guest (no account)', 'hola-weddings')
                        ) ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Details + payment summary -->
    <div class="row">
        <div class="col-lg-8">
            <?php foreach($tabs as $key => $tab): ?>
                <div class="panel">
                    <div class="title">
                        <?php if( $tab['icon'] ): ?>
                            <span class="icon">
                                <i class="<?= $tab['icon'] ?>"></i>
                            </span>
                        <?php endif; ?>
                        <?= $tab['title'] ?>
                    </div>     
                    <div class="body">
                        <?php hw_get_template('template-parts/account/views/reservation/' . $key, ['quote' => $reservation]); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="col-lg-4">
            <?php hw_get_template('template-parts/account/views/reservation/payment', ['quote' => $reservation]); ?>
        </div>
    </div>

    <small class="text-muted d-block">
        <?= sprintf(__('Ref. ID #%s', 'hola-weddings'), get_the_ID()) ?>
    </small>
</div>

<?php do_action('hw_account_reservation_view_end') ?>
