<?php 
$insurance_plan = hw_get_quote_insurance();
?>
<div class="panel">
    <div class="title"><?= __('Insurance', 'hola-weddings') ?></div>     
    <div class="body">
        <?php if($insurance_plan): ?>
            <table style="width: 100%">
                <tr>
                    <td>
                        <strong>Insurance Plan</strong>
                        <p><?= $insurance_plan['name'] ?></p>
                    </td>
                </tr>
            </table>
        <?php else: ?>
            <p><?= __('No insurance', 'hola-weddings') ?></p>
        <?php endif; ?>
    </div>
</div>
