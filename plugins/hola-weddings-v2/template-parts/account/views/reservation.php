<?php

global $post;

$reservation = hw_get_quote(get_the_ID());
$status = hw_get_quote_status();
$is_administrator = hw_user_has_role('administrator');
$tabs = hw_get_single_reservation_tabs();
?>
<div id="reservation-<?= get_the_ID() ?>" class="reservation">

    <div class="d-flex flex-wrap flex-lg-nowrap mb-3">
        <div>
            <h2 class="mb-0 d-flex flex-column-reverse flex-md-row">
                <?= get_the_title() //sprintf(__('Reservation #%s', 'hola-weddings'), get_the_ID()) ?>
                <div class="align-center mb-2 mb-md-0 ml-md-3">
                    <span class="status <?= $status ?>"><?= hw_quote_status_label($status) ?></span>
                </div>
            </h2>
            
            <?php do_action('hw_account_reservation_after_title') ?>
            
        </div>
        <div class="actions text-md-right flex-fill">
            <?php if($is_administrator): ?>
                <span class="d-inline-block p-md-3 mr-2 mt-2 mt-md-0">
                    <i class="far fa-user-circle"></i>
                    <?= sprintf(__('Owner: %s'), get_the_author_meta('display_name', $post->post_author)) ?>
                </span>
            <?php endif; ?>
            <!-- <a class="btn btn-light" target="_blank" href="<?= hw_get_reservation_pdf_permalink(get_the_ID()) ?>">
                <i class="mdi mdi-file-pdf-box"></i>
                <?= __('View PDF', 'hola-weddings') ?>
            </a> -->
        </div>
    </div>

    <!-- tbas navigation -->
    <ul class="nav nav-tabs mb-3" id="reservationTabs" role="tablist">
        <?php foreach($tabs as $key => $tab): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $key == 'details' ? 'active' : '' ?>" id="<?= $key ?>-tab" data-toggle="tab" data-target="#<?= $key ?>" type="button" role="tab" aria-controls="<?= $key ?>" aria-selected="true">
                    <?= $tab['title'] ?>
                </button>
            </li>
        <?php endforeach; ?>
    </ul>

    <!-- tabs and sidebar -->
    <div class="row">
        <div class="col-lg-8">

            <div class="reservation-container"> 
                <div class="tab-content" id="myTabContent">
                    <?php foreach($tabs as $key => $tab): ?>
                        <div class="tab-pane fade <?= $key == 'details' ? 'show active' : '' ?>" id="<?= $key ?>" role="tabpanel" aria-labelledby="<?= $key ?>-tab">
                            <?php hw_get_template('template-parts/account/views/reservation/' . $key, ['quote' => $reservation] ); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
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