<?php
/**
 * Plugin Name: HW card crypto (v2 — per-environment key, authenticated)
 * Description: Replaces the legacy card-at-rest crypto (AES-256-CTR, key hard-coded in
 * Quotes.php, identical on every environment) with versioned AES-256-GCM under a
 * per-environment HW_CARD_KEY defined in wp-config.php. Blob format:
 *   v2:<b64 iv>:<b64 tag>:<b64 ciphertext>
 * GCM authenticates: a tampered blob REFUSES to decrypt instead of returning garbage.
 * Legacy blobs (no prefix) decrypt with the old key for migration only and are
 * upgraded to v2 on their next read. Every decrypt is appended to a capped audit log
 * (who/when/why — PCI Req-10-style accountability for card access).
 * Deploy to wp-content/mu-plugins/ and define HW_CARD_KEY in wp-config.php
 * (unique random string PER ENVIRONMENT — prod, staging and the clone must differ).
 */

// The retired in-source key — DECRYPT-ONLY, for migrating pre-v2 blobs. New data is
// never written with it. Once migration + the non-prod scrub are done it is dead
// weight kept for stragglers.
if (!defined('HW_CARD_LEGACY_KEY')) {
    define('HW_CARD_LEGACY_KEY', 'j|(aoMy3"h-W?Z|fZ]R(*MxtrLqFDK');
}

/** 32-byte key from wp-config's HW_CARD_KEY; null (loud) when unset. */
function hw_card_key() {
    if (defined('HW_CARD_KEY') && HW_CARD_KEY !== '') {
        return hash('sha256', HW_CARD_KEY, true);
    }
    error_log('[CARD-CRYPTO] HW_CARD_KEY is not defined in wp-config.php — v2 card storage unavailable');
    return null;
}


/** Encrypt a card map to the v2 format. Null when the key is missing or encryption fails. */
function hw_card_encrypt(array $map) {
    $key = hw_card_key();
    if (!$key) return null;
    $iv = random_bytes(12);
    $tag = '';
    $ct = openssl_encrypt(json_encode($map), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
    if ($ct === false) {
        error_log('[CARD-CRYPTO] v2 encrypt failed: ' . openssl_error_string());
        return null;
    }
    return 'v2:' . base64_encode($iv) . ':' . base64_encode($tag) . ':' . base64_encode($ct);
}


/**
 * Decrypt a stored card blob (v2 or legacy). $legacy_iv_hex = the hw_iv meta for
 * legacy blobs. $context is written to the audit log ("getCardData quote #123").
 * Returns the decoded card map array, or null (never garbage: GCM refuses tampering).
 */
function hw_card_decrypt($blob, $legacy_iv_hex = '', $context = '') {
    if (!is_string($blob) || $blob === '') return null;
    if (strpos($blob, 'v2:') === 0) {
        $key = hw_card_key();
        if (!$key) return null;
        $parts = explode(':', $blob, 4);
        if (count($parts) !== 4) return null;
        $json = openssl_decrypt(base64_decode($parts[3]), 'aes-256-gcm', $key, OPENSSL_RAW_DATA,
                                base64_decode($parts[1]), base64_decode($parts[2]));
        if ($json === false) {
            error_log('[CARD-CRYPTO] v2 decrypt REFUSED (auth tag mismatch — wrong key or tampered blob) ctx=' . $context);
            return null;
        }
    } else {
        // legacy AES-256-CTR under the retired in-source key
        if ($legacy_iv_hex === '') return null;
        $json = openssl_decrypt($blob, 'aes-256-ctr', HW_CARD_LEGACY_KEY, 0, hex2bin($legacy_iv_hex));
        if ($json === false) return null;
    }
    $map = json_decode($json, true);
    if (!is_array($map)) return null;
    hw_card_audit($context ?: 'unspecified');
    return $map;
}


/** Append one line to the capped card-access audit log (network option, no autoload). */
function hw_card_audit($context) {
    $log = get_option('hw_card_access_log', []);
    if (!is_array($log)) $log = [];
    $u = function_exists('wp_get_current_user') ? wp_get_current_user() : null;
    $log[] = ['t' => current_time('mysql'),
              'user' => ($u && $u->exists()) ? $u->user_login : (php_sapi_name() === 'cli' ? 'wp-cli' : 'system'),
              'ctx' => substr((string) $context, 0, 160)];
    if (count($log) > 500) $log = array_slice($log, -500);
    update_option('hw_card_access_log', $log, false);
}
