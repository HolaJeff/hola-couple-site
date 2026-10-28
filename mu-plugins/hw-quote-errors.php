<?php
/**
 * Plugin Name: HW booking errors made visible (session-loss workaround)
 * Description: Native PHP sessions do not persist on this host (a fresh session id on
 * every request), so the booking flow's validation errors and the guest's typed data —
 * stored in the session right before the bounce redirect — were silently lost. Every
 * failed submission looked like "nothing happens". This carries errors + refill data
 * through the redirect in a short-lived tokened transient (object-cache backed, no
 * cookie dependency) and hands them back to the normal render path, plus scrolls the
 * guest to the error box. Card data is NEVER round-tripped — the guest re-enters it.
 * Deploy to wp-content/mu-plugins/.
 */

// [1] On the bounce redirect: stash errors + refill data under a URL token.
add_filter('wp_redirect', function ($location) {
    if (!did_action('hw_before_cart_redirect') || !function_exists('hw_get_session')) {
        return $location;
    }
    $errs = hw_get_session()->get('quote_errors', []);
    if (empty($errs)) {
        return $location;
    }
    $data = hw_get_session()->get('quote_data', []);
    if (is_array($data) && (isset($data['payment']) || isset($data['signature']))) {
        unset($data['payment'], $data['signature']);   // never persist card data here
        $errs['hw_reenter_card'] = __('For your security, please re-enter your card details and signature.', 'hola-weddings');
    }
    $tok = strtolower(wp_generate_password(16, false, false));
    set_transient('hw_qerr_' . $tok, ['errors' => $errs, 'data' => $data], 15 * MINUTE_IN_SECONDS);
    return add_query_arg('hw_err', $tok, $location);
}, 10, 1);

// [2] On the re-render: feed them back into the request's session object so the
// form's EXISTING error box + refill logic just work.
add_action('wp', function () {
    if (is_admin() || empty($_GET['hw_err']) || !function_exists('hw_get_session')) {
        return;
    }
    $tok = preg_replace('/[^a-z0-9]/', '', (string) $_GET['hw_err']);
    $payload = $tok ? get_transient('hw_qerr_' . $tok) : false;
    if (!is_array($payload)) {
        return;
    }
    hw_get_session()->set('quote_errors', $payload['errors'] ?? []);
    if (!empty($payload['data'])) {
        hw_get_session()->set('quote_data', $payload['data']);
    }
});

// [3] Scroll the guest to the error box — a bounce must never look like nothing.
add_action('wp_footer', function () {
    if (empty($_GET['hw_err'])) {
        return;
    }
    echo '<script>document.addEventListener("DOMContentLoaded",function(){'
       . 'var e=document.querySelector("#booking-form .alert-danger");'
       . 'if(e){e.scrollIntoView({behavior:"smooth",block:"center"});}});</script>';
});
