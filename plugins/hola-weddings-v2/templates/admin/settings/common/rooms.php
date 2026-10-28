<?php

use HW\Models\RoomBlock;

$room_settings = hw_get_rooms_data();
$person_types = get_person_types();
$dates = hw_get_dates_from_range(get_start_date(), get_end_date());
?>
<h5 class="mb-3">Import Rooms from October API</h5>
<div class="form-group">
    <?php if(empty($rooms)): ?>
        <p class="alert alert-warning"><?= __('No rooms available', 'hola-weddings') ?></p>
    <?php else: ?>
        <div id="room-list" class="accordion">
            <table class="table bg-white">
                <thead>
                    <tr>
                        <td style="width:10%"><?= __('ID', 'hola-weddings') ?></td>
                        <td><?= __('Name', 'hola-weddings') ?></td>
                        <td><?= __('Options', 'hola-weddings') ?></td>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 0; foreach($rooms as $room): 
                        $room_block = RoomBlock::getByRoomID($room->getID());
                        ?>
                        <tr>
                            <td><?= $room->getID() ?></td>
                            <td class="align-middle"><?= $room->getName() ?></td>
                            <td>
                                <?php if($room_block != false): ?>
                                    <a href="<?= $room_block->getEditLink() ?>" target="_blank"><?= __('Edit Room Block', 'hola-weddings') ?></a>
                                <?php else: ?>
                                    <button class="btn btn-primary" type="button" data-hw-api="import-room" data-room-id="<?= $room->getID() ?>">
                                        <?= __('Import', 'hola-weddings') ?>
                                        <span class="spinner-border spinner-border-sm ms-1" role="status" aria-hidden="true"></span>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php $i++; endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>