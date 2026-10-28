<?php
/**
 * Plugin Name: HW demo mail guard
 * Description: Demo/sandbox sites must never mail the production intake. Quotes.php:918
 * and hw-onrequest-hooks.php:34 hardcode hola@holaweddings.com as a recipient on EVERY
 * site, so each cv test booking on the clone opened a real ticket in production Desk
 * (2026-08-18..24, WP Mail SMTP log on blog 652). On hosts whose name contains ".demo."
 * strip that address; if nothing is left, fall back to hw_alternative_email (the demo
 * Desk) or admin_email. Deploy to wp-content/mu-plugins/.
 */
add_filter('wp_mail', function ($args) {
    $host = parse_url(home_url(), PHP_URL_HOST) ?: '';
    if (stripos($host, '.demo.') === false) return $args;
    $strip = ['hola@holaweddings.com'];
    $to = $args['to'] ?? [];
    $list = is_array($to) ? $to : preg_split('/\s*,\s*/', (string) $to, -1, PREG_SPLIT_NO_EMPTY);
    $kept = [];
    foreach ($list as $addr) {
        $bare = strtolower(trim(preg_match('/<([^>]+)>/', $addr, $m) ? $m[1] : $addr));
        if (in_array($bare, $strip, true)) continue;
        $kept[] = $addr;
    }
    if (count($kept) === count($list)) return $args;   // nothing to strip
    if (!$kept) {
        $fallback = trim((string) get_option('hw_alternative_email', ''));
        $kept = $fallback ? preg_split('/\s*,\s*/', $fallback, -1, PREG_SPLIT_NO_EMPTY) : [get_option('admin_email')];
    }
    error_log('[DEMO-MAIL-GUARD] ' . $host . ': dropped hola@ from "' . ($args['subject'] ?? '') . '" -> ' . implode(',', $kept));
    $args['to'] = is_array($to) ? $kept : implode(',', $kept);
    return $args;
}, 1);
