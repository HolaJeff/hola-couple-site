<?php

$amenities = hw_get_hotel_amenities();

if(!empty($amenities)): ?>
    <div class="amenities">
        <h3 class="mt-3 text-center"><?= __('Amenities & Services', 'hola-weddings'); ?></h3>
        <div class="row">
            <?php foreach($amenities as $amenity): ?>
                <div class="col-md-3 amenity">
                    <i data-feather="check-circle" class="mr-2"></i>
                    <span class="name"><?= $amenity['name'] ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>