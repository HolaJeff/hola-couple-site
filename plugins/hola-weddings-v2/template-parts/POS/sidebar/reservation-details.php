<?php
$room_block = $quote->getRoomBlock();
$room = $room_block->getRoom();
$hotel = hw_get_hotel();
$wedding = hw()->get('wedding');
$guests = $quote->getGuests();
?>
<div class="d-flex flex-column flex-md-row flex-lg-column reservation-details mt-3 bg-white rounded">
    <div class="image" style="background-image: url('<?= $room['main_image'] ?>');"></div>
    <div class="wedding-details p-3">
        <h4 class="room-name mb-1"><?= $room['name'] ?></h4>
        <div class="mb-2">
            <small><?= sprintf(__('at %s', 'hola-weddings'), $hotel['name']) ?></small>
            <span class="align-middle">&#8226;</span>
            <small><?= sprintf(__('%s Wedding', 'hola-weddings'), $wedding->getWeddingName()) ?></small>
        </div>
        <div class="meta">
            <div class="row mb-2">
                <div class="col-6">
                    <div class="label"><?= __('Main Guest', 'hola-weddings') ?></div>
                    <div class="value"><?= ucwords($guests[0]['name']) ?></div>
                </div>
                <div class="col-6">
                    <div class="label"><?= __('Guests', 'hola-weddings') ?></div>
                    <div class="value"><?= count($guests) ?></div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="label"><?= __('Check-In', 'hola-weddings') ?></div>
                    <div class="value"><?= $quote->getCheckin()->format('d F Y') ?></div>
                </div>
                <div class="col-6">
                    <div class="label"><?= __('Check-Out', 'hola-weddings') ?></div>
                    <div class="value"><?= $quote->getCheckout()->format('d F Y') ?></div>
                </div>
            </div>
        </div>
    </div>
</div>