<table id="guests-table" class="wp-list-table widefat fixed striped table-view-list posts">
    <thead>
        <tr>
            <th style="width: 70px;"><?= __('Title', 'hola-weddings') ?></th>
            <th><?= __('First Name', 'hola-weddings') ?></th>
            <th><?= __('Last Name', 'hola-weddings') ?></th>
            <th><?= __('Date of birth', 'hola-weddings') ?></th>
            <th style="width: 100px;"><?= __('Age', 'hola-weddings') ?></th>
            <th style="width: 50px;"></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($guests as $i => $guest): ?>
            <tr>
                <td>
                    <select class="form-select" name="data[guests][<?= $i ?>][title]">
                        <option value="mr" <?= $guest['title'] == 'mr' ? 'selected' : '' ?>><?= __('Mr', 'hola-weddings') ?></option>
                        <option value="mrs" <?= $guest['title'] == 'mrs' ? 'selected' : '' ?>><?= __('Mrs', 'hola-weddings') ?></option>
                        <option value="miss" <?= $guest['title'] == 'miss' ? 'selected' : '' ?>><?= __('Miss', 'hola-weddings') ?></option>
                        <option value="mstr" <?= $guest['title'] == 'mstr' ? 'selected' : '' ?>><?= __('Mstr', 'hola-weddings') ?></option>
                    </select>
                </td>
                <td>
                    <input class="form-control" type="text" name="data[guests][<?= $i ?>][name]" value="<?= $guest['name'] ?>" />
                </td>
                <td>
                    <input class="form-control" type="text" name="data[guests][<?= $i ?>][lastname]" value="<?= $guest['lastname'] ?>" />
                </td>
                <td>
                    <?php
                    $date = !empty($guest['date']) ? (new \DateTime($guest['date'])) : null;
                    ?>
                    <input class="form-control guest-age-field"  autocomplete="off" type="text" value="<?= $date ? $date->format('m-d-Y') : '' ?>" />
                    <input type="hidden" name="data[guests][<?= $i ?>][date]" value="<?= $date ? $date->format('Y-m-d') : '' ?>" />
                </td>
                <td>
                    <input class="age-field form-control" type="text" name="data[guests][<?= $i ?>][age]" value="<?= $guest['age'] ?>" readonly />
                </td>
                <td>
                    <button type="button" data-remove-guest class="btn btn-outline-danger"><span class="dashicons dashicons-trash"></span></button>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<div class="mt-3 mb-2 text-end">
    <button data-add-guest type="button" class="btn btn-outline-primary">
        <span class="dashicons dashicons-plus align-text-bottom"></span>
        <?= __('Add Guest', 'hola-weddings') ?>
    </button>
</div>