<?php

use HW\API\Operations\Hotels;
use HW\UI\Forms\Controls\Select;

$hotels = Hotels::getList();
$hotel_id = hw_get_hotel_id();

if( !is_wp_error( $hotels ) ):
    $hotels = [ 0 => '- Select Hotel -' ] + $hotels;

    Select::make('hw_hotel_id')
        ->label('Select Hotel')
        ->value($hotel_id)
        ->options($hotels)
        ->render();
    ?>
    <button id="refresh-hotel-data" type="button" class="btn btn-primary d-inline-block mt-2">
        <span class="dashicons dashicons-update"></span>
        <?= __('Refresh', 'hola-weddings') ?>
    </button>
    <br>
    <div id="rooms-container" class="mt-5">
        <?php if( $hotel_id ):
            $rooms = hw_get_rooms();
            
            if( is_wp_error( $rooms ) ): ?>
                <p class="alert alert-warning"><?= $rooms->get_error_message() ?></p>
            <?php else: ?>
                <?php hw_get_template('templates/admin/settings/common/rooms', [ 'rooms' => $rooms, 'form' => $form ] ); ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
<?php else: ?>
    <p class="alert alert-warning"><?= $hotels->get_error_message() ?></p>
<?php endif;