<?php 
$insurance_plan = $quote->getInsurancePlan();

if($insurance_plan): ?>
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
