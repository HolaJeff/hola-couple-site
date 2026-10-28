<?php
$date = hw_get_date(true);
$events = hw_get_events();
$additional_events = hw_get_additional_events();
$hotel = (object) hw_get_hotel();
?>
<?php if($hotel): ?>
    <div class="section hotel-section">
        <div class="bg-overlay"></div>
        <div class="container">
            <div class="row flex-column-reverse flex-lg-row">
                <div class="col-lg-6">
                    <div class="section-icon d-none d-lg-block">
                        <?= hw_theme_get_svg_icon('palm-tree') ?>
                    </div>
                    <h2 class="section-title d-none d-lg-block"><?= __('The Venue', 'hola-weddings') ?></h2>
                    <div class="bg-white p-5">
                        <h3 class="hotel-name mb-1"><?= $hotel->name ?></h3>
                        <div class="hotel-address text-uppercase mb-3 font-italic">
                            <i data-feather="map-pin"></i>
                            <?= hw_get_hotel_location($hotel) ?>
                        </div>
                        <div class="hotel-description"><?= $hotel->description ?></div>
                    </div>
                </div>
                <div class="col-lg-6 mb-5 mb-lg-0">
                    <div class="section-icon d-lg-none">
                        <?= hw_theme_get_svg_icon('palm-tree') ?>
                    </div>
                    <h2 class="section-title d-lg-none"><?= __('The Venue', 'hola-weddings') ?></h2>
                    <div class="revealer animate-left">
                        <div class="revealer-content image-wrapper decoration-right">
                            <div class="hotel-cta-wrapper" style="background-image:url(' <?= $hotel->main_image ?>')" ></div>
                        </div>
                        <div class="revealer-element"></div>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
<?php endif; ?>
<div id="overview" class="section">
    <div class="container">
        <h2 class="section-title"><?= __('Wedding Details', 'hola-weddings') ?></h2>
        <div class="row justify-content-center px-3 px-lg-0">
            <?= hw_get_template('template-parts/home/overview/wedding-details') ?>
        </div>
    </div>
</div>
<?php if(!empty($additional_events)): ?>
    <div id="additional-events" class="section">
        <div class="container">
            <h2 class="section-title"><?= __('Additional Events', 'hola-weddings') ?></h2>
            <div class="px-3 px-lg-0">
                <?= hw_get_template('template-parts/home/overview/additional-events') ?>
            </div>
        </div>
    </div>
<?php endif ?>