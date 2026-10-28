<?php
$deposit = hw_get_quote_deposit($id);
?>
<h6 class="uppercase"><?= __('Log', 'hola-weddings') ?></h6>
<table class="box lined-table">
    <tr>
        <td style="width: 100%">
            <strong><?= __('Charge accepted on', 'hola-weddings') ?></strong>
            <p><?= date('m/d/Y H:i:s', $deposit['accepted_on']); ?></p>
        </td>
    </tr>
    <tr>
        <td style="border-bottom: 0;">
            <strong><?= __('IP Address', 'hola-weddings') ?></strong>
            <p><?= $deposit['ip_address']; ?></p>
        </td>
    </tr>
</table>