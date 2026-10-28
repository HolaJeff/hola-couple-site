<?php
/**
 * Plugin Name: HW Card Attempts → Zoho
 * Description: Every failed card attempt (guest pay page, auto-charge, the pending→failed
 *   sweep) stamps the guest's Zoho Reservation — Card_Failed_Attempts, Last_Card_Failure,
 *   Last_Card_Failure_Note — so the platform's reservation card and action board surface it;
 *   the next successful charge resets the counter. Uses hw_cv_zoho_put() (hw-cv-autocharge.php).
 *   Zoho writes are queued and flushed at shutdown so the guest's response is never delayed.
 *   The running count lives on the quote (hw_card_failed_attempts) so no Zoho read is needed.
 */
if (!defined('ABSPATH')) exit;

$GLOBALS['hw_card_attempts_queue'] = [];   // zoho_reservation_id => fields to PUT (last write wins)

add_action('hw_model_transactions_metadata_updated', 'hw_card_attempts_on_status', 10, 4);
function hw_card_attempts_on_status($name, $value, $old_value, $model) {
    if ($name !== 'hw_status' || !($model instanceof \HW\Models\Transaction)) return;
    $status = is_object($value) ? (string) ($value->value ?? '') : (string) $value;
    if ($status === 'failed') hw_card_attempt_record($model, true);
    elseif ($status === 'confirmed' || $status === 'pending') hw_card_attempt_record($model, false);
}

function hw_card_attempt_record($tx, $failed) {
    static $seen = [];
    $key = $tx->getID() . ':' . ($failed ? 'f' : 'ok');
    if (isset($seen[$key])) return;
    $seen[$key] = true;
    try {
        $invoice = $tx->getInvoice();
        $quote = $invoice ? $invoice->getQuote() : null;
        if (!$quote) return;
        $qid = $quote->getID();
        $zres = (string) get_post_meta($qid, 'zoho_reservation_id', true);
        if ($zres === '') { error_log('[CARD-ATTEMPTS] quote #' . $qid . ' has no zoho_reservation_id - nothing to stamp'); return; }
        $count = (int) get_post_meta($qid, 'hw_card_failed_attempts', true);
        if ($failed) {
            $count++;
            update_post_meta($qid, 'hw_card_failed_attempts', $count);
            $note = hw_card_attempt_note($tx, $invoice);
            $data = [
                'Card_Failed_Attempts' => $count,
                'Last_Card_Failure' => date('c'),
                'Last_Card_Failure_Note' => mb_substr($note, 0, 255),
            ];
            error_log("[CARD-ATTEMPTS] tx #{$tx->getID()} FAILED -> reservation $zres attempt #$count: $note");
        } else {
            if ($count === 0) return;                       // nothing to reset
            update_post_meta($qid, 'hw_card_failed_attempts', 0);
            $data = ['Card_Failed_Attempts' => 0];
            error_log("[CARD-ATTEMPTS] tx #{$tx->getID()} succeeded -> reservation $zres counter reset (was $count)");
        }
        $GLOBALS['hw_card_attempts_queue'][$zres] = $data;
        // the on-request charge runs on shutdown (priority 40), AFTER this plugin's own
        // shutdown flush (24) — stamp straight away or the failure never reaches Zoho
        if (did_action('shutdown')) hw_card_attempts_flush();
    } catch (\Throwable $e) {
        error_log('[CARD-ATTEMPTS] skipped: ' . $e->getMessage());
    }
}

function hw_card_attempt_note($tx, $invoice) {
    $amount = (float) $tx->getAmount();
    $inv = $invoice ? ('invoice #' . $invoice->getID()) : 'no invoice';
    $reason = '';
    $raw = $tx->get('hw_cv_response_body');
    if ($raw) {
        $j = json_decode($raw, true);
        if (is_array($j)) {
            foreach (['ErrorDescription', 'errorDescription', 'Message', 'message', 'Status', 'status'] as $k) {
                if (!empty($j[$k]) && is_scalar($j[$k])) { $reason = (string) $j[$k]; break; }
            }
        }
    }
    if ($reason === '') $reason = 'card declined by the gateway';
    $who = (is_admin() || (defined('DOING_CRON') && DOING_CRON) || (defined('WP_CLI') && WP_CLI)) ? 'auto-charge' : 'guest pay page';
    return sprintf('%s: $%s on %s (%s, %s)', $reason, number_format($amount, 2), $inv, $who, current_time('Y-m-d H:i'));
}

add_action('shutdown', 'hw_card_attempts_flush', 24);   // before the autocharge Zoho flush (25)
function hw_card_attempts_flush() {
    $q = $GLOBALS['hw_card_attempts_queue'] ?? [];
    if (!$q) return;
    $GLOBALS['hw_card_attempts_queue'] = [];
    if (!function_exists('hw_cv_zoho_put')) { error_log('[CARD-ATTEMPTS] hw_cv_zoho_put unavailable - Zoho not stamped'); return; }
    foreach ($q as $zres => $data) {
        $r = hw_cv_zoho_put('Reservations', $zres, $data);
        if (is_wp_error($r)) error_log("[CARD-ATTEMPTS] reservation $zres stamp FAILED: " . $r->get_error_message());
        else error_log("[CARD-ATTEMPTS] reservation $zres stamped: " . wp_json_encode($data));
    }
}
