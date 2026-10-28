<h4><?= sprintf('#%d - %s', $quote->getID(), $quote->getFullName()) ?></h4>
<hr>
<p>
    <strong class="me-2"><?= __('Room:', 'hola-weddings') ?></strong>
    <?= $quote->getRoom()['name'] ?>
</p>
<p>
    <strong class="me-2"><?= __('Check-in:', 'hola-weddings') ?></strong>
    <?= $quote->getCheckin()->format('F d Y') ?>
</p>
<p>
    <strong class="me-2"><?= __('Check-out:', 'hola-weddings') ?></strong>
    <?= $quote->getCheckout()->format('F d Y') ?>
</p>
<p>
    <strong class="me-2"><?= __('Adults:', 'hola-weddings') ?></strong>
    <?= $quote->getAdults() ?>
</p>
<p>
    <strong class="me-2"><?= __('Teen:', 'hola-weddings') ?></strong>
    <?= $quote->getTeen() ?>
</p>
<p>
    <strong class="me-2"><?= __('Children:', 'hola-weddings') ?></strong>
    <?= $quote->getChildren() ?>
</p>
<hr>
<p>
    <strong class="me-2"><?= __('Total', 'hola-weddings') ?></strong>
    <span class="float-end fw-bold"><?= hw_format_price($quote->getTotal()) ?></span>
</p>
