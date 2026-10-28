<?php
use HW\API\Operations\Insurance;

$insurance_plan_id = hw_get_quote_insurance_plan_id($id);
?>
<h6 class="uppercase"><?= __('Insurance', 'hola-weddings') ?></h6>
<?php if($insurance_plan_id): 

    $plan = Insurance::getPlan($insurance_plan_id);
    ?>
    <table class="box">
        <tr>
            <td style="width: 50%;">
                <strong><?= __('Insurance Plan', 'hola-weddings'); ?></strong>
                <p><?= $plan['name'] ?></p>
            </td>
            <td style="width: 50%;">
                <strong><?= __('Insurance Total', 'hola-weddings'); ?></strong>
                <p><?= hw_format_price( hw_get_quote_insurance_total($id) ); ?></p>
            </td>
        </tr>
    </table>
<?php else: ?>
    <p><?= __('Insurance Declined', 'hola-weddings') ?></p>
<?php endif; ?>