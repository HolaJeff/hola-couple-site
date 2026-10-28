<?php
$hotel = (object) hw_get_hotel();
$additional_events = hw_get_additional_events();
?>
<?php if($hotel): ?>
    <div class="section hotel-section">
        <div class="container">
            <h2 class="section-title"><?= __('Wedding Details', 'hola-weddings') ?></h2>
            <div class="hotel-cta-wrapper" style="background-image:url(' <?= $hotel->main_image ?>')" >
                <div class="position-relative">
                    <div class="subtitle text-uppercase mb-3 text-white">
                        <i data-feather="map-pin" height="32" width="32"></i>
                    </div>    
                    <h2 class="hotel-name text-white"><?= $hotel->name ?></h2>
                    <div class="hotel-address text-uppercase"><?= hw_get_hotel_location($hotel) ?></div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
<div id="overview" class="section">
    <div class="container">
        <!-- Schedule -->
        <h3 class="section-subtitle"><?= __('Wedding Day', 'hola-weddings') ?></h3>
        <h2 class="section-title"><?= __('Schedule', 'hola-weddings') ?></h2>
        <?= hw_get_template('template-parts/home/overview/wedding-details') ?>
    </div>
</div>
<?php if(!empty($additional_events)): ?>
    <div id="additional-events" class="section">
        <div class="container">
            <!-- Additional Events -->
            <h3 class="section-subtitle"><?= __('Additional', 'hola-weddings') ?></h3>
            <h2 class="section-title"><?= __('Events', 'hola-weddings') ?></h2>
            <?= hw_get_template('template-parts/home/overview/additional-events') ?>
        </div>
    </div>
<?php endif ?>