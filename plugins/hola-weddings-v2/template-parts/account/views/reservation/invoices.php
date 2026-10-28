<?php

$invoices = hw_get_quote_invoices(get_the_ID());
$columns = hw_get_frontend_invoices_columns();

if(!empty($invoices)): ?>
    <table id="invoices-table" class="table d-none d-md-table" style="width: 100%">
        <thead>
            <tr>
                <?php foreach($columns as $slug => $column): ?>
                    <th><?= $column ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach($invoices as $post): ?>
                <tr>
                    <?php foreach($columns as $slug => $column): ?>
                        <td><?php hw_display_frontend_invoices_columns($post, $slug) ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach;?>
        </tbody>
    </table>
    <div id="mobile-invoices-list" class="d-md-none">
        <?php $i = 1; foreach($invoices as $invoice): 
            $status = $invoice->getStatus();
            ?>
            <div class="<?= $i < count($invoices) ? 'border-bottom' : '' ?> py-3">
                <?php if($status): ?>
                    <span class="d-inline-block mb-0 alert alert-<?= hw_get_invoice_alert_class($status) ?> rounded-pill py-1 py-md-2">
                        <?= hw_get_invoice_status_label($status) ?>
                    </span>
                    <div class="font-weight-bold mt-2 mb-3" style="font-size: 1.1em;"><?= $invoice->getTitle() ?></div>
                    <div class="mb-1">
                        <span><?= __('Due Date', 'hola-weddings') ?>:</span>
                        <?= $invoice->getDueDate('d F Y') ?>
                    </div>
                    <div>
                        <span><?= __('Due Amount', 'hola-weddings') ?>:</span>
                        <?= hw_format_price($invoice->getAmount(), $invoice->getCurrency()) ?>
                    </div>
                    <div class="actions text-right mt-2">
                        <?php do_action('hw_frontend_quote_invoices_actions_column', $invoice); ?>
                    </div>
                <?php $i++; endif; ?>
            </div>
        <?php endforeach;?>
    </div>
    <?php else: ?>
        <p><?= __('No invoices found', 'hola-weddings') ?></p>
    <?php endif; ?>