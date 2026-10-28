<?php
/**
 * Plugin Name: HW contact your advisor
 * Description: A "Contact your advisor" form on the couple sites that files a ticket in the
 * agency's Inbox (platform → Zoho Desk) instead of emailing an advisor's personal mailbox.
 * Shortcode [hw_contact_advisor]; auto-injected under the guest's reservation page. The post
 * goes to {platform_url}/site/{agency}/contact, JSON-signed with HMAC-SHA256 over the raw
 * body using the secret the platform pushes with the branding (hw_email_branding
 * platform_inbound_secret). Honeypot + per-IP rate limit + nonce. Deploy to
 * wp-content/mu-plugins/ (loads after hw-email-templates.php: c < e).
 */
if (!defined('ABSPATH')) exit;

function hw_contact_cfg() {
    $b = function_exists('hw_email_branding') ? hw_email_branding() : (get_network_option(null, 'hw_email_branding') ?: []);
    $url = rtrim((string) ($b['platform_url'] ?? get_network_option(null, 'hw_platform_url', '')), '/');
    return ['url' => $url, 'agency' => (string) ($b['platform_agency'] ?? ''),
            'secret' => (string) ($b['platform_inbound_secret'] ?? '')];
}

function hw_contact_form_html($prefill = []) {
    $cfg = hw_contact_cfg();
    if (!$cfg['url'] || !$cfg['agency'] || !$cfg['secret']) return '';
    $u = wp_get_current_user();
    $name = esc_attr($prefill['name'] ?? ($u && $u->ID ? trim($u->first_name . ' ' . $u->last_name) : ''));
    $email = esc_attr($prefill['email'] ?? ($u && $u->ID ? $u->user_email : ''));
    $nonce = wp_create_nonce('hw_contact');
    ob_start(); ?>
<div class="hw-contact card p-3 p-md-4 mb-4" style="background:#f7f9fb;border:1px solid #e6ebf0;border-radius:12px;">
  <h4 class="mb-1">Contact your travel advisor</h4>
  <p class="text-muted" style="font-size:14px;">Questions about your room, payments, flights or the wedding week? Write to us here — it reaches your advisor directly and you get the answer by email.</p>
  <form class="hw-contact-form" data-nonce="<?php echo esc_attr($nonce); ?>" data-ajax="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
    <div class="row" style="gap:8px 0;">
      <div class="col-md-6"><input class="form-control" name="name" placeholder="Your name" value="<?php echo $name; ?>" maxlength="80" required></div>
      <div class="col-md-6"><input class="form-control" type="email" name="email" placeholder="Your email" value="<?php echo $email; ?>" maxlength="120" required></div>
      <div class="col-md-6"><input class="form-control" name="phone" placeholder="Phone (optional)" maxlength="40"></div>
      <div class="col-12"><textarea class="form-control" name="message" rows="4" placeholder="How can we help?" maxlength="3000" required></textarea></div>
      <div style="position:absolute;left:-9999px;" aria-hidden="true"><input name="website" tabindex="-1" autocomplete="off"></div>
    </div>
    <div class="d-flex align-items-center mt-2" style="gap:12px;">
      <button class="btn btn-primary" type="submit">Send</button>
      <span class="hw-contact-msg text-muted" style="font-size:13px;"></span>
    </div>
  </form>
</div>
<script>
(function(){
  document.querySelectorAll('.hw-contact-form').forEach(function(f){
    if (f.dataset.wired) return; f.dataset.wired = '1';
    f.addEventListener('submit', function(e){
      e.preventDefault();
      var msg = f.querySelector('.hw-contact-msg'), btn = f.querySelector('button[type=submit]');
      btn.disabled = true; msg.textContent = 'Sending…';
      var fd = new FormData(f); fd.append('action', 'hw_contact_advisor'); fd.append('_nonce', f.dataset.nonce);
      fetch(f.dataset.ajax, {method: 'POST', credentials: 'same-origin', body: fd}).then(function(r){ return r.json(); }).then(function(j){
        if (j && j.success) { msg.textContent = 'Sent — your advisor will answer by email.'; f.reset(); }
        else { msg.textContent = (j && j.data && j.data.message) || 'Could not send — please email us instead.'; btn.disabled = false; }
      }).catch(function(){ msg.textContent = 'Could not send — please email us instead.'; btn.disabled = false; });
    });
  });
})();
</script>
    <?php return ob_get_clean();
}
add_shortcode('hw_contact_advisor', function () { return hw_contact_form_html(); });

// under the guest's reservation page (after the Manage-your-booking card, priority 20)
add_action('hw_account_reservation_after_title', function () {
    global $post;
    if (!$post || $post->post_type !== 'quote') return;
    $qid = (int) $post->ID;
    $token_ok = (($GLOBALS['hw_guest_token_ok'] ?? 0) === $qid);
    $owner_ok = is_user_logged_in() && (int) $post->post_author === get_current_user_id();
    if (!$token_ok && !$owner_ok) return;
    echo hw_contact_form_html([
        'name'  => trim((string) get_post_meta($qid, 'hw_guest_first_name', true) . ' ' . (string) get_post_meta($qid, 'hw_guest_last_name', true)),
        'email' => (string) get_post_meta($qid, 'hw_guest_email', true),
    ]);
}, 20);

function hw_contact_rate_ok() {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? '?');
    $ip = trim(explode(',', (string) $ip)[0]);
    $k = 'hw_contact_' . md5($ip);
    $n = (int) get_transient($k);
    if ($n >= 8) return false;
    set_transient($k, $n + 1, 10 * MINUTE_IN_SECONDS);
    return true;
}

function hw_contact_advisor_ajax() {
    if (!wp_verify_nonce((string) ($_POST['_nonce'] ?? ''), 'hw_contact')) wp_send_json_error(['message' => 'Please reload the page and try again.'], 403);
    if (!empty($_POST['website'])) wp_send_json_success();                       // honeypot
    if (!hw_contact_rate_ok()) wp_send_json_error(['message' => 'Too many messages — please try again in a few minutes.'], 429);
    $cfg = hw_contact_cfg();
    if (!$cfg['url'] || !$cfg['agency'] || !$cfg['secret']) wp_send_json_error(['message' => 'The contact form is not set up yet — please email us.'], 503);
    $message = trim(wp_strip_all_tags((string) ($_POST['message'] ?? '')));
    $email = sanitize_email((string) ($_POST['email'] ?? ''));
    $phone = sanitize_text_field((string) ($_POST['phone'] ?? ''));
    if (strlen($message) < 3 || (!$email && !$phone)) wp_send_json_error(['message' => 'Please add your email and a message.'], 400);
    $body = wp_json_encode([
        'name' => sanitize_text_field((string) ($_POST['name'] ?? '')),
        'email' => $email, 'phone' => $phone, 'message' => mb_substr($message, 0, 3000),
        'wedding_id' => (string) get_option('hwz_wedding_id', ''),
        'site' => parse_url(home_url('/'), PHP_URL_HOST),
        'ts' => time(),
    ]);
    $sig = hash_hmac('sha256', $body, $cfg['secret']);
    $r = wp_remote_post($cfg['url'] . '/site/' . rawurlencode($cfg['agency']) . '/contact', [
        'timeout' => 20, 'headers' => ['Content-Type' => 'application/json', 'X-HW-Signature' => $sig], 'body' => $body,
    ]);
    $code = is_wp_error($r) ? 0 : (int) wp_remote_retrieve_response_code($r);
    if ($code === 200) wp_send_json_success();
    error_log('[HW-CONTACT] platform answered ' . $code . ' ' . (is_wp_error($r) ? $r->get_error_message() : wp_remote_retrieve_body($r)));
    // never lose a message: fall back to the ops mailbox
    $to = function_exists('hw_agency_ops_email') ? hw_agency_ops_email() : get_option('admin_email');
    wp_mail($to, 'Contact form — ' . parse_url(home_url('/'), PHP_URL_HOST), $body, ['Reply-To: ' . $email]);
    wp_send_json_success();
}
add_action('wp_ajax_hw_contact_advisor', 'hw_contact_advisor_ajax');
add_action('wp_ajax_nopriv_hw_contact_advisor', 'hw_contact_advisor_ajax');
