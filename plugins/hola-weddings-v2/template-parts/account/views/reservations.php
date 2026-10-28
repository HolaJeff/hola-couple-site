<?php

use HW\Models\Quote;

$quotes = hw_get_user_quotes();
$i = 1;

$is_administrator = hw_user_has_role('administrator');
?>
<h2><?= __('Your Reservations', 'hola-weddings') ?></h2>
<?php if($quotes->have_posts()): ?>
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
            ?>
            <div class="quote mb-4">
                <div class="inner d-flex flex-column flex-md-row flex-nowrap align-items-stretch">
                    <div class="picture" style="background-image:url(<?= $room['main_image'] ?>)">
                    </div>
                    <div class="details">
                        <div class="d-flex flex-column flex-md-row room-type align-items-center">

                            <!-- status -->
                            <div class="mb-2 mb-md-0">
                                <span class="status <?= $status ?> align-center d-inline-block d-md-inline mr-3">
                                    <?= hw_quote_status_label($status) ?>
                                </span>
                            </div>

                            <!-- title -->
                            <div class="mb-2 mb-md-0">
                                <div class="mb-0 title font-weight-bold text-uppercase"><?= get_the_title() ?></div>
                                <?php do_action('hw_account_reservation_after_title') ?>
                            </div>
                            
                            <!-- actions -->
                            <div class="actions text-right ml-md-auto">
                                <?php if($is_administrator): ?>
                                    <span class="d-inline-block p-3 mr-2">
                                        <i class="far fa-user-circle"></i>
                                        <?= sprintf(__('Owner: %s'), get_the_author()) ?>
                                    </span>
                                <?php endif; ?>
                                <a class="btn btn-primary" href="<?= hw_get_reservation_permalink(get_the_ID()) ?>">
                                    <i class="far fa-eye"></i>
                                    <?= __('View Details', 'hola-weddings' ) ?>
                                </a>
                            </div>
                        </div>
                        <div class="row mt-4">
                            <div class="col-md-12 col-lg-4 mb-3 mb-lg-0"><!-- details -->
                                <div class="bg-light rounded p-3 p-md-0 h-100">
                                    <div class="d-flex flex-wrap">
                                        <div class="main-guest info col-12">
                                            <span class="label"><?= __('Room', 'hola-weddings') ?></span>
                                            <div class="value"><?= $room['name'] ?></div>
                                        </div>
                                        <div class="main-guest info col-7">
                                            <span class="label"><?= __('Main Guest', 'hola-weddings') ?></span>
                                            <div class="value"><?= ucwords(hw_guest_full_name($main_guest)) ?></div>
                                        </div>
                                        <div class="guests info col-5">
                                            <span class="label"><?= __('Guests', 'hola-weddings') ?></span>
                                            <div class="value dropdown" data-toggle="tooltip" data-placement="top"  title="">
                                                <span class="dropdown-toggle" data-toggle="dropdown" style="text-decoration: underline dotted;">
                                                    <?= sprintf('%d (View All)', count($guests)) ?>
                                                </span>
                                                <div class="dropdown-menu">
                                                    <?php foreach($guests as $i => $guest): ?>
                                                        <li class="dropdown-item <?= $i < count($guests) - 1 ? 'border-bottom' : '' ?>">
                                                            <?= sprintf('%s %s', $guest['title'], $guest['name']) ?>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="dates info col-7">
                                            <span class="label"><?= __('Check-in', 'hola-weddings') ?></span>
                                            <div class="value"><?=  (new \DateTime($ci))->format('m/d/Y') ?></div>
                                        </div>
                                        <div class="dates info col-5">
                                            <span class="label"><?= __('Check-out', 'hola-weddings') ?></span>
                                            <div class="value"><?= (new \DateTime($co))->format('m/d/Y') ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4 mb-3 mb-md-0"><!-- upcoming payment -->
                                <div class="bg-light rounded p-3 h-100">
                                    <div class="font-weight-bold text-uppercase mb-3 info">
                                        <span class="label">
                                            <?= __('Upcoming Payment', 'hola-weddings' ) ?>
                                        </span>
                                    </div>
                                    <?php if($upcoming_invoice): ?>
                                        <div>
                                            <?= $upcoming_invoice->getTitle() ?>
                                        </div>
                                        <div>
                                            <strong><?= __('Due Date', 'hola-weddings' ) ?>:</strong>
                                            <?= $upcoming_invoice->getDueDate('M d, Y') ?>
                                        </div>
                                        <div>
                                            <strong><?= __('Amount', 'hola-weddings' ) ?>:</strong>
                                            <?= hw_format_price( $upcoming_invoice->getDueAmount() ) ?>
                                        </div>
                                        <?php switch( hw_get_invoice_payment_btn_action () ):
                                            case 'payment-form': ?>
                                                <a class="d-block text-right text-underline mt-2" href="<?= hw_get_invoice_payment_permalink($upcoming_invoice->getID()) ?>" target="_blank">
                                                    <u>  
                                                        <?= __('Pay Invoice', 'hola-weddings' ) ?> >>
                                                    </u>
                                                </a>
                                            <?php break;
                                            case 'request-payment-link': ?>
                                                <?php hw_get_template('template-parts/account/partials/invoice-request-payment-link-btn', [ 'invoice' => $upcoming_invoice ]) ?>
                                            <?php break;
                                            endswitch; ?>
                                    <?php else: ?>
                                        <?= __('No Payment found', 'hola-weddings' ) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4"><!-- insurance -->
                                <div class="bg-light rounded p-3 h-100">
                                    <div class="font-weight-bold text-uppercase mb-3 info">
                                        <span class="label">
                                            <?= __('Insurance', 'hola-weddings' ) ?>
                                        </span>
                                    </div>
                                    <?php if($insurance_plan): ?>
                                        <?= $insurance_plan['name'] ?>
                                    <?php else: ?>
                                        <?= __('No Insurance', 'hola-weddings' ) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php $i++; endwhile; wp_reset_postdata() ?>
    </div>
<?php else: ?>
    <p><?= __('You don\'t have any reservation yet.', 'hola-weddings') ?></p>
<?php endif;

do_action('hw_account_reservations_view_end');