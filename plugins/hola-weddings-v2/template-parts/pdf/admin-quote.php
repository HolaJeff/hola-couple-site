<?php
$him = hw_get_option('hw_his_name', '' );
$her = hw_get_option('hw_her_name', '' );
$quote = hw_get_quote($id);
$customer = $quote->getBillingInformation();
$guests = hw_get_quote_guests($id);
$ci = hw_get_quote_check_in($id);
$co = hw_get_quote_check_out($id);
$room = hw_get_room(hw_get_quote_room_id($id));
?>
<?= hw_get_template('template-parts/pdf/partials/style') ?>
<page>
    <div style="text-align: center; margin-bottom: 30px">
        <img src="<?= HW_PLUGIN_PATH ?>/assets/img/logo.png" style="height: 70px; width: auto;" />
    </div>
    <div>
        <h1 style="margin-bottom: 10px;"><?= sprintf(__('%s and %s Wedding UCC Form', 'hola-weddings'), $him, $her); ?></h1>
        <p><?= sprintf(__('Reservation: <strong>%s %s</strong> for 1 Room', 'hola-weddings'), $customer->getFirstName() ?: '', $customer->getLastName() ?: ''); ?></p>
    </div>

    <!-- Room -->
    <?= hw_get_template('template-parts/pdf/partials/room', ['id' => $id, 'ci' => $ci, 'co' => $co, 'room' => $room]) ?>

    <!-- Guests -->
    <?= hw_get_template('template-parts/pdf/partials/guests', ['guests' => $guests]) ?>

    <!-- Insurance -->
    <?= hw_get_template('template-parts/pdf/partials/insurance', ['id' => $id]) ?>

    <!-- Terms and cancellation -->
    <?= hw_get_template('template-parts/pdf/partials/terms', ['id' => $id]) ?>
</page>
<page>

    <!-- Balance -->
    <?= hw_get_template('template-parts/pdf/partials/payment', ['id' => $id]) ?>
    
    <!-- Balance -->
    <?= hw_get_template('template-parts/pdf/partials/billing', ['id' => $id, 'customer' => $customer]) ?>
</page>
<?php if(is_payment_enabled()): ?>
    <page>
        
        <!-- Balance -->
        <?= hw_get_template('template-parts/pdf/partials/card', ['id' => $id, 'customer' => $customer]) ?>

        <!-- Log -->
        <?= hw_get_template('template-parts/pdf/partials/log', ['id' => $id]) ?>

    </page>
<?php endif; ?>
