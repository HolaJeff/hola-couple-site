<table class="inventory-table table">
    <tbody>
       
        <?php foreach($dates as $date): ?>
            <tr>
                <td>
                    <?= $date ?>
                </td>
                <td>
                    <input class="form-control" type="number" name="hw_inventory[<?= $date ?>]" value="<?= isset($inventory[$date]) ? $inventory[$date] : '1' ?>" />             
                </td>
                <td>
                    <p class="<?= isset($booked_units[$date]) && $booked_units[$date]['count'] >= $room_settings[$room_id]['inventory'][$date] ? 'text-danger' : ''; ?>">
                        <?= sprintf('Booked: %s', isset($booked_units[$date]) ? $booked_units[$date]['count'] : 0 ) ?>
                    </p>
                </td>
            </tr>
        <?php endforeach; ?>
        
    </tbody>
    
</table>