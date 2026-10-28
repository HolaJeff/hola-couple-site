<?php
$room = hw_get_room(hw_get_quote_room_id());
?>
<div class="panel">
    <div class="title"><?= __('Details', 'hola-weddings') ?></div>     
    <div class="body">
        <table style="width: 100%">
            <tbody>
                <tr>
                    <td colspan="2">
                        <strong><?= __('Booked Room') ?></strong>
                        <p><?= $room['name'] ?></p>
                    </td>
                    <td>
                        <strong><?= __('Check-in') ?></strong>
                        <p><?= (new \DateTime(hw_get_quote_check_in()))->format('m/d/Y') ?></p>
                    </td>
                    <td>
                        <strong><?= __('Check-out') ?></strong>
                        <p><?= (new \DateTime(hw_get_quote_check_out()))->format('m/d/Y') ?></p>
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong><?= __('Adults') ?></strong>
                        <p><?= hw_get_quote_adults() ?></p>
                    </td>
                    <?php if(hw_is_teen_enabled()): ?>
                        <td>
                            <strong><?= __('Teen') ?></strong>
                            <p><?= hw_get_quote_teens() ?></p>
                        </td>
                    <?php endif; ?>
                    <td>
                        <strong><?= __('Children') ?></strong>
                        <p><?= hw_get_quote_children() ?></p>
                    </td>
                    <td>
                        <strong><?= __('Infants') ?></strong>
                        <p><?= hw_get_quote_infants()  ?></p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>