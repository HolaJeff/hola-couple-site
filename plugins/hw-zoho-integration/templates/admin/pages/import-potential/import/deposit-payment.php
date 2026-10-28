<?php

use com\zoho\crm\api\util\Choice;

$first_deposit_amount = $potential->getKeyValue('st_Deposit_Amount');

$second_deposit_type = $potential->getKeyValue('nd_Deposit_Type');
$second_deposit_amount = $potential->getKeyValue('nd_Deposit_Amount');
$second_deposit_due_date = $potential->getKeyValue('nd_Deposit_Due_Date');

$third_deposit_amount = $potential->getKeyValue('rd_Deposit_Amount');
$third_deposit_due_date = $potential->getKeyValue('rd_Deposit_Due_Date');

$final_deposit_due_date = $potential->getKeyValue('th_Deposit_Due_Date');

$transfer_cost = $potential->getKeyValue('Transfer_Cost');
?>
<strong>DEPOSIT AND PAYMENT PLAN:</strong>
<ul>
    <?php 
    // first
    if($first_deposit_amount): ?>
        <li><strong>1st Deposit:</strong> To hold your room we will need a deposit of <strong><?= hw_format_price($first_deposit_amount) ?></strong> <strong>per room</strong>.</li>
    <?php endif;
    // second
    if($second_deposit_type instanceof Choice && $second_deposit_type->getValue() != 'No Deposit' 
        && $second_deposit_due_date && $second_deposit_amount): 
        $date = new DateTime($second_deposit_due_date);
        $multiplier_text_array = explode('-', $second_deposit_type->getValue());
        $multiplier_text = isset($multiplier_text_array[1]) ? $multiplier_text_array[1] : $multiplier_text_array[0];
        ?>
        <li><strong>2nd Deposit:</strong> <?php if($date): ?>Due by <strong><?= $date->format('F d, Y') ?></strong> -<?php endif; ?> <strong><?= hw_format_price($second_deposit_amount) ?></strong> <strong><?= $multiplier_text ?></strong>.</li>
    <?php endif; 
    // third
    if($third_deposit_due_date && $third_deposit_amount): 
        $date = new DateTime($third_deposit_due_date);
        ?>
        <li><strong>2nd Deposit:</strong> <?php if($date): ?>Due by <strong><?= $date->format('F d, Y') ?></strong> -<?php endif; ?> <strong><?= hw_format_price($third_deposit_amount) ?></strong> <strong>per person</strong>.</li>
    <?php endif; 
    // final
    if($final_deposit_due_date): 
        $date = new DateTime($final_deposit_due_date);
        ?>
        <li><strong>Final Payment:</strong> The remaining balance will be due by <strong><?= $date->format('F d, Y') ?></strong>.</li>
    <?php endif; ?>
</ul>
<strong>AIRPORT TRANSPORTATION:</strong>
<p>Transportation is NOT included in the rates above; the following rates apply to the group and will be added automatically to every booking: <strong><?= $transfer_cost ? hw_format_price($transfer_cost) : '$x USD' ?></strong> per room for a round-trip shuttle service.</p>