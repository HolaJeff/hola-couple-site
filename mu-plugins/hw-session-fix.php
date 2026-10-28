<?php
/**
 * Plugin Name: HW stable session id
 * Description: On this host Varnish makes PHP sessions impossible for guests: the
 * Set-Cookie for PHPSESSID is stripped from responses, AND request cookies are stripped
 * on cacheable requests unless a wordpress_* cookie is present (which flips the request
 * into cache-bypass mode) — proven with cookie probes 2026-09-01. So every guest request
 * got a fresh session id, breaking everything keyed on session_id(): the HW\Session
 * transients behind the POS invoice-payment guest access, and the booking flow's
 * validation errors (worked around separately in hw-quote-errors).
 *
 * Phase 4 (2026-09-03): the session-bearing paths are now EXCLUDED from Varnish in the
 * Cloudways panel (/invoice-payment/*, /account/*, /booking-confirmation/*,
 * /wp-admin/admin-ajax.php), so on those paths cookies flow both ways and PHP can own the
 * session cookie again — re-issued here as HttpOnly + Secure + SameSite=Lax (php.ini has
 * none of those flags). The JavaScript pin below remains ONLY for the booking form, which
 * lives on ordinary cached pages selected by a ?ci= query string that a path exclusion
 * cannot target. Deploy to wp-content/mu-plugins/.
 */
if (!defined('ABSPATH')) exit;

/** Paths Varnish passes through (keep in step with the Cloudways exclusions). */
function hw_session_path_excluded() {
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
    $path = (string) parse_url($uri, PHP_URL_PATH);
    foreach (['/invoice-payment', '/account', '/booking-confirmation', '/wp-admin/admin-ajax.php'] as $p) {
        if ($path === $p || strpos($path, $p . '/') === 0 || strpos($path, $p . '?') === 0) return true;
    }
    return false;
}

// Server side, at muplugins_loaded (nothing else has queued a Set-Cookie yet).
//  - excluded paths: PHP owns the cookie -> re-issue it with the security flags
//  - cached paths: the auto-started session's Set-Cookie would overwrite the JS pin with
//    a fresh id on every uncached subrequest -> remove it; the JS pin is the only writer
add_action('muplugins_loaded', function () {
    if (php_sapi_name() === 'cli' || headers_sent()) return;
    $had_session_cookie = false;
    foreach (headers_list() as $h) {
        if (stripos($h, 'Set-Cookie:') === 0 && strpos($h, 'PHPSESSID=') !== false) { $had_session_cookie = true; break; }
    }
    if (hw_session_path_excluded()) {
        $sid = session_status() === PHP_SESSION_ACTIVE ? session_id() : '';
        if ($sid !== '' && preg_match('/^[A-Za-z0-9,-]{20,}$/', $sid)) {
            if ($had_session_cookie) header_remove('Set-Cookie');
            setcookie('PHPSESSID', $sid, [
                'expires'  => time() + DAY_IN_SECONDS,
                'path'     => '/',
                'secure'   => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            // any request carrying wordpress_hw_bypass already bypasses the cache; on the
            // excluded paths the bypass cookie is redundant but harmless — leave it.
        }
        return;
    }
    if ($had_session_cookie) header_remove('Set-Cookie');
}, 0);

// Client side, booking form only (?ci=…): pin ONE session id per browser and force the
// cookie back to it on every load, plus a wordpress_* cookie so the cache layer passes
// our cookies through to PHP. Everything else stays cached.
add_action('wp_head', function () {
    if (!isset($_GET['ci']) || hw_session_path_excluded()) return;
    ?>
<script>
(function(){
  try {
    var sid = null;
    try { sid = localStorage.getItem('hw_sid'); } catch (e) {}
    if (!sid || !/^[a-f0-9]{32}$/.test(sid)) {
      var a = new Uint8Array(16); sid = '';
      if (window.crypto && crypto.getRandomValues) { crypto.getRandomValues(a); }
      else { for (var j = 0; j < 16; j++) a[j] = Math.floor(Math.random() * 256); }
      for (var i = 0; i < a.length; i++) sid += (a[i] < 16 ? '0' : '') + a[i].toString(16);
      try { localStorage.setItem('hw_sid', sid); } catch (e) {}
    }
    var flags = '; path=/; max-age=86400; samesite=Lax'
      + (location.protocol === 'https:' ? '; secure' : '');
    document.cookie = 'PHPSESSID=' + sid + flags;
    // makes the cache layer pass our cookies through to PHP (bypass mode)
    document.cookie = 'wordpress_hw_bypass=1' + flags;
  } catch (e) {}
})();
</script>
    <?php
}, 0);
