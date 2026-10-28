<?php

use com\zoho\crm\api\util\Choice;

$rules = [];
$rules[] = [
    'period' => $potential->getKeyValue('Period'),
    'penalty' => $potential->getKeyValue('Penalty_Date_Range_1')
];
$rules[] = [
    'period' => $potential->getKeyValue('Period_2'),
    'penalty' => $potential->getKeyValue('Penalty_Date_Range_2')
];
$rules[] = [
    'period' => $potential->getKeyValue('Period_3'),
    'penalty' => $potential->getKeyValue('Penalty_Date_Range_3')
];
$rules[] = [
    'period' => $potential->getKeyValue('Period_4'),
    'penalty' => $potential->getKeyValue('Penalty_Date_Range_4')
];
$rules[] = [
    'period' => $potential->getKeyValue('Period_5'),
    'penalty' => $potential->getKeyValue('Penalty_Date_Range_5')
];
?>
<strong>CANCELLATION POLICY:</strong>
<?php if(!empty($rules)): ?>
    <ul>
        <?php foreach($rules as $rule): 
            // skip rules that containd null period or null penalty
            if(in_array(null, $rule, true)){
                continue;
            }
            ?>
            <li><strong><?= $rule['period'] ?></strong> - <?= $rule['penalty'] instanceof Choice ? $rule['penalty']->getValue() : $rule['penalty'] ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
<strong>INSURANCE: **We strongly suggest purchasing insurance**</strong>
<p>Please note that the insurance fee is non-refundable.</p>