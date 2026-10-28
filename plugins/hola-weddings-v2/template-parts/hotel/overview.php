<?php

if(!isset($hotel)){
    $hotel = (object) hw_get_hotel();
}

?>
<div class="hotel__meta d-flex flex-wrap flex-md-nowrap mt-4">
    <div class="meta flex-fill">
        <div class="icon"><?= hw_get_svg_icon('bed') ?></div>
        <div class="details">
            <div class="title"><?= __('Rooms', 'hola-weddings') ?></div>
            <div class="value"><?= $hotel->features['rooms'] ?></div>
        </div>
    </div>
    <div class="meta flex-fill">
        <div class="icon"><?= hw_get_svg_icon('restaurant') ?></div>
        <div class="details">
            <div class="title"><?= __('Restaurants', 'hola-weddings') ?></div>
            <div class="value"><?= $hotel->features['restaurants'] ?></div>
        </div>
    </div>
    <div class="meta flex-fill">
        <div class="icon"><?= hw_get_svg_icon('cocktail') ?></div>
        <div class="details">
            <div class="title"><?= __('Bars', 'hola-weddings') ?></div>
            <div class="value"><?= $hotel->features['bars'] ?></div>
        </div>
    </div>
    <div class="meta flex-fill">
        <div class="icon"><?= hw_get_svg_icon('swimming-pool') ?></div>
        <div class="details">
            <div class="title"><?= __('Swimming Pools', 'hola-weddings') ?></div>
            <div class="value"><?= $hotel->features['swimming_pools'] ?></div>
        </div>
    </div>
    <div class="meta flex-fill">
        <div class="icon"><?= hw_get_svg_icon('departure') ?></div>
        <div class="details">
            <div class="title"><?= __('Airport Distance', 'hola-weddings') ?></div>
            <div class="value">
                <?php if($hotel->airport): ?>
                    <div><?= $hotel->airport['name'] ?></div>
                <?php endif; ?>
                <?= $hotel->features['ariport_distance'] ?>
            </div>
        </div>
    </div>
</div>