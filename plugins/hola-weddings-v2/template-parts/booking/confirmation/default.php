<?php
$adults = $quote->getAdults();
$children = $quote->getChildren();
$teen = $quote->getTeen();
$status = $quote->getStatus();
?>
<div id="booking-confirmation">
    <div class="page-header">
        <div class="container">
            <h1 class="page-title text-center text-white mb-3">
                <?php 
                    switch($status){
                        case 'confirmed':
                            echo sprintf(__('Thanks %s, your reservation is Confirmed!', 'hola-weddings'), trim($quote->getFirstName()));
                            break;
                        case 'pending':
                            echo __('Your reservation is Pending!', 'hola-weddings');
                            break;
                    } 
                ?>
            </h1>
            <p class="mb-5 text-center" style="font-size: 1.3em; font-weight: 300">
                <?php 
                    switch($status){
                        case 'confirmed':
                            echo __('Your reservation has been received and is being processed.<br> You will receive your confirmation within 24-48 hours.', 'hola-weddings');
                            break;
                        case 'pending':
                            echo __('Your reservation has been received but needs a little extra work.<br> You will receive an email with the next steps within 48-hours.', 'hola-weddings');
                            break;
                    } 
                ?>
            </p>
            <div class="row">
                <div class="col-lg-8 offset-lg-2">
                <?php 
                    switch($status){
                        case 'confirmed':
                            echo '<iframe style="width: 100%; height: 400px" src="https://www.youtube.com/embed/aRKLE13XmzI?si=d-GKCFMoabxmmm7Z" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>';
                            break;
                        case 'pending':
                            echo '<iframe style="width: 100%; height: 400px" src="https://www.youtube.com/embed/joQpdbLJI-E?si=xujhtYrXzKTAheRL" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>';
                            break;
                    } 
                ?>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body pt-5">
        <div class="container">
            <h2 class="mb-3 fw-bold text-center"><?= __('Your Reservation', 'hola-weddings') ?></h2>
            <div class="text-center">
                <p class="alert alert-<?= $status == 'confirmed' ? 'success' : 'warning' ?> d-inline-block rounded-pill">
                    <?php 
                        switch($status){
                            case 'confirmed':
                                echo __('Confirmed', 'hola-weddings');;
                                break;
                            case 'pending':
                                echo __('Pending Approval', 'hola-weddings');
                                break;
                        } 
                    ?>
                </p>
            </div>
            <div class="quote-metas d-flex justify-content-center flex-wrap mt-3" style="gap: 0 3rem; row-gap: 1.5rem;">
                <div class="meta text-center">
                    <div class="meta-icon"><?= hw_get_svg_icon('passport') ?></div>
                    <div class="meta-value">
                        
                        <?= sprintf(__('Adults: %s'), $adults);  ?>
                        
                        <?php if($teen > 0): ?>
                            &#8226;
                            <?= sprintf(__('Teen: %s'), $teen);  ?>
                        <?php endif; ?>
                        
                        <?php if($children > 0): ?>
                            &#8226;
                            <?= sprintf(__('Children: %s'), $children);  ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="meta text-center">
                    <div class="meta-icon"><?= hw_get_svg_icon('calendar') ?></div>
                    <div class="meta-value"><?= sprintf(__('From %s<br>to %s', 'hola-weddings'), $quote->getCheckin()->format('F d, Y'), $quote->getCheckout()->format('F d, Y')) ?></div>
                </div>
                <div class="meta text-center">
                    <div class="meta-icon"><?= hw_get_svg_icon('bed') ?></div>
                    <div class="meta-value"><?= $quote->getRoom()['name'] ?></div>
                </div>
            </div>
            <?php
            // ── payment schedule + pay-your-balance (added 2026-09-01: guests had
            // no path from the confirmation to a payment). Pending/on-request
            // bookings skip it — nothing is chargeable until the room confirms.
            $invoices = function_exists('hw_get_quote_invoices') ? hw_get_quote_invoices($quote->getID()) : [];
            if ($status === 'confirmed' && !empty($invoices)):
                $balance = 0.0;
                $next_unpaid = null;
                foreach ($invoices as $inv) {
                    $balance += (float) $inv->getDueAmount();
                    if ($next_unpaid === null && $inv->getDueAmount() > 0) { $next_unpaid = $inv; }
                }
            ?>
            <div class="row mt-5">
                <div class="col-lg-8 offset-lg-2">
                    <h3 class="mb-1 fw-bold text-center"><?= __('Your Payment Schedule', 'hola-weddings') ?></h3>
                    <p class="text-center text-muted mb-4">
                        <?php if ($balance > 0.005): ?>
                            <?= sprintf(__('Remaining balance: <b>$%s</b> — pay any installment below whenever you\'re ready.', 'hola-weddings'), number_format($balance, 2)) ?>
                        <?php else: ?>
                            <?= __('You\'re all paid up — nothing left to do. 🎉', 'hola-weddings') ?>
                        <?php endif; ?>
                    </p>
                    <div class="table-responsive">
                        <table class="table align-middle bg-white rounded shadow-sm" style="overflow: hidden;">
                            <thead><tr>
                                <th class="ps-3"><?= __('Installment', 'hola-weddings') ?></th>
                                <th><?= __('Due date', 'hola-weddings') ?></th>
                                <th class="text-end"><?= __('Amount', 'hola-weddings') ?></th>
                                <th class="text-center"><?= __('Status', 'hola-weddings') ?></th>
                                <th class="text-end pe-3"></th>
                            </tr></thead>
                            <tbody>
                            <?php foreach ($invoices as $inv):
                                $paid = $inv->getDueAmount() <= 0.005;
                                // hw_type exists in three shapes on this network (string,
                                // sluggified string, ACF array) — normalize before the label
                                $type = $inv->getType();
                                if (is_array($type)) { $type = $type['value'] ?? ($type['label'] ?? reset($type)); }
                                $label = is_scalar($type) ? hw_get_invoice_type_label(strtolower((string) $type)) : '';
                                try { $due = $inv->getDueDate('M d, Y'); } catch (\Throwable $e) { $due = ''; }
                            ?>
                                <tr>
                                    <td class="ps-3"><?= esc_html($label ?: get_the_title($inv->getID())) ?></td>
                                    <td><?= esc_html($due ?: '—') ?></td>
                                    <td class="text-end">$<?= number_format((float) $inv->getAmount(), 2) ?></td>
                                    <td class="text-center">
                                        <?php if ($paid): ?>
                                            <span class="badge rounded-pill bg-success"><?= __('Paid', 'hola-weddings') ?></span>
                                        <?php else: ?>
                                            <span class="badge rounded-pill bg-warning text-dark"><?= __('Due', 'hola-weddings') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-3">
                                        <?php if (!$paid): ?>
                                            <a class="btn btn-sm btn-primary" href="<?= esc_url(hw_get_invoice_payment_permalink($inv)) ?>">
                                                <?= __('Pay now', 'hola-weddings') ?>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <p class="text-center text-muted" style="font-size: .85em;">
                        <?= __('Payments are processed securely — your card details are encrypted in your browser and never stored on this site.', 'hola-weddings') ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <div class="text-center mt-5">
                <a class="btn btn-primary" href="<?= hw_get_reservation_permalink($quote->getID()) ?>">
                    <?= __('View Reservation', 'hola-weddings') ?>
                </a>
                <?php if (!empty($next_unpaid)): ?>
                <a class="btn btn-outline-primary ms-2" href="<?= esc_url(hw_get_invoice_payment_permalink($next_unpaid)) ?>">
                    <?= __('Pay your next installment', 'hola-weddings') ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>