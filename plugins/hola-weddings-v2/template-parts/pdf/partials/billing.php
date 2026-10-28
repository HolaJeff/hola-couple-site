<table class="box">
    <tr>
        <td>
            <div style="font-weight: bold;"><?= __('Billing Information', 'hola-weddings') ?></div>
            <table class="lined-table">
                <tr>
                    <td style="width: 100%;">
                        <div class="label"><?= __('Full Name', 'hola-weddings'); ?></div>
                        <?= sprintf('%s %s', $customer->getFirstName(), $customer->getLastName()) ?>
                    </td>
                </tr>
                <tr>
                    <td style="width: 100%;">
                        <div class="label"><?= __('Email Address', 'hola-weddings'); ?></div>
                        <?= $customer->getEmail() ?>
                    </td>
                </tr>
                <tr>
                    <td style="width: 100%;">
                        <div class="label"><?= __('Phone Number', 'hola-weddings'); ?></div>
                        <?= $customer->getPhone(); ?>
                    </td>
                </tr>
                <tr>
                    <td style="border-bottom: 0">
                        <div class="label"><?= __('Address', 'hola-weddings'); ?></div>
                        <?= hw_format_address($customer) ?>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>