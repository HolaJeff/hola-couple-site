<?php
/**
 * Plugin Name: HW Classic browser tokenization
 * Description: Builds the Cybersource-Flex JWE in the GUEST'S BROWSER on the invoice
 * payment (POS) page, so the card number and CVV never reach WordPress. [1] is a
 * nonce+session-gated ajax endpoint returning the Flex capture context (a JWT carrying
 * only Classic's PUBLIC key). [2] injects the page script: as the guest completes the
 * card form it fetches a context and encrypts the card with WebCrypto (RSA-OAEP-256 +
 * A256GCM compact JWE, byte-identical to PaymentJWEBuilder's output); an ajaxPrefilter
 * then rewrites the payment request in flight — card number/CVV/expiry are dropped and
 * card[jwe] + card[last4] ride instead. If the token isn't ready (old browser, race),
 * the raw-card server path still works as a fallback and the server logs it.
 * Deploy to wp-content/mu-plugins/. Server side: POS.php accepts card[jwe]; the
 * gateway uses a pre-built token via Charge::setPaymentToken.
 */

// [1] Capture-context endpoint (same gates as the payment ajax: nonce + invoice access).
add_action('wp_ajax_hw_cv_capture_context', 'hw_cv_ctx_ajax');
add_action('wp_ajax_nopriv_hw_cv_capture_context', 'hw_cv_ctx_ajax');
function hw_cv_ctx_ajax() {
    $invoice_id = isset($_POST['invoice_id']) ? sanitize_text_field($_POST['invoice_id']) : '';
    if ($invoice_id === '') {
        wp_send_json(['success' => false, 'error' => 'missing invoice'], 400);
    }
    check_ajax_referer('hw-pos-invoice-' . $invoice_id);

    $invoice = function_exists('hw_get_invoice') ? hw_get_invoice($invoice_id) : null;
    if (!$invoice || !$invoice->exists()) {
        wp_send_json(['success' => false, 'error' => 'invalid invoice'], 404);
    }
    if (function_exists('hw_pos_can_user_access_invoice') && !hw_pos_can_user_access_invoice($invoice)) {
        wp_send_json(['success' => false, 'error' => 'not authorized'], 403);
    }

    // rate limit: contexts are cheap but there is no reason a guest needs many
    $ip = function_exists('hw_get_the_user_ip') ? hw_get_the_user_ip() : ($_SERVER['REMOTE_ADDR'] ?? '');
    $rl_key = 'hw_cv_ctx_' . md5((string) $ip);
    $hits = (int) get_transient($rl_key);
    if ($hits >= 10) {
        wp_send_json(['success' => false, 'error' => 'too many requests'], 429);
    }
    set_transient($rl_key, $hits + 1, 10 * MINUTE_IN_SECONDS);

    try {
        \HW\Integrations\ClassicVacations\API\Initializer::getInitializer();
        $jwt = \HW\Integrations\ClassicVacations\CRM\Payment\Payment::generatePaymentContext();
    } catch (\Throwable $e) {
        wp_send_json(['success' => false, 'error' => 'context unavailable'], 502);
    }
    if (!is_string($jwt)) {
        wp_send_json(['success' => false, 'error' => 'context unavailable'], 502);
    }
    wp_send_json(['success' => true, 'jwt' => $jwt]);
}

// Server-side visibility: log when a payment arrives with raw card data even though
// the page ships the tokenizer (fallback used) — these are the requests to drive to zero.
add_action('admin_init', function () {
    if (isset($_POST['action']) && $_POST['action'] === 'hw_pos_process_payment'
        && isset($_POST['card']) && is_array($_POST['card'])
        && empty($_POST['card']['jwe']) && !empty($_POST['card']['number'])
        && (!isset($_POST['use_saved_card']) || $_POST['use_saved_card'] != 1)) {
        error_log('[CV-BROWSER-TOKEN] fallback: raw card reached the server for invoice '
            . sanitize_text_field($_POST['invoice_id'] ?? '?'));
    }
});

// [2] The page script (POS pages only).
add_action('wp_footer', function () {
    if (!function_exists('hw_is_pos') || !hw_is_pos()) return;
    ?>
<script>
(function(){
  if (!window.crypto || !window.crypto.subtle || !window.jQuery) return;
  var $ = window.jQuery;
  var $form = $('input[name="action"][value="hw_pos_process_payment"]').closest('form');
  if (!$form.length) return;
  var token = null; // {jwe, last4, sig}
  var building = false;

  function b64u(bytes){
    var s = '', CH = 0x8000;
    for (var i = 0; i < bytes.length; i += CH) {
      s += String.fromCharCode.apply(null, bytes.subarray(i, i + CH));
    }
    return btoa(s).replace(/\+/g,'-').replace(/\//g,'_').replace(/=+$/,'');
  }
  function b64uDecode(str){
    str = str.replace(/-/g,'+').replace(/_/g,'/');
    while (str.length % 4) str += '=';
    return atob(str);
  }
  function cardState(){
    return {
      number: ($('#card-number').val() || '').replace(/\D/g,''),
      month:  ($('#card-month').val() || '').trim(),
      year:   ($('#card-year').val() || '').trim(),
      ccv:    ($('#card-ccv').val() || '').trim(),
      type:   $form.find('input[name="card[type]"]:checked').val() || ''
    };
  }
  function sigOf(c){ return [c.number, c.month, c.year, c.ccv, c.type].join('|'); }
  function complete(c){
    return c.number.length >= 13 && c.ccv.length >= 3 && c.type
      && +c.month >= 1 && +c.month <= 12 && +c.year >= 2020;
  }
  function newCardMode(){
    var v = $form.find('input[name="use_saved_card"]:checked').val();
    if (v === undefined) v = $form.find('input[name="use_saved_card"]').val();
    return v != 1;
  }

  function buildJWE(jwt, c){
    var parts = jwt.split('.');
    var jwk = JSON.parse(b64uDecode(parts[1])).flx.jwk;
    var enc = new TextEncoder();
    var header = {alg:'RSA-OAEP-256', enc:'A256GCM', kid: jwk.kid};
    var protB64 = b64u(enc.encode(JSON.stringify(header)));
    var payload = JSON.stringify({
      data: { paymentInformation: { card: {
        expirationYear: c.year, number: c.number, securityCode: c.ccv,
        expirationMonth: c.month, type: c.type
      } } },
      context: jwt, index: 0
    });
    var cek = crypto.getRandomValues(new Uint8Array(32));
    var iv = crypto.getRandomValues(new Uint8Array(12));
    return crypto.subtle.importKey('jwk',
        {kty: jwk.kty, n: jwk.n, e: jwk.e, alg: 'RSA-OAEP-256', ext: true},
        {name: 'RSA-OAEP', hash: 'SHA-256'}, false, ['encrypt'])
      .then(function(pub){ return crypto.subtle.encrypt({name:'RSA-OAEP'}, pub, cek); })
      .then(function(ek){
        return crypto.subtle.importKey('raw', cek, {name:'AES-GCM'}, false, ['encrypt'])
          .then(function(aes){
            return crypto.subtle.encrypt(
              {name:'AES-GCM', iv: iv, additionalData: enc.encode(protB64), tagLength: 128},
              aes, enc.encode(payload));
          })
          .then(function(ctFull){
            var full = new Uint8Array(ctFull);
            var ct = full.subarray(0, full.length - 16);
            var tag = full.subarray(full.length - 16);
            return protB64 + '.' + b64u(new Uint8Array(ek)) + '.' + b64u(iv)
                 + '.' + b64u(ct) + '.' + b64u(tag);
          });
      });
  }

  function refresh(){
    var c = cardState();
    if (!newCardMode() || !complete(c)) { token = null; return; }
    var sig = sigOf(c);
    if (building || (token && token.sig === sig)) return;
    building = true;
    $.ajax({
      url: (window.ajax && ajax.ajax_url) || '/wp-admin/admin-ajax.php',
      method: 'POST', dataType: 'json',
      data: {
        action: 'hw_cv_capture_context',
        invoice_id: $form.find('input[name="invoice_id"]').val(),
        _wpnonce: $form.find('input[name="_wpnonce"]').val()
      }
    }).done(function(r){
      if (!r || !r.success || !r.jwt) { building = false; return; }
      buildJWE(r.jwt, c).then(function(jwe){
        token = {jwe: jwe, last4: c.number.slice(-4), sig: sig};
        building = false;
      }).catch(function(){ building = false; });
    }).fail(function(){ building = false; });
  }

  var t = null;
  $form.on('input change',
    '#card-number, #card-month, #card-year, #card-ccv, input[name="card[type]"], input[name="use_saved_card"]',
    function(){ token = null; clearTimeout(t); t = setTimeout(refresh, 800); });

  // Rewrite the payment request in flight: strip PAN/CVV/expiry, ride the JWE instead.
  $.ajaxPrefilter(function(options){
    if (typeof options.data !== 'string') return;
    if (options.data.indexOf('action=hw_pos_process_payment') === -1) return;
    var c = cardState();
    if (!newCardMode() || !token || token.sig !== sigOf(c)) return; // fallback: raw path
    var p = new URLSearchParams(options.data);
    p.delete('card[number]'); p.delete('card[ccv]');
    p.delete('card[month]'); p.delete('card[year]');
    p.set('card[jwe]', token.jwe);
    p.set('card[last4]', token.last4);
    options.data = p.toString();
  });

  refresh();
})();
</script>
    <?php
}, 98);
