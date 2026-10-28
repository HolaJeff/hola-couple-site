<?php
/**
 * Plugin Name: HW CV Auto-charge (pilot)
 * Description: Charges the FIRST installment to Classic Vacations automatically when
 * its invoice arrives from Zoho, using the card saved at booking. Per-site opt-in:
 * option hw_cv_autocharge = 1. Group id: quote meta, else site option hw_cv_group_id.
 * Mirrors the production charge path (HW\Controllers\POS::processInvoicePayment):
 * Transaction first (PROCESSING), then ClassicVacationGateway->process(). On failure
 * the booking and invoice are untouched; the error is logged and admins are emailed.
 * Deploy to wp-content/mu-plugins/.
 */

// [1] Persist full billing on the quote at booking. The form collects
// address/city/zip but Quotes.php never stores them; a later charge needs them.
add_filter('hw_on_before_create_quote', function ($post_data, $room_block) {
    $c = $_POST['customer'] ?? [];
    foreach (['address' => 'hw_address', 'city' => 'hw_city', 'zip' => 'hw_zip'] as $src => $key) {
        if (!isset($post_data['meta_input'][$key])) {
            $post_data['meta_input'][$key] = sanitize_text_field($c[$src] ?? '');
        }
    }
    return $post_data;
}, 10, 2);

// [2] The charge itself, callable standalone (wp eval / retry).
function hw_cv_autocharge_invoice($invoice_id, $force = false) {
    $log = function ($msg) { error_log('[CV-AUTOCHARGE] ' . $msg); };
    try {
        if (!get_option('hw_cv_autocharge') && !get_site_option('hw_cv_autocharge_default') && !$force) return 'site not opted in';
        foreach (['\HW\Payment\Utilities\Charge',
                  '\HW\Integrations\ClassicVacations\Payment\ClassicVacationGateway',
                  '\HW\Models\Transaction', '\HW\Utils\BillingInformationBuilder'] as $cls) {
            if (!class_exists($cls)) { $log("class missing: $cls"); return "class missing: $cls"; }
        }
        if (!function_exists('hw_get_invoice')) return 'theme functions unavailable';

        $invoice = hw_get_invoice($invoice_id);
        if (!$invoice || !$invoice->exists()) return 'invoice not found';
        if (get_post_meta($invoice_id, 'hw_cv_autocharge_done', true)) return 'already charged';
        // hw_type arrives in THREE shapes: 'First' (hand-made), 'first' (create
        // importer, sluggified), and array('value'=>'first') (update importer on
        // re-delivery -- bit us 2026-08-24, invoice #518 skipped after a retry
        // rewrote it). Normalize, then compare case-insensitively.
        $ht = get_post_meta($invoice_id, 'hw_type', true);
        if (is_array($ht)) { $ht = $ht['value'] ?? ($ht['label'] ?? reset($ht)); }
        if (strcasecmp((string) $ht, 'first') !== 0 && !$force) return 'not the First installment';
        if ($invoice->getDueAmount() <= 0) return 'nothing due';

        $quote = $invoice->getQuote();
        if (!$quote || !$quote->hasCardData()) { $log("invoice #$invoice_id: no saved card"); return 'no saved card'; }

        // availability condition: only auto-charge CONFIRMED bookings; pending /
        // on-request rooms get charged after the TO confirms, by the existing flow.
        $status = get_post_meta($quote->getID(), 'hw_quote_status', true);
        if ($status && stripos($status, 'confirmed') === false && !$force) return "quote status '$status' - skipped";

        $group_id = $quote->get('hw_cv_group_id');
        if (empty($group_id)) {
            $group_id = get_option('hw_cv_group_id', '');
            if (empty($group_id)) {
                $group_id = hw_cv_group_id_from_wedding();
                if (!empty($group_id)) { update_option('hw_cv_group_id', $group_id); $log("group id $group_id resolved from the wedding's Booking_Number"); }
            }
            // Persist the fallback on the quote: the pending-transactions reviewer
            // (cron-functions.php:21) reads hw_cv_group_id from the QUOTE only, so a
            // charge made with the site-option fallback could never be settled by the
            // poller -- Payment::getRecord threw "Either Booking ID or Group ID should be
            // provided" on every run and tx #565 sat PENDING while CV said SUCCESS (cv9).
            if (!empty($group_id)) update_post_meta($quote->getID(), 'hw_cv_group_id', $group_id);
        }
        if (empty($group_id)) { $log("invoice #$invoice_id: no group id"); return 'no group id'; }
        $booking_id = $quote->get('hw_cv_booking_id'); // empty => group-level (patched payload omits it)

        $m = function ($k) use ($quote) { return (string) get_post_meta($quote->getID(), $k, true); };
        $billing = \HW\Utils\BillingInformationBuilder::build(
            $m('hw_first_name'), $m('hw_last_name'), $m('hw_email'), null,
            $m('hw_address') ?: '-', $m('hw_city') ?: '-', $m('hw_state'),
            $m('hw_country') ?: 'US', $m('hw_zip') ?: '00000'
        );
        $card = hw_cv_convert_card_type($quote->getCardData());

        $charge = new \HW\Payment\Utilities\Charge();
        $charge->setAmount($invoice->getDueAmount())
            ->setGroupID($group_id)
            ->setBookingID($booking_id)
            ->setCard($card)
            ->setBillingInformation($billing)
            ->setRefID(hw_cv_generate_ref_id('I-' . $invoice->getID()));

        // transaction FIRST, status PROCESSING - identical to the live POS path.
        // PCI: the transaction record needs the card for display/audit only —
        // store last-4, never the full PAN (plaintext JSON meta).
        $card_store = $card->toArray();
        unset($card_store['ccv']);
        if (!empty($card_store['number']) && strpos((string) $card_store['number'], '****') !== 0) {
            $card_store['number'] = '****' . substr((string) $card_store['number'], -4);
        }
        $meta = [
            'hw_currency' => $charge->getCurrency(),
            'hw_amount' => $charge->getAmount(),
            'hw_status' => \HW\Enums\TransactionStatus::PROCESSING->value,
            'hw_transaction_id' => $charge->getRefID(),
            'hw_invoice_id' => $invoice->getID(),
            'hw_billing_information' => json_encode($billing->toArray()),
            'hw_charge' => json_encode($charge->toArray()),
            'hw_card' => json_encode($card_store),
            'hw_signature' => $m('hw_signature'),
            'hw_ip_address' => function_exists('hw_get_the_user_ip') ? hw_get_the_user_ip() : 'auto',
        ];
        $tx = \HW\Models\Transaction::create($charge->getRefID(), $meta);
        if (is_wp_error($tx)) { $log("invoice #$invoice_id: transaction failed: " . $tx->get_error_message()); return 'transaction failed'; }

        $gateway = new \HW\Integrations\ClassicVacations\Payment\ClassicVacationGateway();
        $tx->set('hw_gateway', $gateway->getId());
        $log("invoice #$invoice_id: charging " . $charge->getAmount() . " to group $group_id"
            . ($booking_id ? " booking $booking_id" : ' (group-level)'));

        // The gateway returns TRUE whenever Classic ANSWERED — it sets the transaction to
        // FAILED on a decline ("payment_status":"FAIL") but does not return false, so the
        // transaction status is the truth (Martina, 2026-09-14: "Declined: Invalid Account"
        // was logged as charged OK, the CVV destroyed and Zoho stamped Charged).
        $processed = $gateway->process($charge, $tx);
        $declined = ($tx->getStatus() === \HW\Enums\TransactionStatus::FAILED->value);
        if (!$processed || $declined) {
            if (!$processed) $tx->setStatus(\HW\Enums\TransactionStatus::FAILED);
            $reason = '';
            $raw = $tx->get('hw_cv_response_body');
            if ($raw && ($j = json_decode($raw, true)) && is_array($j)) {
                $reason = (string) ($j['error_message'] ?? $j['errorDescription'] ?? $j['message'] ?? '');
            }
            // Group-level nuance (proven 2026-08-21): the charge POST can SUCCEED while
            // the follow-up record-fetch fails (GET /v2/payments/{ref}?group_id= without
            // a booking id returns nothing), making a successful charge look declined.
            $maybe = empty($booking_id)
                ? ' GROUP-LEVEL: the charge may have SUCCEEDED - verify bank/CV portal BEFORE retrying; if money moved, confirm the transaction manually.'
                : '';
            $what = $declined ? ('card DECLINED' . ($reason !== '' ? " ($reason)" : '')) : 'gateway returned false';
            if ($declined) $maybe = '';                       // a decline is definitive — no "may have succeeded"
            $log("invoice #$invoice_id: CHARGE FAILED ($what)." . $maybe);
            update_post_meta($invoice_id, 'hw_cv_autocharge_error', $what . $maybe);
            $guest = trim((string) get_post_meta($quote->getID(), 'hw_first_name', true) . ' ' . (string) get_post_meta($quote->getID(), 'hw_last_name', true));
            $msg = "Invoice #$invoice_id (quote #{$quote->getID()}" . ($guest !== '' ? ", $guest" : '') . "): $what." . $maybe
                . "\nThe booking stays intact and the card stays on file. Send the guest a payment link, or charge again once they give a new card."
                . (function_exists('hw_guest_portal_link') ? "\nReservation: " . hw_guest_portal_link($quote->getID()) : '');
            if (function_exists('hw_sync_dead_letter')) {
                hw_sync_dead_letter('charge-failed', (string) get_post_meta($quote->getID(), 'zoho_reservation_id', true), $msg,
                    ['invoice_id' => $invoice_id, 'quote_id' => $quote->getID()]);
            }
            // the team always hears about a declined charge (the dead letter is the record, the mail is the nudge)
            $GLOBALS['hw_email_role'] = '';
            wp_mail(function_exists('hw_agency_ops_email') ? hw_agency_ops_email() : get_option('admin_email'),
                    ($declined ? 'Card declined' : 'CV auto-charge failed') . ($guest !== '' ? " — $guest" : ''), $msg);
            // the on-request flow put "Held" on the reservation; a decline keeps it there
            // (the card is still on file) and CARD-ATTEMPTS stamps the failure counter
            if (function_exists('hw_card_attempts_flush')) hw_card_attempts_flush();
            return 'charge failed';
        }

        do_action('hw_pos_payment_processed', $tx);
        hw_cv_reconcile_invoice($invoice_id, 'after charge');
        if ($tx->getStatus() === \HW\Enums\TransactionStatus::PENDING->value) hw_cv_schedule_recheck($tx->getID());
        update_post_meta($invoice_id, 'hw_cv_autocharge_done', current_time('mysql'));
        // PCI hygiene: the CVV's only job was this first charge — destroy it now
        // (PAN stays for later installments; a decline/cancel purges everything via
        // the card-purge route). Also flag the Zoho reservation as Charged so the
        // platform's card-state chip is truthful. Best-effort — never fail a charge.
        try {
            $cd = $quote->getCardData();
            if ($cd && method_exists($cd, 'getCCV') && $cd->getCCV() !== '') {
                $cd->setCCV('');
                $quote->setCardData($cd);
                $log("invoice #$invoice_id: CVV destroyed after first charge");
            }
            $zres = get_post_meta($quote->getID(), 'zoho_reservation_id', true);
            if ($zres && function_exists('hw_cv_zoho_put')) {
                hw_cv_zoho_put('Reservations', $zres, ['Card_Authorization_Status' => 'Charged']);
            }
        } catch (\Throwable $hx) { $log("invoice #$invoice_id: post-charge hygiene skipped: " . $hx->getMessage()); }
        $log("invoice #$invoice_id: charged OK, ref " . $charge->getRefID());
        return 'charged: ' . $charge->getRefID();

    } catch (\Throwable $e) {
        $log("invoice #$invoice_id: EXCEPTION " . $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine());
        update_post_meta($invoice_id, 'hw_cv_autocharge_error', $e->getMessage());
        if (function_exists('hw_sync_dead_letter')) {
            hw_sync_dead_letter('charge-exception', (string) $invoice_id, "Invoice #$invoice_id: " . $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine(), ['invoice_id' => $invoice_id]);
        } else {
            wp_mail(function_exists('hw_agency_ops_email') ? hw_agency_ops_email() : get_option('admin_email'), 'CV auto-charge exception',
                "Invoice #$invoice_id: " . $e->getMessage());
        }
        return 'exception: ' . $e->getMessage();
    }
}

// [3] Trigger: fire when the Zoho importer finishes writing the invoice.
// zoho_payment_id is the LAST meta the importer sets, so everything is present.
add_action('added_post_meta', function ($meta_id, $post_id, $key) {
    if ($key !== 'zoho_payment_id' || get_post_type($post_id) !== 'invoices') return;
    // Self-healing WPInvoiceID stamp. The Deluge sender stamps it from the
    // create RESPONSE, but when four payment webhooks land in the same second
    // one can miss (Norm's Second, 2026-09-01) — and a payment without
    // WPInvoiceID makes every reminder fall back to the read-only invoice
    // page instead of the checkout. Stamping again is an idempotent PUT.
    $zpid = get_post_meta($post_id, 'zoho_payment_id', true);
    if ($zpid) { hw_cv_stamp_wp_invoice_id((string) $zpid, (int) $post_id); }
    $result = hw_cv_autocharge_invoice($post_id);
    error_log("[CV-AUTOCHARGE] trigger for invoice #$post_id -> $result");
}, 10, 3);


// [4] Synchronous WP->Zoho invoice sync. The stock path dispatches a WP_Async_Request,
// which POSTs to the site's OWN public URL - and this server cannot hairpin to itself,
// so paid-status updates never reached Zoho. Collect changed fields per invoice and
// push them straight through the SDK on shutdown (one Zoho call per invoice/request).
// The async dispatch still fires; if loopback ever works the duplicate PUT is idempotent.
function hw_cv_zoho_sync_queue() {
    static $queue = [];
    static $accessor = null;
    if ($accessor === null) {
        // Keyed by blog id: the pending-transactions poller is a network-wide CLI loop
        // (switch_to_blog per site) and this flush runs at shutdown AFTER
        // restore_current_blog(), so an invoice queued on blog 652 would be looked up
        // on the main site, not found, and silently skipped (cv9, 2026-08-26).
        $accessor = function ($invoice_id = null, $field = null) use (&$queue) {
            if ($invoice_id !== null) { $queue[get_current_blog_id()][$invoice_id][$field] = true; return null; }
            $out = $queue; $queue = [];
            return $out;
        };
    }
    return $accessor;
}
add_action('hw_model_invoices_metadata_updated', function ($name, $new_value, $old_value, $model) {
    if (!in_array($name, ['hw_amount', 'hw_due_date', 'hw_invoice_status', 'hw_type', 'hw_paid_date'], true)) return;
    if (!function_exists('hwzi_get_invoice_zoho_payment_id')) return;
    if (!hwzi_get_invoice_zoho_payment_id($model->getID())) return;
    $q = hw_cv_zoho_sync_queue();
    $q($model->getID(), $name);
}, 20, 4);
function hw_cv_zoho_flush() {
    $q = hw_cv_zoho_sync_queue();
    $flush = $q();
    if (!$flush) return;
    if (!class_exists('\HWZoho\API\Auth\OAuth')) { error_log('[CV-ZOHOSYNC] hwzi classes unavailable'); return; }
    // OAuth::initialize() never passes a token store, so the SDK falls back to
    // getcwd().'/sdk_tokens.txt'. Under php-fpm the cwd is '/', giving the
    // '//sdk_tokens.txt' fopen failure -> fwrite(bool) that every web-context
    // sync died with (2026-08-21..24). WP-CLI worked only because its cwd is
    // public_html. Pin the cwd to ABSPATH for the SDK call, exactly like CLI.
    $cwd = getcwd();
    @chdir(ABSPATH);
    try { \HWZoho\API\Auth\OAuth::initialize(); } catch (\Throwable $e) { error_log('[CV-ZOHOSYNC] OAuth init failed (SDK fallback unavailable): ' . $e->getMessage()); }
    foreach ($flush as $blog_id => $per_blog) {
    $switched = is_multisite() && (int) $blog_id !== get_current_blog_id();
    if ($switched) switch_to_blog($blog_id);
    foreach ($per_blog as $invoice_id => $fields) {
        try {
            $invoice = hw_get_invoice($invoice_id);
            $payment_id = hwzi_get_invoice_zoho_payment_id($invoice_id);
            if (!$invoice || !$invoice->exists() || !$payment_id) continue;
            // Truth guard (phantom Paid, 2026-08-24, invoice #541): a status of
            // 'paid' that WP cannot back with a successful transaction is a mirror
            // of something Zoho told us, never a fact we established. Exporting it
            // back is how a Zoho-side test flip becomes permanent (or re-flips a
            // record a human just reverted). Only export Paid when money moved here.
            if (isset($fields['hw_invoice_status']) && $invoice->getStatus() === 'paid' && $invoice->getDueAmount() > 0) {
                error_log("[CV-ZOHOSYNC] invoice #$invoice_id: NOT exporting status=paid (due " . $invoice->getDueAmount() . ", no covering transaction)");
                unset($fields['hw_invoice_status']);
                if (!$fields) continue;
            }
            $adapter = new \HWZoho\API\CRM\Adapters\Invoices($invoice);
            $names = array_keys($fields);
            try {
                $data = $adapter->getFields($names);
            } catch (\Throwable $e) {
                // hw_type stored as a plain string (synthetic/legacy invoices) fatals the
                // adapter at Invoices.php:68 - retry without it rather than lose the sync.
                $data = $adapter->getFields(array_values(array_diff($names, ['hw_type'])));
            }
            if (!$data) continue;
            $put = hw_cv_zoho_put('Payments', (string) $payment_id, $data);
            if (is_wp_error($put)) {
                error_log("[CV-ZOHOSYNC] invoice #$invoice_id REST push failed (" . $put->get_error_message() . "), trying SDK");
                ob_start(); // Payments::updateRecord echoes "Status Code: N"; at shutdown that would land in the response body
                try { \HWZoho\API\CRM\Modules\Payments::updateRecord($payment_id, $data); } finally { $echo = trim(ob_get_clean()); }
                error_log("[CV-ZOHOSYNC] invoice #$invoice_id -> Zoho payment $payment_id via SDK: " . implode(',', $names) . ($echo ? " ($echo)" : ''));
            } else {
                error_log("[CV-ZOHOSYNC] invoice #$invoice_id -> Zoho payment $payment_id: " . implode(',', $names) . ' (REST ok)');
            }
            if (isset($fields['hw_invoice_status']) && $invoice->getStatus() === 'paid') update_post_meta($invoice_id, 'hw_cv_zoho_synced', current_time('mysql'));
        } catch (\Throwable $e) {
            error_log("[CV-ZOHOSYNC] invoice #$invoice_id FAILED: " . $e->getMessage());
            if (function_exists('hw_sync_dead_letter')) hw_sync_dead_letter('zoho-sync', (string) $invoice_id, "invoice #$invoice_id: Zoho payment sync failed: " . $e->getMessage(), ['invoice_id' => $invoice_id]);
        }
    }
    if ($switched) restore_current_blog();
    }
    @chdir($cwd);
}
add_action('shutdown', 'hw_cv_zoho_flush', 25);

// [4a] Single writer. The stock plugin ALSO pushes every invoice field change to Zoho
// through a WP_Async_Request (hwzi-hooks.php, closure on hw_model_invoices_metadata_updated),
// so a paid invoice reached Zoho as TWO edits a few seconds apart (18:45:27 async,
// 18:45:32 this plugin, cv8 2026-08-26). Zoho's Status=Paid rules fire per edit, so
// books_sync_payment ran twice inside its own idempotency window: Books INV-7 + INV-8
// for one $1 payment. Unhook the stock dispatcher; [4] carries the change (with the
// truth guard). Identified by the closure's defining file so plugin updates stay safe.
add_action('init', function () {
    global $wp_filter;
    if (empty($wp_filter['hw_model_invoices_metadata_updated'])) return;
    foreach ($wp_filter['hw_model_invoices_metadata_updated']->callbacks as $prio => $cbs) {
        foreach ($cbs as $key => $cb) {
            if (!($cb['function'] instanceof \Closure)) continue;
            $file = (new \ReflectionFunction($cb['function']))->getFileName();
            if (basename($file) === 'hwzi-hooks.php') {
                remove_action('hw_model_invoices_metadata_updated', $cb['function'], $prio);
                error_log('[CV-ZOHOSYNC] single-writer: unhooked hwzi async invoice dispatcher (prio ' . $prio . ')');
            }
        }
    }
}, 20);

// [4c] Never auto-cancel a Classic charge that is merely unsettled. The poller cancels any
// transaction still pending/processing after 24h unless the gateway filter returns
// keep_status=true -- and the CV reviewer only returns true on an API exception, not on
// a PENDING answer. Group-level charges settle asynchronously (CV literally answers
// "Payment Pending | Check status after 720 minutes", cv9 2026-08-26; cv2 sat PENDING for
// days while the bank showed the money). A cancel here would mark a real payment void.
// Keep pending for up to 7 days; after that the 24h rule applies again and a human looks.
add_filter('hw_cron_job_classic-vacations_update_pending_transactions', function ($keep_status, $model, $logger) {
    if ($keep_status) return $keep_status;
    if ($model->getStatus() !== \HW\Enums\TransactionStatus::PENDING->value) return $keep_status;
    $age = time() - (int) get_post_time('U', true, $model->getID());
    if ($age < 7 * DAY_IN_SECONDS) {
        error_log('[CV-PENDING] tx #' . $model->getID() . ' still PENDING at CV (' . round($age / 3600, 1) . 'h) - keeping, not cancelling');
        return true;
    }
    return $keep_status;
}, 20, 3);

// [4d] Deterministic paid reconciliation. The theme flips an invoice to Paid from the
// transaction hooks, computing the paid amount with a WP_Query inside the same request
// that just wrote the transaction's status; with the persistent object cache that
// query can answer from a stale cached result and the invoice stays not-paid although
// the transaction is confirmed (cv10 #572: tx confirmed, due=0 when read fresh, status
// still not-paid, so nothing reached Zoho). Recompute from a fresh query and settle it.
// Paid amount straight from the database: confirmed, published transactions for the
// invoice. The model's getPaidAmount() runs a WP_Query whose result is cached for the
// request (object cache) from the moment the transaction was inserted as 'processing';
// the later status change is meta-only and never invalidates it, so inside the charging
// request the invoice keeps reading paid=0 (cv10 #572, Gigi #582 — both fine from CLI).
function hw_cv_paid_amount_sql($invoice_id) {
    global $wpdb;
    $sql = $wpdb->prepare(
        "SELECT COALESCE(SUM(CAST(a.meta_value AS DECIMAL(12,2))),0) FROM {$wpdb->posts} p
           JOIN {$wpdb->postmeta} i ON i.post_id = p.ID AND i.meta_key = 'hw_invoice_id' AND i.meta_value = %s
           JOIN {$wpdb->postmeta} s ON s.post_id = p.ID AND s.meta_key = 'hw_status' AND s.meta_value = 'confirmed'
           LEFT JOIN {$wpdb->postmeta} a ON a.post_id = p.ID AND a.meta_key = 'hw_amount'
          WHERE p.post_type = 'transactions' AND p.post_status = 'publish'",
        (string) $invoice_id);
    return (float) $wpdb->get_var($sql);
}
function hw_cv_reconcile_invoice($invoice_id, $why = '') {
    if (!function_exists('hw_get_invoice')) return false;
    clean_post_cache($invoice_id);
    $invoice = hw_get_invoice($invoice_id);
    if (!$invoice || !$invoice->exists()) return false;
    if ($invoice->getStatus() === 'paid') return false;
    $paid_sql = hw_cv_paid_amount_sql($invoice_id);
    $paid_model = $invoice->getPaidAmount(true);
    $paid = max($paid_sql, $paid_model);
    $amount = (float) $invoice->getAmount();
    if ($paid <= 0 || $amount - $paid > 0) {
        error_log("[CV-RECONCILE] invoice #$invoice_id not settled ($why): amount $amount, paid sql=$paid_sql model=$paid_model");
        return false;
    }
    $invoice->setStatus(\HW\Models\Invoice::$STATUS_PAID);
    $invoice->setPaidDate(new \DateTime('NOW'));
    error_log("[CV-RECONCILE] invoice #$invoice_id -> paid ($why): amount $amount, paid sql=$paid_sql model=$paid_model");
    return true;
}
function hw_cv_reconcile_all() {
    $n = 0; $re = 0;
    $ids = get_posts(['post_type' => 'invoices', 'numberposts' => -1, 'fields' => 'ids', 'post_status' => 'publish']);
    foreach ($ids as $id) {
        if (get_post_meta($id, 'hw_invoice_status', true) === 'paid') {
            // paid here but the Zoho push never confirmed (failed in-request, e.g. Brenda #602): re-queue it
            if (!get_post_meta($id, 'hw_cv_zoho_synced', true) && function_exists('hwzi_get_invoice_zoho_payment_id')
                && hwzi_get_invoice_zoho_payment_id($id) && get_post_meta($id, 'hw_paid_date', true)) {
                $q = hw_cv_zoho_sync_queue(); $q($id, 'hw_invoice_status'); $q($id, 'hw_paid_date'); $re++;
            }
            continue;
        }
        if (!hw_get_transactions_by_invoice($id)) continue;         // cheap pre-filter
        if (hw_cv_reconcile_invoice($id, 'sweep')) $n++;
    }
    if ($re) { hw_cv_zoho_flush(); }
    return "reconciled $n" . ($re ? ", re-pushed $re" : '');
}
// When the poller (or anything) confirms a transaction, settle its invoice right there.
add_action('hw_model_transactions_metadata_updated', function ($name, $value, $old, $model) {
    if ($name !== 'hw_status' || $value !== \HW\Enums\TransactionStatus::CONFIRMED->value) return;
    hw_cv_reconcile_invoice((int) $model->getInvoiceID(), 'tx #' . $model->getID() . ' confirmed');
}, 20, 4);

// [4e] Zoho writes over plain REST. The SDK's per-request FileStore has now failed two
// different ways under php-fpm (fwrite on '//sdk_tokens.txt', then "getTokenById -
// Given ID is invalid" on the guest pay request, Brenda #602 2026-08-27) while WP-CLI
// always worked. Reads can keep the SDK; the one write that matters - Payment status -
// goes through wp_remote_request with an access token minted from the plugin's stored
// refresh token and cached in a transient. Falls back to the SDK if REST fails.
function hw_cv_zoho_access_token($force = false) {
    $t = $force ? '' : get_site_transient('hw_cv_zoho_access');
    if ($t) return $t;
    if (!class_exists('\HWZoho\API\Auth\OAuth')) return '';
    try {
        $stored = \HWZoho\API\Auth\OAuth::get_last_token();
        $refresh = $stored ? $stored->getRefreshToken() : '';
        if (!$refresh) return '';
        $r = wp_remote_post('https://accounts.zoho.com/oauth/v2/token', ['timeout' => 20, 'body' => [
            'grant_type' => 'refresh_token', 'refresh_token' => $refresh,
            'client_id' => \HWZoho\API\Auth\Configuration::get_client_id(),
            'client_secret' => \HWZoho\API\Auth\Configuration::get_secret_id(),
        ]]);
        $j = is_wp_error($r) ? [] : (json_decode(wp_remote_retrieve_body($r), true) ?: []);
        if (empty($j['access_token'])) { error_log('[CV-ZOHOSYNC] token mint failed: ' . (is_wp_error($r) ? $r->get_error_message() : wp_remote_retrieve_body($r))); return ''; }
        set_site_transient('hw_cv_zoho_access', $j['access_token'], 50 * MINUTE_IN_SECONDS);
        return $j['access_token'];
    } catch (\Throwable $e) { error_log('[CV-ZOHOSYNC] token mint exception: ' . $e->getMessage()); return ''; }
}
function hw_cv_zoho_get($module, $record_id, array $fields = []) {
    for ($attempt = 0; $attempt < 2; $attempt++) {
        $tok = hw_cv_zoho_access_token($attempt === 1);
        if (!$tok) return new \WP_Error('no_token', 'no Zoho access token');
        $url = "https://www.zohoapis.com/crm/v3/$module/$record_id" . ($fields ? '?fields=' . rawurlencode(implode(',', $fields)) : '');
        $r = wp_remote_get($url, ['timeout' => 25, 'headers' => ['Authorization' => 'Zoho-oauthtoken ' . $tok]]);
        if (is_wp_error($r)) return $r;
        $code = (int) wp_remote_retrieve_response_code($r);
        if ($code === 401 && $attempt === 0) continue;
        if ($code === 204) return [];
        $j = json_decode(wp_remote_retrieve_body($r), true);
        if ($code >= 300) return new \WP_Error('zoho_get', "HTTP $code " . substr(wp_remote_retrieve_body($r), 0, 200));
        return $j['data'][0] ?? [];
    }
    return new \WP_Error('zoho_get', 'unreachable');
}
// The Classic group number for this couple site = the wedding Deal's Booking_Number
// (written by the contract push). Empty string when unknown; never throws.
function hw_cv_group_id_from_wedding() {
    $wid = (string) get_option('hwz_wedding_id', '');
    if ($wid === '') return '';
    $deal = hw_cv_zoho_get('Potentials', $wid, ['Booking_Number']);
    if (is_wp_error($deal) || !is_array($deal)) { error_log('[CV-AUTOCHARGE] Booking_Number lookup failed: ' . (is_wp_error($deal) ? $deal->get_error_message() : 'no record')); return ''; }
    $bn = trim((string) ($deal['Booking_Number'] ?? ''));
    return preg_match('/^\d{3,}$/', $bn) ? $bn : '';
}
function hw_cv_zoho_put($module, $record_id, array $data) {
    $plain = [];
    foreach ($data as $k => $v) {
        if ($v instanceof \com\zoho\crm\api\util\Choice) $v = $v->getValue();
        elseif ($v instanceof \DateTimeInterface) $v = $v->format('Y-m-d');
        elseif (is_object($v) && method_exists($v, '__toString')) $v = (string) $v;
        $plain[$k] = $v;
    }
    for ($attempt = 0; $attempt < 2; $attempt++) {
        $tok = hw_cv_zoho_access_token($attempt === 1);
        if (!$tok) return new \WP_Error('no_token', 'no Zoho access token');
        $r = wp_remote_request("https://www.zohoapis.com/crm/v2/$module/$record_id", ['method' => 'PUT', 'timeout' => 25,
            'headers' => ['Authorization' => 'Zoho-oauthtoken ' . $tok, 'Content-Type' => 'application/json'],
            'body' => wp_json_encode(['data' => [$plain]])]);
        if (is_wp_error($r)) return $r;
        $code = (int) wp_remote_retrieve_response_code($r); $body = wp_remote_retrieve_body($r);
        if ($code === 401 && $attempt === 0) continue;                         // stale token: re-mint once
        $j = json_decode($body, true);
        $ok = $code < 300 && (($j['data'][0]['status'] ?? '') === 'success');
        return $ok ? ($j['data'][0] ?? true) : new \WP_Error('zoho_put', "HTTP $code " . substr($body, 0, 200));
    }
    return new \WP_Error('zoho_put', 'unreachable');
}

// [4b] One shape per field. The update importer wraps select values as
// array('value' => ...) ("fix for ACF") while the create importer and the
// theme write plain strings, so hw_type/hw_invoice_status exist in two or three
// shapes on the same site and every reader needs its own unwrap (the hw_type
// case silently skipped a chargeable invoice, #518). Collapse to the scalar as
// soon as it is written; ACF formats a scalar select into value/label anyway.
add_action('updated_post_meta', 'hw_cv_normalize_invoice_meta', 10, 4);
add_action('added_post_meta',   'hw_cv_normalize_invoice_meta', 10, 4);
function hw_cv_normalize_invoice_meta($meta_id, $post_id, $key, $value) {
    if ($key !== 'hw_invoice_status' && $key !== 'hw_type') return;
    if (!is_array($value)) { $value = maybe_unserialize($value); if (!is_array($value)) return; }
    if (get_post_type($post_id) !== 'invoices') return;
    $scalar = $value['value'] ?? ($value['label'] ?? reset($value));
    if (!is_scalar($scalar) || $scalar === '') return;
    if ($key === 'hw_type') $scalar = strtolower((string) $scalar);
    update_post_meta($post_id, $key, (string) $scalar); // re-enters with a scalar -> returns at the is_array check
}


// [5] Deferred invoice delivery. The booking Deluge returns the Zoho ids in its
// RESPONSE, which the booking form saves to the quote only after the function
// completes -- but the payment-create workflows fire mid-function, so their invoice
// webhooks arrive before the quote knows its zoho_reservation_id and the importer
// rejects them (cv4: 3 of 4 invoices lost, 2026-08-24). Intercept those deliveries,
// park the payload, and replay once the quote exists. Replays feed the importer,
// so the auto-charge trigger in [3] fires naturally when the First finally lands.
add_filter('rest_request_before_callbacks', function ($response, $handler, $request) {
    if ($response !== null) return $response;                    // someone already handled it
    // get_route() returns the registered PATTERN ("/records/(?P<post_type>...)/"),
    // NOT the literal URL -- matching on 'records/invoices' never fired and the
    // race deliveries kept getting 400s (cv6, 2026-08-24). Match the pattern and
    // read the post_type from the resolved params instead.
    $route = $request->get_route();
    if (strpos($route, '/zoho-plugin/records/') === false) return $response;
    if ($request->get_param('post_type') !== 'invoices') return $response;
    if ($request->get_method() !== 'POST') return $response;     // create only
    // The update route has (?P<id>) in the URL -- but the CREATE body also carries
    // 'id' (the Zoho Payment id), and get_param() reads body params too, so this
    // check skipped every create delivery (cv7, 2026-08-26). URL params only.
    if (isset($request->get_url_params()['id'])) return $response;
    // The sender wraps the record as {"data": {...}} (WP_Create_Payment_Webhook.dlg:
    // params.put("data", dataMap)); the route handler unwraps it before the importer.
    // Read the same shape here, with the flat form as a fallback.
    $p = $request->get_param('data');
    if (is_string($p)) $p = json_decode($p, true);
    if (!is_array($p) || empty($p['id'])) $p = $request->get_params();
    $rid = $p['zoho_reservation_id'] ?? '';
    if (empty($rid) || empty($p['id'])) return $response;         // malformed -> importer's problem
    if (!function_exists('hwzi_get_quote_by_zoho_id')) return $response;
    // One create per Zoho payment at a time. Zoho's UPDATE webhook falls back to a
    // create when WPInvoiceID is still empty, and the balance recalculation edits every
    // Payment at booking, so two creates for the same id can hit the importer in the
    // same second and both pass its exists-check (cv10: Third #574 + #575). add_option is
    // an INSERT on a unique key -> atomic. Answer DUPLICATED_RECORD, which the sender
    // treats as benign.
    $lock = 'hw_cv_inflight_' . preg_replace('/\D/', '', (string) $p['id']);
    if (function_exists('hwzi_get_invoice_by_zoho_id') && hwzi_get_invoice_by_zoho_id((string) $p['id']) instanceof \WP_Post) {
        return new \WP_Error('DUPLICATED_RECORD', 'A payment already exists for id ' . $p['id'], ['status' => 400, 'code' => 'DUPLICATED_RECORD']);
    }
    if (!add_option($lock, time(), '', 'no')) {
        $since = (int) get_option($lock);
        if ($since && time() - $since < 120) {
            error_log('[CV-DEFER] duplicate create in flight for payment ' . $p['id'] . ' - rejected');
            return new \WP_Error('DUPLICATED_RECORD', 'A payment is already being created for id ' . $p['id'], ['status' => 400, 'code' => 'DUPLICATED_RECORD']);
        }
        update_option($lock, time(), 'no');                        // stale lock (>2 min): take it over
    }
    add_action('shutdown', function () use ($lock) { delete_option($lock); }, 1);
    if (hwzi_get_quote_by_zoho_id($rid) instanceof \WP_Post) return $response;  // quote known -> normal path

    // Quote not linked yet: park the (flat) payload and answer 202 so the sender does not error.
    $p['post_type'] = 'invoices';
    $queue = get_option('hw_cv_deferred_invoices', []);
    $queue[] = ['params' => $p, 'attempts' => 0, 'first_seen' => time()];
    update_option('hw_cv_deferred_invoices', $queue, false);
    if (!wp_next_scheduled('hw_cv_deferred_tick')) {
        wp_schedule_single_event(time() + 90, 'hw_cv_deferred_tick');
    }
    error_log('[CV-DEFER] parked invoice delivery for reservation ' . $rid . ' (queue: ' . count($queue) . ')');
    if (function_exists('hw_sync_event')) hw_sync_event('deferred', (string) $rid, 'invoice delivery parked until the quote is linked (queue: ' . count($queue) . ')');
    // Must be a WP_Error: WP_REST_Server::respond_to_request() only skips the route
    // callback when this filter returns an error, so a WP_REST_Response here let the
    // importer run anyway on the not-yet-linked quote (cv7 test, 2026-08-26).
    return new \WP_Error('hw_cv_deferred', 'quote not linked yet; queued for replay', ['status' => 202, 'deferred' => true]);
}, 10, 3);

function hw_cv_process_deferred() {
    $queue = get_option('hw_cv_deferred_invoices', []);
    if (!$queue) return 'queue empty';
    if (!class_exists('\HWZoho\REST\Operations\Import\Factory')) return 'hwzi unavailable';
    $keep = [];
    foreach ($queue as $item) {
        $rid = $item['params']['zoho_reservation_id'] ?? '';
        try {
            if (!hwzi_get_quote_by_zoho_id($rid) instanceof \WP_Post) {
                $item['attempts']++;
                // give up after ~1h of retries; the nightly operator can replay by hand
                if ($item['attempts'] < 40 ) { $keep[] = $item; }
                else {
                    error_log("[CV-DEFER] giving up on reservation $rid after {$item['attempts']} attempts");
                    if (function_exists('hw_sync_dead_letter')) {
                        hw_sync_dead_letter('deferred-giveup', (string) $rid, "invoice delivery for reservation $rid gave up after {$item['attempts']} attempts (quote never linked) - Zoho payment " . ($item['params']['id'] ?? '?') . ' has no WP invoice',
                            ['payment_id' => (string) ($item['params']['id'] ?? ''), 'first_seen' => (int) ($item['first_seen'] ?? 0)]);
                    }
                }
                continue;
            }
            $importer = \HWZoho\REST\Operations\Import\Factory::create('invoices');
            $r = $importer->create($item['params']);
            if (is_wp_error($r)) {
                // DUPLICATED_RECORD means an earlier replay already landed it - drop.
                $drop = ($r->get_error_data()['code'] ?? '') === 'DUPLICATED_RECORD';
                error_log("[CV-DEFER] replay for $rid: " . $r->get_error_message() . ($drop ? ' (dropping)' : ''));
                if (!$drop) { $item['attempts']++; if ($item['attempts'] < 40) $keep[] = $item; }
            } else {
                error_log("[CV-DEFER] replayed invoice for reservation $rid -> post " . ($r['id'] ?? '?'));
                hw_cv_stamp_wp_invoice_id($item['params']['id'] ?? '', (int) ($r['id'] ?? 0));
            }
        } catch (\Throwable $e) {
            error_log("[CV-DEFER] exception replaying $rid: " . $e->getMessage());
            $item['attempts']++; if ($item['attempts'] < 40) $keep[] = $item;
        }
    }
    update_option('hw_cv_deferred_invoices', $keep, false);
    if ($keep && !wp_next_scheduled('hw_cv_deferred_tick')) {
        wp_schedule_single_event(time() + 90, 'hw_cv_deferred_tick');
    }
    return 'processed; remaining ' . count($keep);
}
add_action('hw_cv_deferred_tick', 'hw_cv_process_deferred');

// [5a] Echo guard on UPDATE deliveries. Our WPInvoiceID stamp and Zoho's balance
// recalculation both edit the Payment, so the update webhook fires and delivers Zoho's
// status of the moment -- "not-paid" for an installment WP has just charged -- and the
// update importer overwrites the invoice we had marked paid (Michael Jordan #591,
// 2026-08-27). Money collected here is the truth: drop the status (and paid date) from an
// incoming update when WP holds confirmed transactions covering the invoice.
add_filter('rest_request_before_callbacks', function ($response, $handler, $request) {
    if ($response !== null) return $response;
    if ($request->get_method() !== 'PUT') return $response;
    if (strpos((string) $request->get_route(), '/zoho-plugin/records/') === false) return $response;
    if ($request->get_param('post_type') !== 'invoices') return $response;
    $zoho_id = (string) ($request->get_url_params()['id'] ?? '');
    if (!$zoho_id || !function_exists('hwzi_get_invoice_by_zoho_id') || !function_exists('hw_cv_paid_amount_sql')) return $response;
    $post = hwzi_get_invoice_by_zoho_id($zoho_id);
    if (!$post instanceof \WP_Post) return $response;
    $incoming = $request->get_param('status');
    if ($incoming === null || strtolower((string) $incoming) === 'paid') return $response;
    $amount = (float) get_post_meta($post->ID, 'hw_amount', true);
    $paid = hw_cv_paid_amount_sql($post->ID);
    if ($amount > 0 && $paid >= $amount) {
        $request->set_param('status', null);
        $request->set_param('date_paid', null);
        error_log("[CV-ECHO] invoice #{$post->ID}: ignored status='$incoming' from Zoho update (WP holds $paid of $amount confirmed)");
        if (function_exists('hw_sync_event')) hw_sync_event('echo', (string) $post->ID, "invoice #{$post->ID}: ignored status '$incoming' from Zoho (WP holds $paid of $amount)");
    }
    return $response;
}, 9, 3);

// [5b] Event-driven replay. wp-cron does not run on this site, so parked deliveries
// waited for the 15-minute wrapper (cv8: parked 18:37, replayed 18:45). The moment we
// are waiting for is observable: the reservation PUT that writes zoho_reservation_id on
// the quote. Replay right then -- after the response has been flushed to the sender, so
// the auto-charge (~13s) never holds the Zoho invokeurl open.
function hw_cv_on_quote_linked($meta_id, $post_id, $key, $value) {
    if ($key !== 'zoho_reservation_id' || empty($value) || get_post_type($post_id) !== 'quote') return;
    $queue = get_option('hw_cv_deferred_invoices', []);
    $waiting = 0;
    foreach ($queue as $item) if (($item['params']['zoho_reservation_id'] ?? '') == $value) $waiting++;
    if (!$waiting) return;
    error_log("[CV-DEFER] quote #$post_id linked to reservation $value; $waiting parked delivery(s) -> replay at shutdown");
    add_action('shutdown', function () use ($value) {
        if (function_exists('fastcgi_finish_request')) { @fastcgi_finish_request(); }
        error_log('[CV-DEFER] event replay for reservation ' . $value . ': ' . hw_cv_process_deferred());
        hw_cv_zoho_flush();   // the prio-25 flush already ran; push what the replay just changed
    }, 30);
}
add_action('added_post_meta',   'hw_cv_on_quote_linked', 10, 4);
add_action('updated_post_meta', 'hw_cv_on_quote_linked', 10, 4);

// The sender stamps WPInvoiceID on the Zoho Payment from the create response; a
// replayed delivery never answers the sender, so stamp it ourselves or every later
// edit-webhook PUTs to an id WP does not know (404) and falls back to a create.
function hw_cv_stamp_wp_invoice_id($zoho_payment_id, $wp_id) {
    if (!$zoho_payment_id || !$wp_id || !class_exists('\HWZoho\API\Auth\OAuth')) return;
    $cwd = getcwd(); @chdir(ABSPATH);
    try {
        $put = hw_cv_zoho_put('Payments', (string) $zoho_payment_id, ['WPInvoiceID' => $wp_id]);
        if (is_wp_error($put)) {
            \HWZoho\API\Auth\OAuth::initialize();
            ob_start();
            \HWZoho\API\CRM\Modules\Payments::updateRecord((string) $zoho_payment_id, ['WPInvoiceID' => $wp_id]);
            ob_end_clean();
        }
        error_log("[CV-DEFER] stamped WPInvoiceID=$wp_id on Zoho payment $zoho_payment_id" . (is_wp_error($put) ? ' (SDK)' : ''));
    } catch (\Throwable $e) { error_log("[CV-DEFER] WPInvoiceID stamp failed for $zoho_payment_id: " . $e->getMessage()); }
    @chdir($cwd);
}

// [6] PENDING follow-up without waiting for the 15-minute cron. Classic often answers
// PENDING and then settles within minutes (cv9: SUCCESS 5 min after the charge). Fire a
// non-blocking self-request that sleeps, then runs the same review the poller runs for
// just this transaction (any gateway: it goes through the same filter), then reconciles.
function hw_cv_schedule_recheck($tx_id, $delay = 120) {
    $key = wp_hash('hw_cv_recheck|' . $tx_id . '|' . get_current_blog_id());
    $url = add_query_arg(['action' => 'hw_cv_recheck', 'tx' => (int) $tx_id, 'delay' => (int) $delay, 'k' => $key], admin_url('admin-ajax.php'));
    wp_remote_post($url, ['timeout' => 0.5, 'blocking' => false, 'sslverify' => false]);
    error_log("[CV-PENDING] tx #$tx_id: re-check scheduled in {$delay}s");
}
add_action('hw_pos_payment_processed', function ($tx) {
    if (!is_object($tx) || !method_exists($tx, 'getStatus')) return;
    // make the poller's reviewer viable for every charge path: persist the fallback
    // group id on the quote if the charge could only have used the site option
    try {
        $invoice = method_exists($tx, 'getInvoice') ? $tx->getInvoice() : null;
        $quote = $invoice && method_exists($invoice, 'getQuote') ? $invoice->getQuote() : null;
        $fallback = get_option('hw_cv_group_id', '');
        if ($quote && $fallback && !get_post_meta($quote->getID(), 'hw_cv_group_id', true)) {
            update_post_meta($quote->getID(), 'hw_cv_group_id', $fallback);
            error_log('[CV-PENDING] stamped hw_cv_group_id=' . $fallback . ' on quote #' . $quote->getID() . ' (charge path fallback)');
        }
    } catch (\Throwable $e) { /* stamping is best-effort */ }
    if ($tx->getStatus() === \HW\Enums\TransactionStatus::PENDING->value) hw_cv_schedule_recheck($tx->getID());
}, 30);
add_action('wp_ajax_nopriv_hw_cv_recheck', 'hw_cv_recheck_handler');
add_action('wp_ajax_hw_cv_recheck', 'hw_cv_recheck_handler');
function hw_cv_recheck_handler() {
    $tx_id = (int) ($_GET['tx'] ?? 0); $delay = min(240, max(10, (int) ($_GET['delay'] ?? 120)));
    if (!$tx_id || ($_GET['k'] ?? '') !== wp_hash('hw_cv_recheck|' . $tx_id . '|' . get_current_blog_id())) { status_header(403); exit; }
    ignore_user_abort(true); set_time_limit($delay + 60);
    if (function_exists('fastcgi_finish_request')) { echo 'ok'; @fastcgi_finish_request(); }
    sleep($delay);
    if (!function_exists('hw_get_transaction')) exit;
    $model = hw_get_transaction($tx_id);
    if (!$model || $model->getStatus() !== \HW\Enums\TransactionStatus::PENDING->value) exit;
    $logger = class_exists('\HW\Payment\PoS') ? (new \HW\Payment\PoS())->getLogger() : null;
    try { apply_filters("hw_cron_job_{$model->getGateway()}_update_pending_transactions", false, $model, $logger); }
    catch (\Throwable $e) { error_log("[CV-PENDING] re-check tx #$tx_id failed: " . $e->getMessage()); }
    $model = hw_get_transaction($tx_id);
    $st = $model ? $model->getStatus() : '?';
    error_log("[CV-PENDING] tx #$tx_id re-check after {$delay}s -> $st");
    if ($st === \HW\Enums\TransactionStatus::CONFIRMED->value) { hw_cv_reconcile_invoice((int) $model->getInvoiceID(), 'pending re-check'); hw_cv_zoho_flush(); }
    elseif ($st === \HW\Enums\TransactionStatus::PENDING->value) {
        $n = (int) get_post_meta($tx_id, 'hw_cv_recheck_n', true) + 1;
        update_post_meta($tx_id, 'hw_cv_recheck_n', $n);
        if ($n < 6) { hw_cv_schedule_recheck($tx_id, 120); }   // ~12 min of 2-min hops, then the cron owns it
    }
    exit;
}
