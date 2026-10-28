<?php
use HW\App;

$wedding = App::getInstance()->getWedding();
$him = $wedding->getHisName();
$her = $wedding->getHerName();
$billing = $quote->getBillingInformation();
$guests = $quote->getGuests();
$ci = $quote->getCheckin();
$co = $quote->getCheckout();
$room = $quote->getRoom();
?>
<?= hw_get_template('template-parts/pdf/partials/style') ?>
<page>
    <div style="text-align: center; margin-bottom: 30px">
        <img src="<?= HW_PLUGIN_PATH ?>/assets/img/logo.png" style="height: 70px; width: auto;" />
    </div>
    <div>
        <h1 style="margin-bottom: 10px;"><?= sprintf(__('%s and %s Wedding UCC Form', 'hola-weddings'), $him, $her); ?></h1>
        <p><?= sprintf(__('Reservation: <strong>%s %s</strong> for 1 Room', 'hola-weddings'), $billing->getFirstName(), $billing->getLastName()); ?></p>
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
    <?php hw_get_template('template-parts/pdf/partials/payment', ['id' => $id, 'quote' => $quote ]) ?>
    
    <!-- Balance -->
    <?= hw_get_template('template-parts/pdf/partials/billing', ['id' => $id, 'billing' => $billing]) ?>
</page>
<?php if(is_payment_enabled()): ?>
    <page>
        
        <!-- Balance -->
        <?= hw_get_template('template-parts/pdf/partials/card', ['id' => $id, 'customer' => $customer]) ?>

        <!-- Log -->
        <?= hw_get_template('template-parts/pdf/partials/log', ['id' => $id]) ?>

    </page>
<?php endif; ?>
