<?php

use HW\App;

$wedding = App::getInstance()->getWedding();
$him = $wedding->getHisName();
$her = $wedding->getHerName();

$customer = $transaction->getBillingInformation();
?>
<?= hw_get_template('template-parts/pdf/partials/style') ?>
<page>
    <div style="text-align: center; margin-bottom: 30px">
        <img src="<?= HW_PLUGIN_PATH ?>/assets/img/logo.png" style="height: 70px; width: auto;" />
    </div>
    <div>
        <h1 style="margin-bottom: 10px;"><?= sprintf(__('%s and %s Wedding UCC Form', 'hola-weddings'), $him, $her); ?></h1>
        <p><?= sprintf(__('Reservation: <strong>%s %s</strong> for 1 Room', 'hola-weddings'), $customer->getFirstName(), $customer->getLastName()); ?></p>
    </div>
    <br><br>
    <!-- Transaction -->
    <?= hw_get_template('template-parts/pdf/admin-transaction-ucc/transaction', ['transaction' => $transaction]) ?>
    <br><br>
    <!-- Invoice 
    <?php //hw_get_template('template-parts/pdf/admin-transaction-ucc/invoice', ['transaction' => $transaction]) ?>
    <br><br>-->
    <!-- Reservatopm -->
    <?= hw_get_template('template-parts/pdf/admin-transaction-ucc/reservation', ['transaction' => $transaction]) ?>
    <br><br>
    <!-- Balance -->
    <?= hw_get_template('template-parts/pdf/partials/billing', ['id' => $id, 'customer' => $customer]) ?>

    <!-- Terms and cancellation -->
    <?= hw_get_template('template-parts/pdf/partials/terms', ['id' => $id]) ?>

     <!-- Card -->
    <?= hw_get_template('template-parts/pdf/admin-transaction-ucc/card', ['transaction' => $transaction, 'customer' => $customer]) ?>
</page>
