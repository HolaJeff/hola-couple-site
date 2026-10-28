<?php
/**
 * Plugin Name: HW sync health (money-path observability)
 * Description: One place where the couple site's Zoho/CV money-path failures LAND instead of
 * disappearing into error_log: a dead-letter list (deferred deliveries that gave up, Zoho
 * syncs that failed, declined/exception charges, guest edits that never reached Zoho) plus a
 * rolling event log (deferred parks, echo guards, replays). Read by the platform's per-wedding
 * "Sync health" panel (GET /v1/zoho-plugin/sync-health) and by a Tools → HW sync health admin
 * page; a give-up or a failed charge also emails the agency's ops address (throttled).
 * Writers: hw-cv-autocharge.php, hw-guest-portal.php (function_exists-guarded).
 * Deploy to wp-content/mu-plugins/.
 */
if (!defined('ABSPATH')) exit;

const HW_SYNC_DEAD_MAX = 200;
const HW_SYNC_EVENTS_MAX = 100;

/** Record a failure that needs a human. $kind: deferred-giveup | zoho-sync | charge-failed | charge-exception | guest-update | guest-request | other */
function hw_sync_dead_letter($kind, $ref, $message, array $ctx = [], $alert = true) {
    $list = get_option('hw_sync_dead_letters', []);
    if (!is_array($list)) $list = [];
    $entry = [
        'id'      => substr(md5(uniqid('', true)), 0, 12),
        'when'    => current_time('mysql'),
        'kind'    => (string) $kind,
        'ref'     => (string) $ref,
        'message' => mb_substr((string) $message, 0, 600),
        'ctx'     => $ctx,
        'site'    => get_bloginfo('name'),
    ];
    array_unshift($list, $entry);
    if (count($list) > HW_SYNC_DEAD_MAX) $list = array_slice($list, 0, HW_SYNC_DEAD_MAX);
    update_option('hw_sync_dead_letters', $list, false);
    error_log("[SYNC-HEALTH] dead letter $kind/$ref: $message");
    if ($alert) hw_sync_alert($kind, $ref, $message);
    return $entry['id'];
}

/** Informational trail (deferred parks, echo guards, replays) — no alert. */
function hw_sync_event($kind, $ref, $message) {
    $list = get_option('hw_sync_events', []);
    if (!is_array($list)) $list = [];
    array_unshift($list, ['when' => current_time('mysql'), 'kind' => (string) $kind, 'ref' => (string) $ref, 'message' => mb_substr((string) $message, 0, 300)]);
    if (count($list) > HW_SYNC_EVENTS_MAX) $list = array_slice($list, 0, HW_SYNC_EVENTS_MAX);
    update_option('hw_sync_events', $list, false);
}

function hw_sync_clear_dead_letter($id) {
    $list = get_option('hw_sync_dead_letters', []);
    if (!is_array($list)) return false;
    $before = count($list);
    $list = array_values(array_filter($list, function ($e) use ($id) { return ($e['id'] ?? '') !== $id; }));
    update_option('hw_sync_dead_letters', $list, false);
    return count($list) < $before;
}

/** The tenant's ops address: hw-email-templates branding (platform push) → admin_email. */
function hw_sync_ops_email() {
    if (function_exists('hw_agency_ops_email')) return hw_agency_ops_email();
    return (string) get_option('admin_email');
}

/** Email the ops address, at most once per kind+ref every 6 hours. */
function hw_sync_alert($kind, $ref, $message) {
    $key = 'hw_sync_alert_' . md5($kind . '|' . $ref);
    if (get_transient($key)) return false;
    set_transient($key, 1, 6 * HOUR_IN_SECONDS);
    $to = hw_sync_ops_email();
    if (!$to) return false;
    $site = home_url('/');
    $subject = sprintf('[%s] Money-path attention: %s (%s)', wp_specialchars_decode(get_bloginfo('name')), $kind, $ref);
    $body = "Something on the couple site needs a human.\n\nSite: $site\nKind: $kind\nRef: $ref\nWhen: " . current_time('mysql')
          . "\n\n$message\n\nOpen the wedding on the platform → Activity → Sync health, or WP admin → Tools → HW sync health.";
    $GLOBALS['hw_email_role'] = 'ops-alert';
    return wp_mail($to, $subject, $body);
}

/** Per-invoice sync flags for every quote on this site (optionally one Zoho reservation). */
function hw_sync_invoice_status($reservation_id = '') {
    $args = ['post_type' => 'quote', 'post_status' => 'any', 'numberposts' => 400, 'fields' => 'ids',
             'meta_query' => [['key' => 'zoho_reservation_id', 'compare' => 'EXISTS']]];
    if ($reservation_id !== '') $args['meta_query'] = [['key' => 'zoho_reservation_id', 'value' => (string) $reservation_id]];
    $out = [];
    foreach (get_posts($args) as $qid) {
        $rid = (string) get_post_meta($qid, 'zoho_reservation_id', true);
        $invoices = [];
        foreach (get_posts(['post_type' => 'invoices', 'post_status' => 'any', 'numberposts' => 20, 'fields' => 'ids',
                            'meta_key' => 'hw_reservation_id', 'meta_value' => $qid]) as $iid) {
            $m = function ($k) use ($iid) { $v = get_post_meta($iid, $k, true); return is_array($v) ? ($v['value'] ?? reset($v)) : $v; };
            $invoices[] = [
                'id' => $iid, 'type' => (string) $m('hw_type'), 'status' => (string) $m('hw_invoice_status'),
                'amount' => (float) $m('hw_amount'), 'due' => (string) $m('hw_due_date'),
                'zoho_payment_id' => function_exists('hwzi_get_invoice_zoho_payment_id') ? (string) hwzi_get_invoice_zoho_payment_id($iid) : '',
                'zoho_synced' => (string) $m('hw_cv_zoho_synced'),
                'autocharge_done' => (string) $m('hw_cv_autocharge_done'),
                'autocharge_error' => (string) $m('hw_cv_autocharge_error'),
            ];
        }
        $out[] = ['quote_id' => $qid, 'reservation_id' => $rid, 'status' => (string) get_post_meta($qid, 'hw_quote_status', true),
                  'card_failed_attempts' => (int) get_post_meta($qid, 'hw_card_failed_attempts', true),
                  'guest_link_active' => (bool) get_post_meta($qid, 'hw_access_token', true) && !get_post_meta($qid, 'hw_access_revoked', true),
                  'invoices' => $invoices];
    }
    return $out;
}

function hw_sync_health_snapshot($reservation_id = '') {
    $deferred = get_option('hw_cv_deferred_invoices', []);
    $deferred = is_array($deferred) ? array_map(function ($it) {
        return ['reservation_id' => (string) ($it['params']['zoho_reservation_id'] ?? ''), 'payment_id' => (string) ($it['params']['id'] ?? ''),
                'attempts' => (int) ($it['attempts'] ?? 0), 'first_seen' => isset($it['first_seen']) ? gmdate('c', (int) $it['first_seen']) : null];
    }, $deferred) : [];
    $dead = get_option('hw_sync_dead_letters', []); $dead = is_array($dead) ? $dead : [];
    $events = get_option('hw_sync_events', []); $events = is_array($events) ? $events : [];
    if ($reservation_id !== '') {
        $f = function ($e) use ($reservation_id) { return (string) ($e['ref'] ?? $e['reservation_id'] ?? '') === (string) $reservation_id || strpos((string) ($e['message'] ?? ''), (string) $reservation_id) !== false; };
        $deferred = array_values(array_filter($deferred, $f)); $dead = array_values(array_filter($dead, $f)); $events = array_values(array_filter($events, $f));
    }
    return [
        'site' => home_url('/'), 'generated' => gmdate('c'),
        'ops_email' => hw_sync_ops_email(),
        'cron_next_deferred_tick' => ($t = wp_next_scheduled('hw_cv_deferred_tick')) ? gmdate('c', $t) : null,
        'deferred' => $deferred, 'dead_letters' => $dead, 'events' => array_slice($events, 0, 40),
        'invoices' => hw_sync_invoice_status($reservation_id),
    ];
}

// ── REST: GET snapshot / POST replay|clear ────────────────────────────────────
add_action('rest_api_init', function () {
    $perm = function () {
        if (!is_user_logged_in()) return new WP_Error('rest_not_logged_in', 'Authentication required (Application Password).', ['status' => 401]);
        if (!current_user_can('manage_network')) return new WP_Error('rest_forbidden', 'manage_network required.', ['status' => 403]);
        return true;
    };
    register_rest_route('v1/zoho-plugin', '/sync-health', [
        ['methods' => 'GET', 'permission_callback' => $perm, 'callback' => function (\WP_REST_Request $r) {
            return new WP_REST_Response(hw_sync_health_snapshot((string) $r->get_param('reservation')), 200);
        }],
        ['methods' => 'POST', 'permission_callback' => $perm, 'callback' => function (\WP_REST_Request $r) {
            $action = (string) $r->get_param('action');
            if ($action === 'replay') {
                $res = function_exists('hw_cv_process_deferred') ? hw_cv_process_deferred() : 'hw_cv_process_deferred unavailable';
                hw_sync_event('replay', 'platform', $res);
                return new WP_REST_Response(['status' => 'SUCCESS', 'result' => $res], 200);
            }
            if ($action === 'clear') {
                $ok = hw_sync_clear_dead_letter((string) $r->get_param('id'));
                return new WP_REST_Response(['status' => $ok ? 'SUCCESS' : 'NOT_FOUND'], $ok ? 200 : 404);
            }
            if ($action === 'clear_all') {
                update_option('hw_sync_dead_letters', [], false);
                return new WP_REST_Response(['status' => 'SUCCESS'], 200);
            }
            return new WP_REST_Response(['status' => 'ERROR', 'message' => 'action must be replay, clear or clear_all'], 400);
        }],
    ]);
});

// ── WP admin: Tools → HW sync health ─────────────────────────────────────────
add_action('admin_menu', function () {
    add_management_page('HW sync health', 'HW sync health', 'manage_options', 'hw-sync-health', 'hw_sync_health_admin_page');
});
function hw_sync_health_admin_page() {
    if (!current_user_can('manage_options')) return;
    if (isset($_POST['hw_sync_action']) && check_admin_referer('hw_sync_health')) {
        $a = sanitize_key($_POST['hw_sync_action']);
        if ($a === 'replay' && function_exists('hw_cv_process_deferred')) { $r = hw_cv_process_deferred(); hw_sync_event('replay', 'wp-admin', $r); echo '<div class="notice notice-info"><p>Replay: ' . esc_html($r) . '</p></div>'; }
        if ($a === 'clear' && !empty($_POST['id'])) { hw_sync_clear_dead_letter(sanitize_text_field($_POST['id'])); }
        if ($a === 'clear_all') { update_option('hw_sync_dead_letters', [], false); }
    }
    $s = hw_sync_health_snapshot();
    echo '<div class="wrap"><h1>HW sync health</h1><p>Money-path failures that need a human. Alerts go to <b>' . esc_html($s['ops_email']) . '</b>.</p>';
    echo '<form method="post" style="margin:12px 0;">'; wp_nonce_field('hw_sync_health');
    echo '<button class="button button-primary" name="hw_sync_action" value="replay">Replay deferred deliveries (' . count($s['deferred']) . ')</button> ';
    echo '<button class="button" name="hw_sync_action" value="clear_all" onclick="return confirm(\'Clear every dead letter?\')">Clear all dead letters</button></form>';
    echo '<h2>Dead letters (' . count($s['dead_letters']) . ')</h2><table class="widefat striped"><thead><tr><th>When</th><th>Kind</th><th>Ref</th><th>Message</th><th></th></tr></thead><tbody>';
    foreach ($s['dead_letters'] as $e) {
        echo '<tr><td>' . esc_html($e['when']) . '</td><td>' . esc_html($e['kind']) . '</td><td>' . esc_html($e['ref']) . '</td><td>' . esc_html($e['message']) . '</td><td><form method="post">';
        wp_nonce_field('hw_sync_health'); echo '<input type="hidden" name="id" value="' . esc_attr($e['id']) . '"><button class="button-link" name="hw_sync_action" value="clear">clear</button></form></td></tr>';
    }
    if (!$s['dead_letters']) echo '<tr><td colspan="5">Nothing outstanding.</td></tr>';
    echo '</tbody></table>';
    echo '<h2>Deferred deliveries (' . count($s['deferred']) . ')</h2><table class="widefat striped"><thead><tr><th>Reservation</th><th>Payment</th><th>Attempts</th><th>First seen</th></tr></thead><tbody>';
    foreach ($s['deferred'] as $d) echo '<tr><td>' . esc_html($d['reservation_id']) . '</td><td>' . esc_html($d['payment_id']) . '</td><td>' . (int) $d['attempts'] . '</td><td>' . esc_html((string) $d['first_seen']) . '</td></tr>';
    if (!$s['deferred']) echo '<tr><td colspan="4">Queue empty.</td></tr>';
    echo '</tbody></table>';
    echo '<h2>Recent events</h2><table class="widefat striped"><thead><tr><th>When</th><th>Kind</th><th>Ref</th><th>Message</th></tr></thead><tbody>';
    foreach ($s['events'] as $e) echo '<tr><td>' . esc_html($e['when']) . '</td><td>' . esc_html($e['kind']) . '</td><td>' . esc_html($e['ref']) . '</td><td>' . esc_html($e['message']) . '</td></tr>';
    if (!$s['events']) echo '<tr><td colspan="4">No events yet.</td></tr>';
    echo '</tbody></table></div>';
}
