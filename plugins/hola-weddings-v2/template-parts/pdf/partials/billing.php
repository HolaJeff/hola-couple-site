<?php
/** @var HW\Models\CRM\BillingInformation|null $billing */
?>
<h6 class="uppercase"><?= __('Billing Information', 'hola-weddings') ?></h6>
<table class="box">
    <tr>
        <td>
            <table class="lined-table">
                <tr>
                    <td style="width: 100%;">
                        <div class="label"><?= __('Full Name', 'hola-weddings'); ?></div>
                        <?= sprintf('%s %s', $billing->getFirstName(), $billing->getLastName()) ?>
                    </td>
                </tr>
                <tr>
                    <td style="width: 100%;">
                        <div class="label"><?= __('Email Address', 'hola-weddings'); ?></div>
                        <?= $billing->getEmail() ?>
                    </td>
                </tr>
                <tr>
                    <td style="width: 100%;">
                        <div class="label"><?= __('Phone Number', 'hola-weddings'); ?></div>
                        <?= $billing->getPhone(); ?>
                    </td>
                </tr>
                <tr>
                    <td style="border-bottom: 0">
                        <div class="label"><?= __('Address', 'hola-weddings'); ?></div>
                        <?= hw_format_address($billing) ?>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>