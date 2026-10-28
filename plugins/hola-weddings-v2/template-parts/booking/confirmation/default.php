<?php
$adults = $quote->getAdults();
$children = $quote->getChildren();
$teen = $quote->getTeen();
$status = $quote->getStatus();
?>
<div id="booking-confirmation">
    <div class="page-header">
        <div class="container">
            <h1 class="page-title text-center text-white mb-3">
                <?php 
                    switch($status){
                        case 'confirmed':
                            echo sprintf(__('Thanks %s, Your reservation is Confirmed!', 'hola-weddings'), $quote->getFirstName());
                            break;
                        case 'pending':
                            echo __('Your reservation is Pending!', 'hola-weddings');
                            break;
                    } 
                ?>
            </h1>
            <p class="mb-5 text-center" style="font-size: 1.3em; font-weight: 300">
                <?php 
                    switch($status){
                        case 'confirmed':
                            echo __('Your reservation has been received and is being processed.<br> You will receive your confirmation within 24-48 hours.', 'hola-weddings');
                            break;
                        case 'pending':
                            echo __('Your reservation has been received but needs a little extra work.<br> You will receive an email with the next steps within 48-hours.', 'hola-weddings');
                            break;
                    } 
                ?>
            </p>
            <div class="row">
                <div class="col-lg-8 offset-lg-2">
                <?php 
                    switch($status){
                        case 'confirmed':
                            echo '<iframe style="width: 100%; height: 400px" src="https://www.youtube.com/embed/aRKLE13XmzI?si=d-GKCFMoabxmmm7Z" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>';
                            break;
                        case 'pending':
                            echo '<iframe style="width: 100%; height: 400px" src="https://www.youtube.com/embed/joQpdbLJI-E?si=xujhtYrXzKTAheRL" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>';
                            break;
                    } 
                ?>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body pt-5">
        <div class="container">
            <h2 class="mb-3 fw-bold text-center"><?= __('Your Reservation', 'hola-weddings') ?></h2>
            <div class="text-center">
                <p class="alert alert-<?= $status == 'confirmed' ? 'success' : 'warning' ?> d-inline-block rounded-pill">
                    <?php 
                        switch($status){
                            case 'confirmed':
                                echo __('Confirmed', 'hola-weddings');;
                                break;
                            case 'pending':
                                echo __('Pending Approval', 'hola-weddings');
                                break;
                        } 
                    ?>
                </p>
            </div>
            <div class="quote-metas d-flex justify-content-center mt-3">
                <div class="meta text-center">
                    <div class="meta-icon"><?= hw_get_svg_icon('passport') ?></div>
                    <div class="meta-value">
                        
                        <?= sprintf(__('Adults: %s'), $adults);  ?>
                        
                        <?php if($teen > 0): ?>
                            &#8226;
                            <?= sprintf(__('Teen: %s'), $teen);  ?>
                        <?php endif; ?>
                        
                        <?php if($children > 0): ?>
                            &#8226;
                            <?= sprintf(__('Children: %s'), $children);  ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="meta text-center">
                    <div class="meta-icon"><?= hw_get_svg_icon('calendar') ?></div>
                    <div class="meta-value"><?= sprintf(__('From %s<br>to %s', 'hola-weddings'), $quote->getCheckin()->format('F d, Y'), $quote->getCheckout()->format('F d, Y')) ?></div>
                </div>
                <div class="meta text-center">
                    <div class="meta-icon"><?= hw_get_svg_icon('bed') ?></div>
                    <div class="meta-value"><?= $quote->getRoom()['name'] ?></div>
                </div>
            </div>
            <div class="text-center mt-5">
                <a class="btn btn-primary" href="<?= hw_get_reservation_permalink($quote->getID()) ?>">
                    <?= __('View Reservation', 'hola-weddings') ?>
                </a>
            </div>
        </div>
    </div>
</div>