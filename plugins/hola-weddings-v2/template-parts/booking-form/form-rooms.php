<div class="room-list-selector">
    <div class="form-group">
        <div class="row <?= isset($errors['room']) ? 'is-invalid' : '' ?>">
            <?php foreach($products as $room_block):

                $room_block_id = $room_block->getID();
                $room = (object) $room_block->getRoom();
                $checked = ( $data['room_block_id'] ?? null ) == $room_block_id;
                ?>
                <div id="room_<?= $room_block_id ?>" class="room col-12 col-md-6 col" data-name="<?= $room->name ?>" data-prices="<?= htmlspecialchars( json_encode($room_block->prices, JSON_FORCE_OBJECT | JSON_HEX_TAG )) ?>" data-available="<?= $room_block->isAvailable($idata['ci'], $idata['co']) ? '1' : '0' ?>">
                    <input id="room_<?= $room_block_id ?>_input" class="room-input" type="radio" name="room_block_id" value="<?= $room_block_id ?>" <?= $checked ? 'checked' : '' ?> />
                    <label for="room_<?= $room_block_id ?>_input" class="inner d-flex" data-room-id="<?= $room_block_id ?>">
                        <div class="image-holder" style="background-image: url(<?= $room->main_image ?? '' ?>);"></div>
                        <div class="content">
                            <h4><?= $room->name ?></h4>
                            <div class="actions">
                                <button class="btn btn-link room-info-cta p-0 mt-2" type="button"><?= __('Room Details & Rates', 'hola-weddings') ?></button>
                            </div>
                        </div>
                    </label>
                </div>
             <?php endforeach; ?>
        </div>
        <?php if(isset($errors['room'])): ?>
            <div id="room-Feedback" class="invalid-feedback">
               <?= $errors['room'] ?>
            </div>
        <?php endif; ?>
    </div>
</div>