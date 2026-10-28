<div id="hero" class="p-0 position-relative">
    <div class="bg-container">
        <div class="hero-image" style="background-image: url('<?= $hotel->main_image ?>');"></div>
    </div>
    <div class="content">
        <div class="container position-relative">
            <div class="hotel-meta">
                <h1 class="hero-title hotel-name">
                    <?= $hotel->name ?>
                </h1>
                <div class="hero-subtitle hotel-location text-uppercase">
                    <?= hw_get_hotel_location($hotel) ?>
                </div>
            </div>
        </div>
    </div>
</div>