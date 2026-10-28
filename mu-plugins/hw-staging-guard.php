<?php
/**
 * Plugin Name: HW staging guard
 * Description: On staging (host contains "staging." or HW_STAGING_MODE true in wp-config):
 * every wp_mail goes ONLY to the sink (HW_STAGING_MAIL_SINK, default jeff+staging@holaweddings.com)
 * with the original recipients in the subject; card charging is disabled (see
 * hw_is_staging() checks in hw-cv-autocharge.php and ClassicVacationGateway::process);
 * an admin notice is shown. No SMS code exists in the plugins (checked 2026-10-28).
 * Deploy to wp-content/mu-plugins/.
 */
if (!function_exists('hw_is_staging')) {
    function hw_is_staging() {
        if (defined('HW_STAGING_MODE') && HW_STAGING_MODE) return true;
        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        if ($host === '' && function_exists('home_url')) $host = strtolower((string) parse_url(home_url(), PHP_URL_HOST));
        return strpos($host, 'staging.') !== false;
    }
}

// (a) Mail redirect. Priority PHP_INT_MAX so it runs after every other wp_mail filter.
add_filter('wp_mail', function ($args) {
    if (!hw_is_staging()) return $args;
    $sink = defined('HW_STAGING_MAIL_SINK') ? HW_STAGING_MAIL_SINK : 'jeff+staging@holaweddings.com';
    $flat = function ($v) { return is_array($v) ? implode(', ', $v) : (string) $v; };
    $orig = [];
    $to = $flat($args['to'] ?? '');
    if ($to !== '') $orig[] = 'to: ' . $to;
    $headers = $args['headers'] ?? [];
    $lines = is_array($headers) ? $headers : preg_split("/\r\n|\n/", (string) $headers);
    $kept = [];
    foreach ($lines as $h) {
        if (preg_match('/^\s*(cc|bcc)\s*:\s*(.*)$/i', (string) $h, $m)) { $orig[] = strtolower($m[1]) . ': ' . trim($m[2]); continue; }
        $kept[] = $h;
    }
    $args['to'] = $sink;
    $args['headers'] = $kept;
    $args['subject'] = '[STAGING ' . implode(' | ', $orig) . '] ' . ($args['subject'] ?? '');
    return $args;
}, PHP_INT_MAX);
// Belt and braces: strip any cc/bcc added directly on PHPMailer after the filter.
add_action('phpmailer_init', function ($phpmailer) {
    if (!hw_is_staging()) return;
    $sink = defined('HW_STAGING_MAIL_SINK') ? HW_STAGING_MAIL_SINK : 'jeff+staging@holaweddings.com';
    $phpmailer->clearAllRecipients();
    $phpmailer->addAddress($sink);
}, PHP_INT_MAX);

// (b) Force auto-charge options off at read time, whatever is in the DB.
foreach (['option_hw_cv_autocharge', 'site_option_hw_cv_autocharge_default'] as $hook) {
    add_filter($hook, function ($v) { return hw_is_staging() ? 0 : $v; }, PHP_INT_MAX);
}

// (d) Admin notice.
$hw_staging_notice = function () {
    if (!hw_is_staging()) return;
    echo '<div class="notice notice-warning"><p><strong>STAGING: mail redirected, charges disabled</strong></p></div>';
};
add_action('admin_notices', $hw_staging_notice);
add_action('network_admin_notices', $hw_staging_notice);
