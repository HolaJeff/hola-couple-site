<?php
/**
 * Plugin Name: HW on-request bookings (card authorization)
 * Description: Rooms with no availability in the block (after cut-off / over-allotment)
 * book on-request WITH a card authorization: the checkout keeps the card step, explains
 * that the card is saved but NOT charged, and stores it encrypted on the quote exactly
 * like the single-occupancy flow. When the quote flips to confirmed (TO approved — Zoho
 * update or wp-admin) the stored card is charged immediately via the auto-charge; the
 * CVV is destroyed right after the first successful charge. If the TO declines, the
 * platform calls the card-purge REST route and every card byte is deleted (audited).
 * A booking posted with NO card (JS off / legacy) falls back to the old card-less path:
 * nothing stored, guest pays through the emailed payment link after confirmation.
 * A retention sweep purges cards on cancelled quotes and holds older than 45 days.
 * Deploy to wp-content/mu-plugins/.
 */

// [1] Availability check for the checkout page (admin-ajax, public).
function hw_or_is_unavailable($room_block_id, $ci, $co) {
    if (!$room_block_id || !$ci || !$co || !function_exists('hw_get_room_block')) return false;
    $rb = hw_get_room_block((int) $room_block_id);
    if (!$rb || !method_exists($rb, 'isAvailable')) return false;
    try { return !$rb->isAvailable($ci, $co); } catch (\Throwable $e) { return false; }
}
add_action('wp_ajax_hw_or_avail', 'hw_or_avail_ajax');
add_action('wp_ajax_nopriv_hw_or_avail', 'hw_or_avail_ajax');
function hw_or_avail_ajax() {
    $rb = (int) ($_GET['room_block_id'] ?? 0);
    $ci = sanitize_text_field($_GET['ci'] ?? ''); $co = sanitize_text_field($_GET['co'] ?? '');
    $adults = (int) ($_GET['adults'] ?? 2);
    $unavailable = hw_or_is_unavailable($rb, $ci, $co);
    // the card is collected for EVERY booking now — unavailable rooms hold it as an
    // authorization (charged on TO confirm) instead of charging at booking
    wp_send_json(['unavailable' => $unavailable, 'card_required' => true, 'adults' => $adults]);
}

// [2] Checkout: for an unavailable room, keep the card step but explain the
// authorization (saved, not charged; charged on confirm; destroyed on decline)
// and make the card fields required — server-side validation backs this up.
add_action('wp_footer', function () {
    if (is_admin()) return;
    ?>
<script>
(function(){
  // The booking is a single-page flow: room_block_id / ci / co / adults are filled in by
  // the app as the guest chooses, so watch them and (re)check availability on change.
  var ajax = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';
  var last = '', note = null;
  var CARD_IDS = ['card-owner', 'card-number', 'card-month', 'card-year', 'card-ccv'];
  function val(n){ var el = document.querySelector('input[name="'+n+'"], select[name="'+n+'"]'); if (el && el.value) return el.value; return new URLSearchParams(window.location.search).get(n) || ''; }
  function cardBox(){ return document.getElementById('payment-card') || document.getElementById('card-form'); }
  var holdState = false;
  // the theme hides the whole payment section (.show-if-payment) for on-request rooms —
  // a leftover from the card-less flow. Required-but-hidden inputs make the browser block
  // the native submit with NO feedback, so the section must actually be shown (and kept
  // shown: the theme re-hides it on cart events, hence the re-assert in the interval).
  function reveal(){
    document.querySelectorAll('.show-if-payment').forEach(function(el){
      if (el.style.display === 'none') el.style.display = '';
    });
  }
  function setHold(hold){
    holdState = !!hold;
    var box = cardBox(); if (!box) return;
    CARD_IDS.forEach(function(id){ var i = document.getElementById(id); if (i) { i.required = !!hold; } });
    if (hold) reveal();
    if (hold && !note) {
      note = document.createElement('div'); note.id = 'hw-or-note'; note.className = 'mt-3 bg-white p-3 p-md-5 rounded';
      note.innerHTML = '<p class="mt-0 text-uppercase font-weight-bold"><?php echo esc_js(__('Room on request — card authorization', 'hola-weddings')); ?></p>'
        + '<p><?php echo esc_js(__('This room is not currently available in the group block, so we will request it from the resort for you. Your card details are securely saved but NOT charged now. By submitting this booking you authorize us to charge your first deposit automatically as soon as the resort confirms your room. If the room cannot be confirmed, nothing is charged and your card details are permanently deleted.', 'hola-weddings')); ?></p>';
      box.parentNode.insertBefore(note, box);
    }
    if (note) note.style.display = hold ? '' : 'none';
  }
  function tick(){
    var rb = val('room_block_id'), ci = val('ci'), co = val('co'), ad = val('adults') || '2';
    var key = [rb, ci, co, ad].join('|');
    if (!rb || !ci || !co || key === last) return;
    last = key;
    fetch(ajax + '?' + new URLSearchParams({action:'hw_or_avail', room_block_id: rb, ci: ci, co: co, adults: ad}).toString(), {credentials:'same-origin'})
      .then(function(r){ return r.json(); })
      .then(function(a){ setHold(!!(a && a.unavailable)); })
      .catch(function(){ setHold(false); });
  }
  setInterval(function(){ tick(); if (holdState) reveal(); }, 700); tick();
})();
</script>
    <?php
}, 99);

// [3] Unavailable-room booking: KEEP the posted card as an authorization hold
// (hw_card_held_at stamps when). A booking with no real card falls back to the
// card-less path. NOTE: Quotes.php encrypts even an EMPTY payment array into
// hw_card, and hasCardData() only checks non-emptiness — so the decision must
// key on the actual posted card fields, or an empty blob would "have a card"
// and the confirm hook would try to charge it.
add_filter('hw_on_before_create_quote', function ($post_data, $room_block) {
    $mi = $post_data['meta_input'] ?? [];
    $pending = ($mi['hw_quote_status'] ?? '') === 'pending';
    $adults = (int) ($_POST['adults'] ?? ($mi['hw_adults'] ?? 2));
    if ($pending && $adults !== 1 && $room_block && method_exists($room_block, 'isAvailable')) {
        $ci = $mi['hw_checkin'] ?? ($_POST['ci'] ?? ''); $co = $mi['hw_checkout'] ?? ($_POST['co'] ?? '');
        if ($ci && $co && !$room_block->isAvailable($ci, $co)) {
            $p = $_POST['payment'] ?? [];
            $has_card = !empty($p['card-number']) && !empty($p['card-owner']) && !empty($p['card-ccv']);
            if ($has_card) {
                $post_data['meta_input']['hw_card_held_at'] = current_time('mysql');
                error_log('[ONREQUEST] card AUTHORIZED and held: room not available in the block (pending, ' . $adults . ' adults) — charged on TO confirm');
            } else {
                unset($post_data['meta_input']['hw_card'], $post_data['meta_input']['hw_iv']);
                $post_data['meta_input']['hw_or_no_card'] = 1;
                error_log('[ONREQUEST] card not stored (none posted): room not available in the block (pending, ' . $adults . ' adults) — payment-link path');
            }
        }
    }
    return $post_data;
}, 20, 2);

// [4] Guest-only notification. The theme's hw_onrequest_notify also mails advisors an
// "ACTION REQUIRED" (hola@ + alt) - the tour-operator ask now goes through Zoho/Desk.
add_action('init', function () {
    if (has_action('hw_after_create_quote', 'hw_onrequest_notify')) {
        remove_action('hw_after_create_quote', 'hw_onrequest_notify', 20);
        add_action('hw_after_create_quote', 'hw_or_notify_guest', 20, 3);
    }
}, 20);
function hw_or_notify_guest($post_id, $data, $room_block) {
    if (($data['quote_status'] ?? '') !== 'pending') return;
    $customer = $data['customer'] ?? [];
    $guest_email = $customer['email'] ?? get_post_meta($post_id, 'hw_email', true);
    if (empty($guest_email)) return;
    $guest_name = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
    $no_card = (bool) get_post_meta($post_id, 'hw_or_no_card', true);
    $held = (bool) get_post_meta($post_id, 'hw_card_held_at', true);
    $headers = ['Content-Type: text/html; charset=UTF-8', function_exists('hw_onrequest_from_header') ? hw_onrequest_from_header() : 'From: Hola Weddings <hola@holaweddings.com>'];
    if ($no_card) {
        $pay_line = __('No payment has been taken. Once availability is confirmed you will receive a secure link to pay your first deposit.', 'hola-weddings');
    } elseif ($held) {
        $pay_line = __('Your card details are securely held and will only be charged once the resort confirms your room — you authorized this first-deposit charge at booking. If the room cannot be confirmed, nothing is charged and your card details are permanently deleted.', 'hola-weddings');
    } else {
        $pay_line = __('Your card has NOT been charged - payment details are only used once your reservation is confirmed.', 'hola-weddings');
    }
    $body = sprintf('<p>%s</p><p>%s</p><p>%s</p>',
        sprintf(__('Hi %s,', 'hola-weddings'), esc_html($guest_name ?: __('there', 'hola-weddings'))),
        __('Thank you for your booking request. The room you selected is currently <b>on request</b>: we are confirming availability with the resort and will email you as soon as your reservation is confirmed.', 'hola-weddings'),
        $pay_line);
    // tenant template override (hw-email-templates): PAYMENT_NOTE carries the
    // per-booking card-hold/no-card sentence chosen above (raw HTML token)
    $GLOBALS['hw_email_role'] = 'wp-booking-received';
    $GLOBALS['hw_email_ctx'] = ['GUEST_NAME' => $guest_name ?: 'there',
                                'PAYMENT_NOTE' => $pay_line,
                                'RESERVATION_LINK' => function_exists('hw_guest_portal_link')
                                    ? hw_guest_portal_link($post_id) : ''];
    $GLOBALS['hw_email_raw'] = ['PAYMENT_NOTE'];
    wp_mail($guest_email, __('Your booking request has been received', 'hola-weddings'), $body, $headers);
}

// [5] Quote confirmed (Zoho update webhook via the quote importer, or the wp-admin action):
// a stored card (authorization hold or single occupancy) is charged now through the
// auto-charge; a card-less quote is left for the emailed payment link.
function hw_or_on_confirmed($meta_id, $post_id, $key, $value) {
    if ($key !== 'hw_quote_status' || $value !== 'confirmed' || get_post_type($post_id) !== 'quote') return;
    if (!function_exists('hw_get_quote') || !function_exists('hw_cv_autocharge_invoice')) return;
    $quote = hw_get_quote($post_id);
    if (!$quote || !$quote->hasCardData()) { error_log("[ONREQUEST] quote #$post_id confirmed, no card on file -> payment link path"); return; }
    $first = get_posts(['post_type' => 'invoices', 'numberposts' => 1, 'fields' => 'ids', 'post_status' => 'publish',
        'meta_query' => [['key' => 'hw_reservation_id', 'value' => $post_id], ['key' => 'hw_type', 'value' => 'first', 'compare' => 'LIKE']]]);
    if (!$first) { error_log("[ONREQUEST] quote #$post_id confirmed with card but no First invoice yet"); return; }
    add_action('shutdown', function () use ($first, $post_id) {
        if (function_exists('fastcgi_finish_request')) { @fastcgi_finish_request(); }
        error_log("[ONREQUEST] quote #$post_id confirmed with card -> charging First #{$first[0]}: " . hw_cv_autocharge_invoice($first[0]));
        if (function_exists('hw_card_attempts_flush')) hw_card_attempts_flush();   // a decline reaches Zoho
        if (function_exists('hw_cv_zoho_flush')) hw_cv_zoho_flush();
    }, 40);
}
add_action('updated_post_meta', 'hw_or_on_confirmed', 10, 4);
add_action('added_post_meta', 'hw_or_on_confirmed', 10, 4);

// [6] Card purge — the decline path. The platform's "Operator declined" action calls
// this authenticated route (Application Password Basic Auth, same contract as the
// couple-site route) and every card byte is deleted, with an audit meta. Idempotent.
add_action('rest_api_init', function () {
    register_rest_route('v1/zoho-plugin', '/card-purge', [
        'methods'  => 'POST',
        'callback' => 'hw_or_card_purge',
        'permission_callback' => function () {
            if (!is_user_logged_in()) {
                return new WP_Error('rest_not_logged_in',
                    'Authentication required. Use a WordPress Application Password (Basic Auth over HTTPS).',
                    ['status' => 401]);
            }
            if (!current_user_can('manage_network')) {
                return new WP_Error('rest_forbidden',
                    'Insufficient permissions: purging card data requires the manage_network capability.',
                    ['status' => 403]);
            }
            return true;
        },
        'args' => [
            'quote_id' => ['required' => true, 'type' => 'integer'],
            'reason'   => ['required' => false, 'type' => 'string'],
        ],
    ]);
});
function hw_or_card_purge(WP_REST_Request $req) {
    $qid = (int) $req['quote_id'];
    if (!$qid || get_post_type($qid) !== 'quote') {
        return new WP_Error('quote_not_found', "No quote #$qid on this site.", ['status' => 404]);
    }
    $had = (bool) get_post_meta($qid, 'hw_card', true);
    delete_post_meta($qid, 'hw_card');
    delete_post_meta($qid, 'hw_iv');
    update_post_meta($qid, 'hw_or_no_card', 1);   // belt: hasCardData() can never charge now
    update_post_meta($qid, 'hw_card_purged', [
        'at' => current_time('mysql'),
        'by' => wp_get_current_user()->user_login,
        'reason' => sanitize_text_field((string) ($req['reason'] ?? '')),
    ]);
    error_log("[ONREQUEST] card purged on quote #$qid (had_card=" . ($had ? '1' : '0') . ", reason=" . $req['reason'] . ")");
    return ['purged' => true, 'had_card' => $had];
}

// [7] Retention sweep (called by cv-pending-cron.php daily): purge cards on
// cancelled quotes, and on pending authorization holds older than 45 days —
// a hold the TO never answered must not rot in the database.
function hw_or_card_sweep($max_age_days = 45, $cap = 50) {
    global $wpdb;
    $ids = $wpdb->get_col(
        "SELECT p.ID FROM {$wpdb->posts} p
           JOIN {$wpdb->postmeta} c ON c.post_id = p.ID AND c.meta_key = 'hw_card' AND c.meta_value != ''
          WHERE p.post_type = 'quote' LIMIT 500");
    $purged = [];
    foreach ($ids as $qid) {
        if (count($purged) >= $cap) break;
        $status = strtolower((string) get_post_meta($qid, 'hw_quote_status', true));
        $held_at = (string) get_post_meta($qid, 'hw_card_held_at', true);
        $stale = $held_at && (strtotime($held_at) < strtotime("-{$max_age_days} days"))
                 && strpos($status, 'pending') !== false;
        $cancelled = strpos($status, 'cancel') !== false;
        if (!$cancelled && !$stale) continue;
        delete_post_meta($qid, 'hw_card');
        delete_post_meta($qid, 'hw_iv');
        update_post_meta($qid, 'hw_or_no_card', 1);
        update_post_meta($qid, 'hw_card_purged', [
            'at' => current_time('mysql'), 'by' => 'retention-sweep',
            'reason' => $cancelled ? 'quote cancelled' : "hold older than {$max_age_days} days",
        ]);
        $purged[] = "#$qid(" . ($cancelled ? 'cancelled' : 'stale') . ")";
    }
    $msg = $purged ? ('purged ' . implode(' ', $purged)) : 'nothing to purge';
    error_log('[ONREQUEST] card sweep: ' . $msg);
    return $msg;
}
