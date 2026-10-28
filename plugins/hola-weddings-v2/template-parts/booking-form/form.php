<?php

global $wp;

$hotel = (object) hw_get_hotel();
$wedding = hw()->getWedding();
$date = hw_get_date(true);
$payment_enabled = is_payment_enabled();

$errors = hw_get_cart_errors();
$data = hw_get_submitted_data();

$is_greetings = isset($_GET['greetings']);
$greeting = $is_greetings ? $_GET['greetings'] : '';

$idata = hw_get_cart_search_data();

$proceed = false;
if(hw_validate_cart_search($idata) !== false){
    
    $proceed = true;
}

if($is_greetings): ?>

    <?php if($greeting == 1): // OLD: Congratulations, your booking is confirmed. You will receive a confirmation by email shortly. ?>
        <div class="alert alert-success"><?= __('Thanks for submitting your reservation. Please allow 48 hours for processing, Monday through Saturday. You will receive an email confirmation once it has been approved.', 'hola-weddings') ?></div>
    <?php endif; ?>
    
    <?php if($greeting == 2): // OLD: Your request was received successfully and the room will be requested. An agent will be in touch with you shortly to confirm the reservartion. ?>
        <div class="alert alert-success"><?= __('Thanks for submitting your reservation. Please allow 48 hours for processing, Monday through Saturday. You will receive an email confirmation once it has been approved.' , 'hola-weddings') ?></div> 
    <?php endif; ?>
    
<?php endif; ?>

<div id="booking-form" class="row">
    <div class="col-lg-8">
        
        <?php if(!empty($errors)): ?>
            <ul class="alert alert-danger">
                <?php foreach($errors as $error ): ?>
                    <li><?= $error ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <!-- User Login Notice -->
        <?php if(!is_user_logged_in() && !hw_hide_form_login_cta()): ?>
            <div class="login-alert alert alert-primary d-flex flex-row align-items-center mt-3 p-3">
                <div class="mb-0 fw-bold"><?= __('Do you already have an account?', 'hola-weddings') ?></div>
                <button class="btn btn-info ml-auto" type="button" data-toggle="modal" data-target="#loginModal"><?= __('Log In', 'hola-weddings') ?></button>
            </div>
        <?php endif; ?>
        
        <?= hw_get_template('template-parts/booking-form/form-search', ['idata' => $idata, 'hotel' => $hotel]) ?>
        
        <?php if($proceed):
            
            $products = hw_get_cart_products($idata);   
            
            if(empty($products)): ?>
                <p class="alert alert-warning"><?= __('Ops! There are no rates available for your selection', 'hola-weddings') ?></p>
            <?php else: ?>
                <form id="quoteForm" method="post" class="<?= hw_get_mode() == 'room' ? 'room-mode' : 'package-mode' ?>" action="<?= home_url( $wp->request ) ?>?ci=<?= $idata['ci'] ?>&co=<?= $idata['co'] ?>&adults=<?= $idata['adults'] ?>&children=<?= $idata['children'] ?>&teens=<?= $idata['teens'] ?>">
                    
                    <!-- Room -->
                    <div class="card room p-3 mt-3">
                        <h4><?= hw_get_mode() == 'room' ? __('Select Room', 'hola-weddings') : __('Select Package', 'hola-weddings') ?></h4>
                        <?php if(hw_get_mode() == 'room'): ?>
                            <?= hw_get_template('template-parts/booking-form/form-rooms', ['products' => $products, 'idata' => $idata, 'errors' => $errors, 'data' => $data]) ?>
                        <?php else: ?>
                            <?= hw_get_template('template-parts/booking-form/form-packages', ['products' => $products, 'idata' => $idata, 'errors' => $errors, 'data' => $data]) ?>
                        <?php endif; ?>
                    </div>

                    <!-- Guests information -->
                    <div id="guests" class="mt-3 card pt-3">
                        <h4 class="px-3 border-bottom mb-3 pb-3"><?= __('Guest Information', 'hola-weddings') ?></h4>
                        <?= hw_get_template('template-parts/booking-form/form-guests', ['idata' => $idata, 'errors' => $errors, 'data' => $data]) ?>
                    </div>

                    <?php if(hw_is_additional_notes_enabled()):
                        $label = hw_get_additional_notes_label();
                        ?>
                        <!-- Additional notes -->
                        <div id="additional-notes" class="mt-3 card p-3">
                            <h4><?= $label ?> <?= hw_is_additional_notes_is_required() ? sprintf('( %s )', __('Required', 'hola-weddings')) : '' ?></h4>
                            <?= hw_get_template('template-parts/booking-form/form-additional-notes', [ 'errors' => $errors, 'data' => $data]) ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Guest preferences: bed type, optional transfers -->
                    <?= hw_get_template('template-parts/booking-form/form-preferences', ['errors' => $errors, 'data' => $data]) ?>

                    <!-- Customer -->
                    <div class="mt-3 card p-3">
                        <h4><?= $payment_enabled ? __('Card Holder Information', 'hola-weddings') : __('Main Contact Information', 'hola-weddings') ?></h4>
                        <?= hw_get_template('template-parts/booking-form/form-billing', ['errors' => $errors, 'data' => $data]) ?>
                    </div>
                    
                    <!-- Insurance -->  
                    <?= hw_get_template('template-parts/booking-form/form-insurance', ['errors' => $errors, 'data' => $data]) ?>

                    <!-- Payment -->       
                    <?= hw_get_template('template-parts/booking-form/form-payment', ['errors' => $errors, 'data' => $data]) ?>
    
                    <!-- Cancellation & Terms -->    
                    <div class="card mt-3 p-3">
                        <?= hw_get_template('template-parts/booking-form/form-terms-conditions', ['errors' => $errors, 'data' => $data]) ?>
                    </div>
                    
                    <!-- Submit button -->
                    <div class="text-right mt-3">
                        <button id="submit-btn" type="submit" class="btn btn-info">
                            <?= __('Submit', 'hola-weddings') ?>
                        </button>
                    </div>
                    
                    <input type="hidden" name="action" value="hw_process_quote" />
                    <input type="hidden" name="checkin" value="<?= $idata['ci'] ?>" />
                    <input type="hidden" name="checkout" value="<?= $idata['co'] ?>" />
                    <input type="hidden" name="adults" value="<?= $idata['adults'] ?>" />
                    <input type="hidden" name="teens" value="<?= $idata['teens'] ?>" />
                    <input type="hidden" name="children" value="<?= $idata['children'] ?>" />
                    <input type="hidden" name="infants" value="<?= $idata['infants'] ?>" />
                    <?php wp_nonce_field( 'hw-process-quote' ); ?>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <!-- Sidebar -->
    <div id="sidebar" class="col-lg-4 mt-lg-0 d-none d-lg-block">
        <div class="inner h-100">
            <div class="card hotel-card d-none d-lg-block mb-3">
                <div class="hotel__image" style="background-image: url('<?=$hotel->main_image ?>');"></div>
                <div class="hotel__content p-3">
                    <h4 class="hotel__name"><?= $hotel->name ?></h4>
                    <div><?= $wedding->getWeddingName('&') ?></div>
                    <div>
                        <?php if($date): ?>
                            <?= $date ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="card cart-card p-3">
                <h4 class="mb-3"><?= __('Your Reservation', 'hola-weddings') ?></h4>
                <div data-cart class="cart-holder">
                    <p><?= __('No rooms selected', 'hola-weddings') ?></p>
                </div>
            </div>
        </div>
    </div>

    <?php do_action('hw_after_booking_form'); ?>
</div>