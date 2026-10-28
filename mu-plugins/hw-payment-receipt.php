<?php
/**
 * Plugin Name: HW payment receipt
 * Description: Emails the guest a payment confirmation the moment an installment
 * flips to PAID — the gap found on the 2026-09-01 test drive: the theme only mails
 * pending/canceled transactions, and success receipts were left to Zoho workflow
 * rules (absent on tenant orgs, and agencies shouldn't live in Zoho anyway).
 * Fires for every charge path (guest checkout, auto-charge, reconciler) via the
 * invoice-status model hook; once per invoice (hw_receipt_sent meta).
 * Deploy to wp-content/mu-plugins/.
 */

add_action('hw_model_invoices_metadata_updated', function ($name, $new_value, $old_value, $model) {
    if ($name !== 'hw_invoice_status') return;
    $status = is_array($new_value) ? ($new_value['value'] ?? '') : $new_value;
    if (strtolower((string) $status) !== 'paid') return;
    $invoice_id = (int) $model->getID();
    if (get_post_meta($invoice_id, 'hw_receipt_sent', true)) return;
    // claim first — a reconciler + webhook can both flip status in one request
    update_post_meta($invoice_id, 'hw_receipt_sent', current_time('mysql'));
    try {
        hw_receipt_send($invoice_id);
    } catch (\Throwable $e) {
        error_log("[HW-RECEIPT] invoice #$invoice_id failed: " . $e->getMessage());
    }
}, 30, 4);

function hw_receipt_send($invoice_id) {
    $invoice = function_exists('hw_get_invoice') ? hw_get_invoice($invoice_id) : null;
    if (!$invoice || !$invoice->exists()) return 'invoice not found';
    $quote = $invoice->getQuote();
    if (!$quote) return 'no quote';
    $email = get_post_meta($quote->getID(), 'hw_email', true);
    if (!$email) return 'no guest email';

    $first = trim((string) get_post_meta($quote->getID(), 'hw_first_name', true));
    $amount = (float) $invoice->getAmount();

    // installment label (hw_type lives in three shapes on this network)
    $type = get_post_meta($invoice_id, 'hw_type', true);
    if (is_array($type)) { $type = $type['value'] ?? ($type['label'] ?? reset($type)); }
    $label = function_exists('hw_get_invoice_type_label')
        ? (hw_get_invoice_type_label(strtolower((string) $type)) ?: ucfirst((string) $type))
        : ucfirst((string) $type);
    if (!$label) { $label = get_the_title($invoice_id); }

    // balance across the whole reservation + the next unpaid installment
    $balance = 0.0; $next = null; $invoiced = 0.0; $paid_now = 0.0;
    if (function_exists('hw_get_quote_invoices')) {
        foreach (hw_get_quote_invoices($quote->getID()) as $inv) {
            $due = (float) $inv->getDueAmount();
            $balance += $due;
            $invoiced += (float) $inv->getAmount();
            $paid_now += (float) $inv->getPaidAmount();
            if ($next === null && $due > 0.005) { $next = $inv; }
        }
    }
    $res_total = (float) get_post_meta($quote->getID(), 'hw_total', true);
    if ($res_total > 0.005 && $invoiced + 0.01 < $res_total) {
        // schedule not fully on the site yet -> balance from the reservation total
        $balance = max($res_total - $paid_now, 0.0);
    }

    $btn = 'style="background:#0e6b6b;color:#ffffff;padding:10px 22px;'
         . 'border-radius:8px;text-decoration:none;"';
    $body = '<p>Hola ' . esc_html($first ?: 'there') . ',</p>'
        . '<p>We\'ve received your <b>' . esc_html($label) . '</b> payment of <b>$'
        . number_format($amount, 2) . '</b> — thank you!</p>'
        . ($balance > 0.005
            ? '<p>Remaining balance on your reservation: <b>$' . number_format($balance, 2) . '</b>.</p>'
            : '<p><b>Your reservation is fully paid.</b> Nothing left to do — see you at the beach! 🎉</p>');
    $portal = function_exists('hw_guest_portal_link') ? hw_guest_portal_link($quote->getID()) : '';
    if ($next && function_exists('hw_get_invoice_payment_permalink')) {
        $ntype = get_post_meta($next->getID(), 'hw_type', true);
        if (is_array($ntype)) { $ntype = $ntype['value'] ?? ($ntype['label'] ?? reset($ntype)); }
        $nlabel = function_exists('hw_get_invoice_type_label')
            ? (hw_get_invoice_type_label(strtolower((string) $ntype)) ?: ucfirst((string) $ntype))
            : ucfirst((string) $ntype);
        try { $ndue = $next->getDueDate('F d, Y'); } catch (\Throwable $e) { $ndue = ''; }
        $body .= '<p>Your next installment: <b>' . esc_html($nlabel) . '</b> of <b>$'
            . number_format((float) $next->getAmount(), 2) . '</b>'
            . ($ndue ? ' due <b>' . esc_html($ndue) . '</b>' : '') . '.</p>'
            . '<p style="margin:18px 0;"><a href="' . esc_url($portal ?: hw_get_invoice_payment_permalink($next))
            . '" ' . $btn . '>View your reservation &amp; schedule</a></p>';
    } elseif ($portal) {
        $body .= '<p style="margin:18px 0;"><a href="' . esc_url($portal) . '" ' . $btn . '>View your reservation</a></p>';
    }
    $body .= '<p>Questions? Just reply to this email.</p><p>Warm regards</p>';

    $subject = sprintf('Payment received — %s ($%s)', $label, number_format($amount, 2));
    $headers = ['Content-Type: text/html; charset=UTF-8'];
    // tenant template override (hw-email-templates mu-plugin): when the agency
    // has published a 'payment-received' template, the wp_mail filter swaps this
    // hardcoded copy for theirs, branded. No template -> this body sends as-is.
    $nlabel_tok = ''; $ndue_tok = ''; $namount_tok = '';
    if ($next) {
        $ntype2 = get_post_meta($next->getID(), 'hw_type', true);
        if (is_array($ntype2)) { $ntype2 = $ntype2['value'] ?? ($ntype2['label'] ?? reset($ntype2)); }
        $nlabel_tok = function_exists('hw_get_invoice_type_label')
            ? (hw_get_invoice_type_label(strtolower((string) $ntype2)) ?: ucfirst((string) $ntype2))
            : ucfirst((string) $ntype2);
        try { $ndue_tok = $next->getDueDate('F d, Y'); } catch (\Throwable $e) { $ndue_tok = ''; }
        $namount_tok = '$' . number_format((float) $next->getAmount(), 2);
    }
    $couple = trim(get_option('hw_her_name', '') . ' & ' . get_option('hw_his_name', ''), ' &');
    $pay_link = ($next && function_exists('hw_get_invoice_payment_permalink'))
        ? hw_get_invoice_payment_permalink($next)
        : (function_exists('hw_get_invoice_payment_permalink')
            ? hw_get_invoice_payment_permalink($invoice) : home_url('/'));
    $paid_total = 0.0;
    if (function_exists('hw_get_quote_invoices')) {
        foreach (hw_get_quote_invoices($quote->getID()) as $pinv) {
            $paid_total += (float) $pinv->getPaidAmount();
        }
    }
    $GLOBALS['hw_email_role'] = 'payment-received';
    $GLOBALS['hw_email_ctx'] = [
        'GUEST_NAME'          => $first ?: 'there',
        'COUPLE_NAMES'        => $couple ?: get_bloginfo('name'),
        'INSTALLMENT'         => $label,
        'AMOUNT'              => '$' . number_format($amount, 2),
        'PAID'                => '$' . number_format($paid_total, 2),
        'BALANCE'             => '$' . number_format($balance, 2),
        'NEXT_PAYMENT_AMOUNT' => $namount_tok,
        'NEXT_PAYMENT_DATE'   => $ndue_tok,
        // the template's button: the reservation page (whole schedule), not one invoice
        'PAYMENT_LINK'        => $portal ?: $pay_link,
        'INVOICE_LINK'        => $pay_link,
        'RESERVATION_LINK'    => $portal ?: $pay_link,
    ];
    $sent = wp_mail($email, $subject, $body, $headers);
    error_log("[HW-RECEIPT] invoice #$invoice_id ($label, $$amount) -> $email: "
        . ($sent ? 'sent' : 'wp_mail returned false'));
    return $sent ? 'sent' : 'wp_mail failed';
}
