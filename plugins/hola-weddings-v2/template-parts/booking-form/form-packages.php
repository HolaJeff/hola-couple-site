<?php

$rate_slug = $rate_type['rate']['slug'];
$rooms_data = hw_get_rooms_data();

?>
<div class="room-list-selector">
    <div class="form-group">
        <div class="">
            <?php while($products->have_posts()): $packages->the_post();
            
                $room = (object) hw_get_room(get_field('hw_room'));
                $checkin = get_field('from');
                $checkout = get_field('to');
                
                if(!$rooms_data[$room->id]['enabled']
                   || !$rooms_data[$room->id]['prices'][$rate_slug]['enabled']
                   || (!hw_is_room_available($room->id, $checkin, $checkout, 1))){
                    continue;
                }
                
                $prices = $rooms_data[$room->id]['prices'];
                $adult_price = $prices[$rate_slug]['adult'];
                $child_price = $prices[$rate_slug]['child'];
                ?>
                <div class="room">
                    <input id="package_<?= $room->id ?>" class="room-input" type="radio" name="package_id" value="<?= get_the_ID() ?>" />
                    <label for="package_<?= $room->id ?>" class="inner d-flex" data-room-id="<?= $room->id ?>" data-nights="<?= hw_calc_package_nights( get_the_ID() ) ?>">
                        <div class="image-holder" style="background-image: url(<?= $room->main_image ?>);"></div>
                        <div class="content">
                            <h4><?= $room->name ?></h4>
                            <div class="dates">
                                <strong><?= __('From:', 'hola-weddings') ?></strong>
                                <span class="dates"><?= (new DateTime(get_field('from')))->format('F, j Y') ?></span>
                                -
                                <strong><?= __('To:', 'hola-weddings') ?></strong>
                                <span class="dates"><?= (new DateTime(get_field('to')))->format('F, j Y') ?></span>
                            </div>
                            <!--<div class="rates">
                                <?= 'Adult: ' . hw_format_price( $adult_price ) . ( !empty($child_price) ? ' / Children: ' . hw_format_price( $child_price ) : '' ) ?>
                            </div>-->
                            
                            <div class="features d-flex mt-2">
                                <div class="feature mr-3">
                                    <i class="fas fa-plane mr-1"></i>
                                    <?= __('Flight Included', 'hola-weddings') ?>
                                </div>
                                <div class="feature mr-3">
                                    <i class="fas fa-shuttle-van mr-1"></i>
                                    <?= __('Airport Transportation Included', 'hola-weddings') ?>
                                </div>
                            </div>
                            
                            <div class="actions">
                                <button class="btn btn-link room-info-cta p-0 mt-2" type="button"><?= __('Room Details', 'hola-weddings') ?></button>
                                <button class="btn btn-link package-info-cta p-0 mt-2 ml-3" data-id="<?= get_the_ID() ?>" type="button"><?= __('Package Details', 'hola-weddings') ?></button>
                            </div>
                        </div>
                    </label>
                </div>
             <?php endwhile; wp_reset_postdata(); ?>
        </div>
        <?php if(isset($errors['room'])): ?>
            <div id="room-Feedback" class="invalid-feedback">
               <?= $errors['room'] ?>
            </div>
        <?php endif; ?>
    </div>
</div>