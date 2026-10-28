<?php

if(!isset($hotel)){
    $hotel = (object) hw_get_hotel();
}

if(!empty($hotel->images)): ?>
    <div class="hotel-slider">
        <?php foreach($hotel->images as $image): ?>
            <div class="image-holder" style="background-image: url(<?= $image ?>)"></div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <img src="<?= $hotel->image ?>" />
<?php endif; ?>