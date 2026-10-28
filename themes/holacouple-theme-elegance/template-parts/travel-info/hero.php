<div id="hero" class="p-0 position-relative divider-bottom">
    <div class="bg-container">
        <div class="hero-image" style="background-image: url('<?= $hotel->main_image ?>');"></div>
    </div>
    <div class="content">
        <div class="container position-relative">
            <div class="hotel-meta position-relative decoration-top decoration-bottom py-5">
                <h1 class="hero-title hotel-name text-white">
                    <?= $hotel->name ?>
                </h1>
                <div class="hotel-location text-white mb-0 mt-5 hero-subtitle text-uppercase">
                    <?= hw_get_hotel_location($hotel) ?>
                </div>
            </div>
        </div>
    </div>
</div>