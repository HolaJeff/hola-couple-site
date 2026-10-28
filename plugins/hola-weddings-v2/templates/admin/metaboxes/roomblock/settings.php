<label for="room_id" class="form-label"><?= __('Room', 'hola-weddings') ?></label>
<select id="room_id" class="form-select" name="hw_room_id">
    <?php foreach($rooms as $room): ?>
        <option value="<?= $room['id'] ?>" <?= $current == $room['id'] ? 'selected' : '' ?>><?= $room['name'] ?></option>
    <?php endforeach; ?>
</select>