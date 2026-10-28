<?php 
$person_types = get_person_types();
$rate_types = $room['occupancyrules'];

if(!empty($rate_types)): ?>
    <!-- occupancy rules -->
    <table class="mt-3 table table-bordered">
        <thead>
            <tr>
                <td></td>
                <td><?= __('Disabled') ?></td>
                <?php foreach( $person_types as $slug => $label): ?>
                    <td><?= $label; ?></td>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach($rate_types as $rate): 
                $rate_name = $rate['name'];
                $rate_slug = $rate['slug'];
                ?>
                <tr>
                    <td><?= $rate_name ?></td>
                    <td><input class="form-control" type="checkbox" name="hw_occupancy_rules[<?= $rate_slug ?>][enabled]" value="1" <?= isset($occupancy_rules[ $rate_slug ]['enabled']) && $occupancy_rules[ $rate_slug ]['enabled'] ? 'checked="checked"' : '' ?> /></td>
                    <?php foreach( $person_types as $slug => $label): ?>
                        <td><input min="0" class="form-control" type="number" name="hw_occupancy_rules[<?= $rate_slug ?>][<?= $slug ?>]" value="<?= isset($occupancy_rules[ $rate_slug ][$slug]) ? $occupancy_rules[$rate_slug][$slug] : '' ?>" /></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <!-- End occupancy rules -->
<?php else: ?>
    <p><?= __('No occupancy rules found for this room.') ?></p>
<?php endif; ?>