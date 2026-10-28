<?php

$quote = $transaction->getQuote();
$hotel = hw_get_hotel();
$ci = $quote->getCheckin();
$co = $quote->getCheckout();
$room = $quote->getRoomBlock();
?>
<div class="box">
    <div class="label" style="font-weight: bold;"><?= __('Reservation Details', 'hola-weddings') ?></div>
    <table style="width: 100%;">
        <tr>
            <td style="width: 50%;">
                <div class="label" style="font-weight: bold;"><?= __('Hotel', 'hola-weddings') ?></div>
                <?= $hotel['name'] ?>
            </td>
            <td style="width: 50%;">
                <div class="label" style="font-weight: bold;"><?= __('Room Type', 'hola-weddings') ?></div>
                <?= $room->getTitle() ?>
            </td>
        </tr>
        <tr>
            <td style="width: 50%;">
                <div class="label" style="font-weight: bold;"><?= __('Check In', 'hola-weddings') ?></div>
                <?= $ci instanceof DateTime ? $ci->format('l F d, Y') : (new DateTime($ci))->format('l F d, Y') ?>
            </td>
            <td style="width: 50%;">
                <div class="label"><?= __('Check Out', 'hola-weddings') ?></div>
                <?= $co instanceof DateTime ? $co->format('l F d, Y') : (new DateTime($co))->format('l F d, Y') ?>
            </td>
        </tr>
        <tr>
            <td style="width: 50%;">
                <div class="label" style="font-weight: bold;"><?= __('Occupancy', 'hola-weddings') ?></div>
                <?= hw_format_quote_occupancy($quote) ?>
            </td>
        </tr>
    </table>
</div>