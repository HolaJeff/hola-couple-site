<h6 class="uppercase"><?= __('Reservation Details', 'hola-weddings') ?></h6>
<table class="box" style="width: 100%;">
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
            <div class="label" style="font-weight: bold;"><?= __('Room Type', 'hola-weddings') ?></div>
            <?= $room['name'] ?>
        </td>
        <td style="width: 50%;">
            <div class="label" style="font-weight: bold;"><?= __('Room Total', 'hola-weddings') ?></div>
            <?= hw_format_price(hw_get_quote_room_total($id)) ?>
        </td>
    </tr>
</table>