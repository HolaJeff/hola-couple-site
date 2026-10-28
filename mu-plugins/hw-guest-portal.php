<?php
/**
 * Plugin Name: HW guest self-service portal
 * Description: Magic-link (no-login) access to the couple-site reservation page, plus a
 * "Manage your booking" panel where the guest edits safe fields directly (email, phone,
 * special requests) and REQUESTS bigger changes (cancel / date / name / occupancy / room)
 * that land on the advisor's platform board for approval. No plugin edit: access is granted
 * request-scoped via a token that matches the quote's hw_access_token; the manage panel is
 * injected on the reservation page's hw_account_reservation_after_title hook; writes reach
 * Zoho through the hw-cv-autocharge REST helpers (loaded first, c < g).
 *
 * v2 (Phase 4 guest identity): tokens EXPIRE (network option hw_guest_link_days, default 90)
 * and ROTATE when a fresh link is generated after expiry; the platform can rotate or revoke a
 * link (POST /v1/zoho-plugin/guest-link, App-Password + manage_network); the raw token never
 * appears in page HTML (the manage forms carry an hourly HMAC nonce instead); the nopriv
 * handler is rate-limited per IP and per quote and stamps last-used for the audit.
 * Deploy to wp-content/mu-plugins/.
 */
if (!defined('ABSPATH')) exit;

// ── token + link helpers ─────────────────────────────────────────────────────
function hw_guest_link_days() {
    $d = (int) get_network_option(null, 'hw_guest_link_days', 90);
    return $d > 0 ? $d : 90;
}

/** The quote's current valid token — minted when missing, invalid, revoked-then-rotated or expired. */
function hw_guest_access_token($quote_id, $rotate = false) {
    $quote_id = (int) $quote_id;
    $tok = (string) get_post_meta($quote_id, 'hw_access_token', true);
    $issued = (int) get_post_meta($quote_id, 'hw_access_token_issued', true);
    $expired = $issued && (time() - $issued) > hw_guest_link_days() * DAY_IN_SECONDS;
    if ($rotate || !$tok || !preg_match('/^[a-f0-9]{32}$/', $tok) || $expired) {
        try { $tok = bin2hex(random_bytes(16)); }
        catch (\Throwable $e) { $tok = md5($quote_id . wp_salt() . microtime()); }
        update_post_meta($quote_id, 'hw_access_token', $tok);
        update_post_meta($quote_id, 'hw_access_token_issued', time());
        delete_post_meta($quote_id, 'hw_access_revoked');
        error_log("[GUEST-PORTAL] quote #$quote_id: guest link " . ($rotate ? 'rotated' : ($expired ? 'renewed after expiry' : 'minted')));
    }
    return $tok;
}

/** True when $k is the quote's live token: matches, not revoked, not expired. */
function hw_guest_token_valid($quote_id, $k) {
    $quote_id = (int) $quote_id;
    $k = (string) $k;
    if (!$quote_id || $k === '' || !preg_match('/^[a-f0-9]{32}$/', $k)) return false;
    if (get_post_meta($quote_id, 'hw_access_revoked', true)) return false;
    $stored = (string) get_post_meta($quote_id, 'hw_access_token', true);
    if ($stored === '' || !hash_equals($stored, $k)) return false;
    $issued = (int) get_post_meta($quote_id, 'hw_access_token_issued', true);
    if ($issued && (time() - $issued) > hw_guest_link_days() * DAY_IN_SECONDS) return false;
    return true;
}

function hw_guest_portal_link($quote_id) {
    $quote_id = (int) $quote_id;
    if (!$quote_id) return '';
    return home_url('/account/reservation/' . $quote_id . '/?k=' . hw_guest_access_token($quote_id));
}

/** Revoke: the current link stops working until something mints a new one (a new email, or the platform). */
function hw_guest_link_revoke($quote_id, $reason = '') {
    $quote_id = (int) $quote_id;
    update_post_meta($quote_id, 'hw_access_revoked', time());
    delete_post_meta($quote_id, 'hw_access_token');
    error_log("[GUEST-PORTAL] quote #$quote_id: guest link revoked" . ($reason ? " ($reason)" : ''));
}

/** Hourly HMAC the manage forms carry instead of the raw token (valid this hour and the previous one). */
function hw_guest_form_nonce($quote_id, $hour = null) {
    $tok = (string) get_post_meta((int) $quote_id, 'hw_access_token', true);
    $hour = $hour === null ? floor(time() / HOUR_IN_SECONDS) : $hour;
    return hash_hmac('sha256', (int) $quote_id . '|' . $tok . '|' . $hour, wp_salt('nonce'));
}
function hw_guest_form_nonce_ok($quote_id, $n) {
    $n = (string) $n;
    if ($n === '' || get_post_meta((int) $quote_id, 'hw_access_revoked', true)) return false;
    $h = floor(time() / HOUR_IN_SECONDS);
    return hash_equals(hw_guest_form_nonce($quote_id, $h), $n) || hash_equals(hw_guest_form_nonce($quote_id, $h - 1), $n);
}

/** Sliding-window rate limit on a transient counter: true = allowed. */
function hw_guest_rate_ok($key, $max, $window) {
    $k = 'hw_gm_rl_' . md5($key);
    $n = (int) get_transient($k);
    if ($n >= $max) return false;
    set_transient($k, $n + 1, $window);
    return true;
}
function hw_guest_client_ip() {
    return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');   // never XFF: client-controlled
}

// ── magic-link access: grant the quote's author for THIS request only ─────────
// Runs before the plugin's account gate (template_redirect prio 10). No auth
// cookie is set — access lasts one request and is scoped to a matching token.
add_action('template_redirect', function () {
    if (!function_exists('hw_is_reservation') || !hw_is_reservation()) return;
    $k = isset($_GET['k']) ? (string) $_GET['k'] : '';
    if ($k === '' || is_user_logged_in()) return;
    $qid = (int) (get_query_var('p') ?: get_queried_object_id());
    if (!$qid) return;
    if (!hw_guest_rate_ok('link|' . hw_guest_client_ip(), 60, 10 * MINUTE_IN_SECONDS)) {
        status_header(429); nocache_headers();
        wp_die('Too many attempts — please try again in a few minutes.', 'Slow down', ['response' => 429]);
    }
    if (hw_guest_token_valid($qid, $k)) {
        $post = get_post($qid);
        if ($post && (int) $post->post_author) {
            wp_set_current_user((int) $post->post_author);   // request-scoped, no cookie
            $GLOBALS['hw_guest_token_ok'] = $qid;
            update_post_meta($qid, 'hw_access_last_used', time());
            // the raw token must not be echoed into the page (hreflang/canonical/share links
            // repeat the current URL): scrub every ?k=/&k= occurrence from the response body
            ob_start(function ($html) use ($k) {
                return str_replace(['?k=' . $k, '&amp;k=' . $k, '&k=' . $k], ['', '', ''], $html);
            });
        }
    } else {
        usleep(300000);   // 0.3s: guessing 128-bit tokens is hopeless anyway, but don't make it fast
    }
}, 1);

// ── the Manage-your-booking card (injected on the reservation page) ──────────
// v3: the details / request forms moved to the platform's guest page (summary, contact
// info with extra emails/phones, guarded change requests, cancel with recorded acceptance).
// The card shows the status and ONE button; the platform verifies the guest token against
// this site (guest-link status) and opens the page. The AJAX handler below stays one more
// release for cached copies of the old card.
function hw_guest_platform_url() {
    $b = function_exists('hw_email_branding') ? hw_email_branding() : (get_network_option(null, 'hw_email_branding') ?: []);
    $u = is_array($b) ? (string) ($b['platform_url'] ?? '') : '';
    return rtrim($u ?: (string) get_network_option(null, 'hw_platform_url'), '/');
}

add_action('hw_account_reservation_after_title', function () {
    global $post;
    if (!$post || $post->post_type !== 'quote') return;
    $qid = (int) $post->ID;
    $token_ok = (($GLOBALS['hw_guest_token_ok'] ?? 0) === $qid);
    $owner_ok = is_user_logged_in() && (int) $post->post_author === get_current_user_id();
    if (!$token_ok && !$owner_ok) return;
    $status = (string) get_post_meta($qid, 'hw_quote_status', true);
    $platform = hw_guest_platform_url();
    $tok = (string) get_post_meta($qid, 'hw_access_token', true);
    if ($tok === '' || get_post_meta($qid, 'hw_access_revoked', true)) $tok = hw_guest_access_token($qid);
    $host = parse_url(home_url('/'), PHP_URL_HOST);
    $manage = $platform ? $platform . '/booking/from-site?' . http_build_query(['site' => $host, 'quote' => $qid, 'k' => $tok]) : '';
    ?>
<div class="hw-guest-manage card p-3 p-md-4 mb-4" style="background:#fbf7f0;border:1px solid #eee7dc;border-radius:12px;">
  <div class="d-flex flex-wrap align-items-center" style="gap:12px;">
    <div style="flex:1;min-width:220px;">
      <h4 class="mb-1">Manage your booking</h4>
      <p class="text-muted mb-0" style="font-size:14px;">Reservation <b><?php echo esc_html(ucfirst($status ?: 'on file')); ?></b>.
        Update your contact details and special requests, or request a change to your dates, room or guest names — your travel advisor confirms changes with the resort.</p>
    </div>
    <?php if ($manage): ?>
    <a class="btn btn-primary" href="<?php echo esc_url($manage); ?>" style="white-space:nowrap;">Manage your booking →</a>
    <?php else: ?>
    <span class="text-muted" style="font-size:13px;">Contact your travel advisor to change this booking.</span>
    <?php endif; ?>
  </div>
</div>
    <?php
});

// ── booking form: the notes field is the special-requests field ─────────────
// Label + a plain note under it; the text lands on the Zoho reservation as Special_Requests
// once the reservation exists (the booking webhook stamps zoho_reservation_id on the quote).
add_filter('option_hw_additional_notes_label', function ($v) { return $v ?: 'Special requests'; });
add_action('wp_footer', function () {
    if (is_admin()) return;
    ?>
<script>
(function(){
  var t=document.querySelector('#additional-notes textarea[name=additional_notes]'); if(!t) return;
  var n=document.createElement('div'); n.className='text-muted'; n.style.cssText='font-size:12.5px;margin-top:6px;';
  n.textContent='We forward these to the resort (room location, bedding, celebrations, dietary needs). They are requests, not guarantees.';
  t.parentNode.insertBefore(n, t.nextSibling);
})();
</script>
    <?php
});

function hw_guest_notes_sync($qid, $attempt = 1) {
    $qid = (int) $qid;
    $notes = trim((string) get_post_meta($qid, 'hw_additional_notes', true));
    if ($notes === '' || get_post_meta($qid, 'hw_special_requests_synced', true)) return;
    $rid = (string) get_post_meta($qid, 'zoho_reservation_id', true);
    if ($rid === '') {
        if ($attempt < 4) wp_schedule_single_event(time() + 120 * $attempt, 'hw_guest_notes_sync_event', [$qid, $attempt + 1]);
        return;
    }
    if (!function_exists('hw_cv_zoho_put')) return;
    $res = hw_cv_zoho_put('Reservations', $rid, ['Special_Requests' => mb_substr($notes, 0, 1000)]);
    if (is_array($res) && !empty($res['ok'])) {
        update_post_meta($qid, 'hw_special_requests', mb_substr($notes, 0, 1000));
        update_post_meta($qid, 'hw_special_requests_synced', current_time('mysql'));
        error_log("[GUEST-PORTAL] quote #$qid: special requests synced to Zoho reservation $rid");
    } elseif ($attempt < 4) {
        wp_schedule_single_event(time() + 300, 'hw_guest_notes_sync_event', [$qid, $attempt + 1]);
    } elseif (function_exists('hw_sync_dead_letter')) {
        hw_sync_dead_letter('guest-notes', $rid, 'Special_Requests not written after 4 attempts', ['quote' => $qid]);
    }
}
add_action('hw_guest_notes_sync_event', 'hw_guest_notes_sync', 10, 2);
add_action('save_post_quote', function ($post_id, $post, $update) {
    if ($update || wp_is_post_revision($post_id)) return;
    if (trim((string) get_post_meta($post_id, 'hw_additional_notes', true)) === '' && empty($_POST['additional_notes'])) return;
    wp_schedule_single_event(time() + 90, 'hw_guest_notes_sync_event', [(int) $post_id, 1]);
}, 20, 3);

// ── the write handler ────────────────────────────────────────────────────────
add_action('wp_ajax_hw_guest_manage', 'hw_guest_manage_handler');
add_action('wp_ajax_nopriv_hw_guest_manage', 'hw_guest_manage_handler');
function hw_guest_manage_handler() {
    nocache_headers();
    $qid = (int) ($_POST['qid'] ?? 0);
    $n = (string) ($_POST['n'] ?? '');
    $ip = hw_guest_client_ip();
    if (!hw_guest_rate_ok('manage|ip|' . $ip, 30, 10 * MINUTE_IN_SECONDS)
        || ($qid && !hw_guest_rate_ok('manage|quote|' . $qid, 60, HOUR_IN_SECONDS))) {
        status_header(429);
        wp_send_json(['ok' => false, 'message' => 'Too many requests — please wait a few minutes and try again.']);
    }
    if (!$qid || get_post_type($qid) !== 'quote' || !hw_guest_form_nonce_ok($qid, $n)) {
        sleep(1);
        status_header(403);
        wp_send_json(['ok' => false, 'message' => 'This page has expired — reload your reservation from the link in your email and try again.']);
    }
    $res_id = (string) get_post_meta($qid, 'zoho_reservation_id', true);
    $op = (string) ($_POST['op'] ?? '');
    $has_put = function_exists('hw_cv_zoho_put');
    update_post_meta($qid, 'hw_access_last_used', time());

    if ($op === 'update') {
        $email = sanitize_email($_POST['email'] ?? '');
        if (!empty($_POST['email']) && !is_email($email)) {
            wp_send_json(['ok' => false, 'message' => 'That email address does not look right — please check it.']);
        }
        $phone = mb_substr(sanitize_text_field($_POST['phone'] ?? ''), 0, 40);
        $special = mb_substr(sanitize_textarea_field($_POST['special_requests'] ?? ''), 0, 1000);
        // WP-side truth
        if ($email) update_post_meta($qid, 'hw_email', $email);
        $cust = get_post_meta($qid, 'hw_customer_data', true) ?: [];
        if (is_array($cust)) { $cust['email'] = $email ?: ($cust['email'] ?? ''); $cust['phone'] = $phone; update_post_meta($qid, 'hw_customer_data', $cust); }
        update_post_meta($qid, 'hw_special_requests', $special);
        // Zoho reservation
        $synced = true;
        if ($res_id && $has_put) {
            $data = array_filter([
                'Email' => $email ?: null,
                'Main_Contact_Phone' => $phone ?: null,
                'Special_Requests' => $special,
            ], function ($v) { return $v !== null; });
            if ($data) {
                $put = hw_cv_zoho_put('Reservations', $res_id, $data);
                $synced = !is_wp_error($put);
                if (!$synced && function_exists('hw_sync_dead_letter')) {
                    hw_sync_dead_letter('guest-update', $res_id, 'guest contact update did not reach Zoho: ' . $put->get_error_message(), ['quote_id' => $qid]);
                }
            }
        } elseif ($res_id && !$has_put) {
            $synced = false;
            error_log("[GUEST-PORTAL] quote #$qid: hw_cv_zoho_put unavailable - Zoho NOT updated");
        }
        wp_send_json(['ok' => true, 'message' => $synced
            ? 'Your details are saved. Thank you!'
            : 'Your details are saved here; your advisor will see the update shortly.']);
    }

    if ($op === 'request') {
        $type = mb_substr(sanitize_text_field($_POST['change_type'] ?? ''), 0, 60);
        $details = mb_substr(sanitize_text_field($_POST['details'] ?? ''), 0, 400);
        if (!$type || !$details) wp_send_json(['ok' => false, 'message' => 'Please choose a change and add a detail.']);
        $summary = "Guest change request: $type — $details";
        $ok = false;
        if ($res_id && $has_put) {
            // surface on the advisor board: Action_Status + Pending_Reason
            $put = hw_cv_zoho_put('Reservations', $res_id, [
                'Action_Status' => 'Change requested',
                'Pending_Reason' => substr($summary, 0, 250),
            ]);
            $ok = !is_wp_error($put);
            hw_guest_zoho_note($res_id, 'Guest change request', $summary . "\n(submitted from the couple site)");
            if (!$ok && function_exists('hw_sync_dead_letter')) {
                hw_sync_dead_letter('guest-request', $res_id, 'guest change request did not reach Zoho: ' . $put->get_error_message() . " — $summary", ['quote_id' => $qid]);
            }
        }
        update_post_meta($qid, 'hw_guest_change_request', ['type' => $type, 'details' => $details, 'when' => current_time('mysql'), 'synced' => $ok]);
        wp_send_json(['ok' => true, 'message' => $ok
            ? 'Request received — your advisor will review it and email you.'
            : 'Request saved — your advisor will pick it up shortly.']);
    }
    wp_send_json(['ok' => false, 'message' => 'Unknown action.']);
}

/** Create a Zoho Note on the reservation, reusing the autocharge token helper. */
function hw_guest_zoho_note($res_id, $title, $content) {
    if (!function_exists('hw_cv_zoho_access_token')) return;
    $tok = hw_cv_zoho_access_token();
    if (!$tok) return;
    wp_remote_post('https://www.zohoapis.com/crm/v2/Notes', ['timeout' => 20,
        'headers' => ['Authorization' => 'Zoho-oauthtoken ' . $tok, 'Content-Type' => 'application/json'],
        'body' => wp_json_encode(['data' => [[
            'Note_Title' => $title, 'Note_Content' => $content,
            'Parent_Id' => $res_id, 'se_module' => 'Reservations']]])]);
}

// ── platform control: rotate / revoke / inspect a guest link ─────────────────
// POST /v1/zoho-plugin/guest-link  {quote_id, action: rotate|revoke|status, reason?}
// App-Password Basic Auth + manage_network (the card-purge contract).
add_action('rest_api_init', function () {
    register_rest_route('v1/zoho-plugin', '/guest-link', [
        'methods'  => 'POST',
        'callback' => 'hw_guest_link_rest',
        'permission_callback' => function () {
            if (!is_user_logged_in()) return new WP_Error('rest_not_logged_in', 'Authentication required (Application Password).', ['status' => 401]);
            if (!current_user_can('manage_network')) return new WP_Error('rest_forbidden', 'manage_network required.', ['status' => 403]);
            return true;
        },
        'args' => [
            'quote_id' => ['required' => true, 'type' => 'integer'],
            'action'   => ['required' => true, 'type' => 'string'],
            'reason'   => ['required' => false, 'type' => 'string'],
        ],
    ]);
});
function hw_guest_link_rest(\WP_REST_Request $r) {
    $qid = (int) $r->get_param('quote_id');
    if (!$qid || get_post_type($qid) !== 'quote') return new WP_REST_Response(['status' => 'ERROR', 'message' => "quote #$qid not found"], 404);
    $action = (string) $r->get_param('action');
    $reason = sanitize_text_field((string) $r->get_param('reason'));
    if ($action === 'revoke') {
        hw_guest_link_revoke($qid, $reason ?: 'platform');
    } elseif ($action === 'rotate') {
        hw_guest_access_token($qid, true);
        error_log("[GUEST-PORTAL] quote #$qid: rotated by platform" . ($reason ? " ($reason)" : ''));
    } elseif ($action !== 'status') {
        return new WP_REST_Response(['status' => 'ERROR', 'message' => 'action must be rotate, revoke or status'], 400);
    }
    $issued = (int) get_post_meta($qid, 'hw_access_token_issued', true);
    $revoked = (int) get_post_meta($qid, 'hw_access_revoked', true);
    $tok = (string) get_post_meta($qid, 'hw_access_token', true);
    return new WP_REST_Response([
        'status'    => 'SUCCESS',
        'quote_id'  => $qid,
        'active'    => ($tok !== '' && !$revoked),
        'issued'    => $issued ? gmdate('c', $issued) : null,
        'expires'   => $issued ? gmdate('c', $issued + hw_guest_link_days() * DAY_IN_SECONDS) : null,
        'revoked'   => $revoked ? gmdate('c', $revoked) : null,
        'last_used' => ($lu = (int) get_post_meta($qid, 'hw_access_last_used', true)) ? gmdate('c', $lu) : null,
        'link'      => ($action === 'rotate' || ($action === 'status' && $tok !== '' && !$revoked)) ? hw_guest_portal_link($qid) : null,
    ], 200);
}
