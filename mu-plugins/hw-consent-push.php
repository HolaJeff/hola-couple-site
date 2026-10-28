<?php
/**
 * Plugin Name: HW consent record to the CRM
 * Description: What a guest accepted at booking (the terms with time and IP, the agency's own
 * checkboxes, the travel-protection decision, bed preference, optional transfers) used to stay
 * on the booking site. As soon as the reservation exists in Zoho (zoho_reservation_id is written
 * on the quote), this posts the record to {platform_url}/site/{agency}/consent, signed with
 * HMAC-SHA256 over the raw body exactly like the contact form (secret pushed with the branding:
 * hw_email_branding platform_inbound_secret). The platform files it as a "Consent record" note
 * on the Reservation. A failed post is retried when the guest next opens their reservation page,
 * and by hand with: wp eval 'var_dump(hw_consent_push(QUOTE_ID, true));' --url=SITE
 * Deploy to wp-content/mu-plugins/.
 */
if (!defined('ABSPATH')) exit;

function hw_consent_cfg() {
    $b = function_exists('hw_email_branding') ? hw_email_branding() : (get_network_option(null, 'hw_email_branding') ?: []);
    $b = is_array($b) ? $b : [];
    return ['url' => rtrim((string) ($b['platform_url'] ?? get_network_option(null, 'hw_platform_url', '')), '/'),
            'agency' => (string) ($b['platform_agency'] ?? ''), 'secret' => (string) ($b['platform_inbound_secret'] ?? '')];
}

function hw_consent_payload($quote_id) {
    $m = function ($k) use ($quote_id) { return get_post_meta($quote_id, $k, true); };
    $boxes = [];
    foreach ((array) $m('hw_extra_checkboxes') as $cb) {
        if (is_array($cb) && !empty($cb['label'])) {
            $boxes[] = ['label' => (string) $cb['label'], 'accepted' => !empty($cb['accepted']), 'at' => (string) ($cb['at'] ?? '')];
        }
    }
    $plan = '';
    if ($m('hw_insurance_plan_id') && function_exists('hw_api_get_insurance_plan')) {
        $p = hw_api_get_insurance_plan($m('hw_insurance_plan_id'));
        $plan = is_array($p) ? (string) ($p['name'] ?? '') : '';
    }
    $ins = function_exists('hw_get_insurance') ? hw_get_insurance() : null;
    return [
        'reservation_id' => (string) $m('zoho_reservation_id'),
        'quote_id' => (int) $quote_id,
        'site' => (string) parse_url(home_url('/'), PHP_URL_HOST),
        'terms_accepted_at' => (string) $m('hw_toc_accepted'),
        'ip' => (string) $m('hw_consent_ip'),
        'signature' => (bool) $m('hw_signature'),
        'checkboxes' => $boxes,
        'insurance' => ['offered' => $ins && !is_wp_error($ins) && !empty($ins['plans']), 'plan' => $plan,
                        'declined_at' => (string) $m('hw_insurance_declined_at'), 'decline_text' => (string) $m('hw_insurance_decline_text')],
        'bed_preference' => (string) $m('hw_bed_preference'),
        'transfers' => (string) $m('hw_transfer_choice'),
        'ts' => time(),
    ];
}

/** @return true|string true when filed (or already filed), else the reason */
function hw_consent_push($quote_id, $force = false) {
    $quote_id = (int) $quote_id;
    if (get_post_type($quote_id) !== 'quote') return 'not a quote';
    if (!$force && get_post_meta($quote_id, 'hw_consent_pushed', true)) return true;
    if (!get_post_meta($quote_id, 'zoho_reservation_id', true)) return 'no reservation in the CRM yet';
    $cfg = hw_consent_cfg();
    if (!$cfg['url'] || !$cfg['agency'] || !$cfg['secret']) return 'platform link not configured';
    $body = wp_json_encode(hw_consent_payload($quote_id));
    $r = wp_remote_post($cfg['url'] . '/site/' . rawurlencode($cfg['agency']) . '/consent', [
        'timeout' => 12, 'headers' => ['Content-Type' => 'application/json', 'X-HW-Signature' => hash_hmac('sha256', $body, $cfg['secret'])],
        'body' => $body,
    ]);
    $code = is_wp_error($r) ? 0 : (int) wp_remote_retrieve_response_code($r);
    if ($code === 200) {
        update_post_meta($quote_id, 'hw_consent_pushed', gmdate('Y-m-d H:i:s'));
        return true;
    }
    $why = 'platform answered ' . $code . ' ' . (is_wp_error($r) ? $r->get_error_message() : substr((string) wp_remote_retrieve_body($r), 0, 200));
    error_log('[HW-CONSENT] quote ' . $quote_id . ': ' . $why);
    return $why;
}

// the moment the reservation exists in Zoho
function hw_consent_on_meta($meta_id, $post_id, $meta_key, $meta_value) {
    if ($meta_key === 'zoho_reservation_id' && $meta_value) hw_consent_push($post_id);
}
add_action('added_post_meta', 'hw_consent_on_meta', 20, 4);
add_action('updated_post_meta', 'hw_consent_on_meta', 20, 4);

// retry quietly when the guest opens their reservation
add_action('hw_account_reservation_after_title', function () {
    global $post;
    if ($post && $post->post_type === 'quote' && !get_post_meta($post->ID, 'hw_consent_pushed', true)
        && get_post_meta($post->ID, 'hw_toc_accepted', true)) {
        hw_consent_push($post->ID);
    }
}, 30);
