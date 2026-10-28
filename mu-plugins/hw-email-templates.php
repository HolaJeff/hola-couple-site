<?php
/**
 * Plugin Name: HW tenant email templates
 * Description: Makes the couple-site guest emails (payment receipt, payment
 * pending/canceled, on-request received/confirmed) tenant-customizable from the
 * platform. The platform pushes per-agency templates + branding here
 * (POST /v1/zoho-plugin/email-templates, App-Password + manage_network — the
 * card-purge pattern); they are stored as NETWORK options (network == tenant)
 * with a per-site option as an escape hatch, and swapped into outgoing mail via
 * one wp_mail filter (senders tag themselves with $GLOBALS['hw_email_role'] +
 * ['hw_email_ctx']) plus the hw_mailer_email_args filter for the EmailSender
 * classes. No pushed template -> the hardcoded copy sends unchanged.
 * Deploy to wp-content/mu-plugins/.
 */

// Roles this network accepts from the platform (platform TEMPLATE_ROLES subset).
function hw_email_tpl_roles() {
    return ['payment-received', 'wp-payment-pending', 'wp-payment-canceled',
            'wp-booking-received', 'wp-booking-confirmed'];
}

// ── [1] receive + store ──────────────────────────────────────────────────────
add_action('rest_api_init', function () {
    register_rest_route('v1/zoho-plugin', '/email-templates', [
        'methods'             => 'POST',
        'callback'            => 'hw_email_tpl_receive',
        'permission_callback' => function () {
            return is_user_logged_in() && current_user_can('manage_network');
        },
    ]);
});

function hw_email_tpl_receive(\WP_REST_Request $request) {
    $stored = 0;
    $templates = $request->get_param('templates');
    if (is_array($templates)) {
        foreach ($templates as $role => $tpl) {
            if (!in_array($role, hw_email_tpl_roles(), true) || !is_array($tpl)) continue;
            $subject = trim((string) ($tpl['subject'] ?? ''));
            $body = (string) ($tpl['body'] ?? '');
            if ($body === '') continue;
            update_network_option(null, 'hw_email_tpl_' . $role,
                                  ['subject' => $subject, 'body' => $body]);
            $stored++;
        }
    }
    $branding = $request->get_param('branding');
    if (is_array($branding)) {
        update_network_option(null, 'hw_email_branding', [
            'from_name'   => sanitize_text_field($branding['from_name'] ?? ''),
            'from_email'  => sanitize_email($branding['from_email'] ?? ''),
            'ops_email'   => sanitize_email($branding['ops_email'] ?? ''),
            'logo_url'    => esc_url_raw($branding['logo_url'] ?? ''),
            'footer_html' => (string) ($branding['footer_html'] ?? ''),
            // guest-facing support details (account sidebar, help bubble, POS help box,
            // error / failed / canceled screens, email footer, theme footers)
            'support_email' => sanitize_email($branding['support_email'] ?? ''),
            'support_phone' => sanitize_text_field($branding['support_phone'] ?? ''),
            'website'       => esc_url_raw($branding['website'] ?? ''),
            'facebook'      => esc_url_raw($branding['facebook'] ?? ''),
            'instagram'     => esc_url_raw($branding['instagram'] ?? ''),
            'twitter'       => esc_url_raw($branding['twitter'] ?? ''),
            'pinterest'     => esc_url_raw($branding['pinterest'] ?? ''),
            // the platform's own address: the guest 'Manage your booking' button links there
            'platform_url'  => esc_url_raw($branding['platform_url'] ?? ''),
            // the Inbox: every guest email answers back into the agency's Desk mailbox;
            // the couple sites' contact form posts to the platform signed with this secret
            'reply_to'      => sanitize_email($branding['reply_to'] ?? ''),
            'platform_agency' => sanitize_key($branding['platform_agency'] ?? ''),
            'platform_inbound_secret' => sanitize_text_field($branding['platform_inbound_secret'] ?? ''),
        ]);
        // the themes' footer social icons read the network option hw_social_links (plugin
        // hw_get_network_social_links) — keep it in step with the tenant's branding so a
        // tenant network never shows the previous owner's handles. Empty = icon hidden.
        update_network_option(null, 'hw_social_links', [
            'facebook'  => esc_url_raw($branding['facebook'] ?? ''),
            'twitter'   => esc_url_raw($branding['twitter'] ?? ''),
            'instagram' => esc_url_raw($branding['instagram'] ?? ''),
            'pinterest' => esc_url_raw($branding['pinterest'] ?? ''),
        ]);
    }
    error_log("[HW-EMAIL-TPL] platform push: $stored template(s)"
        . (is_array($branding) ? ' + branding' : ''));
    return new \WP_REST_Response(['status' => 'SUCCESS', 'stored' => $stored,
                                  'branding' => is_array($branding)], 200);
}

// ── [2] read ladder + renderer ───────────────────────────────────────────────
function hw_email_tpl($role) {
    $t = get_option('hw_email_tpl_' . $role);              // per-site escape hatch
    if (!is_array($t) || empty($t['body'])) {
        $t = get_network_option(null, 'hw_email_tpl_' . $role);
    }
    return (is_array($t) && !empty($t['body'])) ? $t : null;
}

function hw_email_branding() {
    $b = get_network_option(null, 'hw_email_branding');
    return is_array($b) ? $b : [];
}
/**
 * The TENANT's operations address — where advisor notifications (new quote, on-request,
 * payment-link requests, money-path alerts) go. Ladder: network option hw_agency_ops_email
 * → branding ops_email → branding from_email → the network admin email. Hola's legacy
 * hola@ address is NOT a fallback any more: an unconfigured tenant mails its own admin.
 */
function hw_agency_ops_email() {
    $o = sanitize_email((string) get_network_option(null, 'hw_agency_ops_email', ''));
    if ($o) return $o;
    $b = hw_email_branding();
    foreach (['ops_email', 'from_email'] as $k) {
        $v = sanitize_email((string) ($b[$k] ?? ''));
        if ($v) return $v;
    }
    return sanitize_email((string) (get_network_option(null, 'admin_email') ?: get_option('admin_email')));
}
/**
 * What GUESTS see as "Need assistance?": the tenant's support email/phone/website/socials
 * from the platform's branding push. Email falls back to the ops address; everything else
 * is blank when unset and the templates hide it — no Hola details leak onto a tenant site.
 */
function hw_tenant_support() {
    $b = hw_email_branding();
    $email = sanitize_email((string) ($b['support_email'] ?? '')) ?: hw_agency_ops_email();
    $site = (string) ($b['website'] ?? '');
    return [
        'name'      => sanitize_text_field((string) ($b['from_name'] ?? '')) ?: wp_specialchars_decode(get_network_option(null, 'site_name') ?: get_bloginfo('name')),
        'email'     => $email,
        'phone'     => sanitize_text_field((string) ($b['support_phone'] ?? '')),
        'website'   => $site,
        'website_label' => preg_replace('#^https?://(www\.)?#', '', rtrim($site, '/')),
        'facebook'  => (string) ($b['facebook'] ?? ''),
        'instagram' => (string) ($b['instagram'] ?? ''),
        'twitter'   => (string) ($b['twitter'] ?? ''),
        'pinterest' => (string) ($b['pinterest'] ?? ''),
        'logo_url'  => (string) ($b['logo_url'] ?? ''),
        'footer_html' => (string) ($b['footer_html'] ?? ''),
    ];
}
/** mailto anchor for inline sentences ("contact our support team at …"). */
function hw_tenant_support_link() {
    $s = hw_tenant_support();
    return '<a href="mailto:' . esc_attr($s['email']) . '">' . esc_html($s['email']) . '</a>';
}
/** email + phone lines for the help boxes; phone omitted when unset. */
function hw_tenant_support_lines() {
    $s = hw_tenant_support();
    $h = '<div><a href="mailto:' . esc_attr($s['email']) . '">' . esc_html($s['email']) . '</a></div>';
    if ($s['phone'] !== '') $h .= "\n" . '<div><a href="tel:' . esc_attr(preg_replace('/[^0-9+]/', '', $s['phone'])) . '">' . esc_html($s['phone']) . '</a></div>';
    return $h;
}
/** the plugin email layout's footer cell: name, footer text, phone, email, website. */
function hw_tenant_email_footer() {
    $s = hw_tenant_support();
    $h = esc_html($s['name']) . '<br>';
    if ($s['footer_html'] !== '') $h .= wp_kses_post($s['footer_html']) . '<br><br>';
    if ($s['phone'] !== '') $h .= '<a href="tel:' . esc_attr(preg_replace('/[^0-9+]/', '', $s['phone'])) . '">' . esc_html($s['phone']) . '</a><br><br>';
    $h .= '<a href="mailto:' . esc_attr($s['email']) . '">' . esc_html($s['email']) . '</a>';
    if ($s['website'] !== '') $h .= '<br><br><a href="' . esc_url($s['website']) . '">' . esc_html($s['website_label']) . '</a>';
    return $h;
}
/**
 * Content guard: couple sites are cloned from a template blog whose Elementor copy (T&C,
 * cancellation text, help blocks) still says hola@holaweddings.com / +1 888 472 7077. On a
 * tenant network whose support email is not Hola's, rewrite those literals in rendered content
 * to the tenant's details. No-op on Hola's own network.
 */
function hw_tenant_content_guard($html) {
    if (!is_string($html) || $html === '' || strpos($html, 'holaweddings.com') === false && strpos($html, '888 472 7077') === false && strpos($html, '888-472-7077') === false) return $html;
    $s = hw_tenant_support();
    if (!$s['email'] || strtolower($s['email']) === 'hola@holaweddings.com') return $html;
    $phone = $s['phone'] !== '' ? $s['phone'] : $s['email'];
    return str_replace(
        ['hola@holaweddings.com', '+1 888 472 7077', '1-888-472-7077', '888 472 7077'],
        [$s['email'], $phone, $phone, $phone],
        $html);
}
add_filter('the_content', 'hw_tenant_content_guard', 99);
add_filter('elementor/frontend/the_content', 'hw_tenant_content_guard', 99);
add_filter('widget_text_content', 'hw_tenant_content_guard', 99);
/** "From: Name <ops>" header for advisor-facing mail, tenant-branded. */
function hw_agency_ops_from() {
    $b = hw_email_branding();
    $name = sanitize_text_field((string) ($b['from_name'] ?? '')) ?: wp_specialchars_decode(get_network_option(null, 'site_name') ?: get_bloginfo('name'));
    return 'From: ' . $name . ' <' . hw_agency_ops_email() . '>';
}

/**
 * Fill {{TOKENS}} — same grammar as the platform's policies.render_email:
 * scalars are HTML-escaped; keys listed in $raw pass through as markup.
 * Unknown tokens stay visible (typos surface instead of vanishing).
 */
function hw_email_fill($template, array $ctx, array $raw = []) {
    return preg_replace_callback('/\{\{\s*([A-Z_]+)\s*\}\}/', function ($m) use ($ctx, $raw) {
        $token = $m[1];
        if (!array_key_exists($token, $ctx)) return $m[0];
        $v = (string) ($ctx[$token] ?? '');
        return in_array($token, $raw, true) ? $v : esc_html($v);
    }, (string) $template);
}

/**
 * Render one role for a context: [subject, html] with the tenant's branded
 * wrapper, or null when no template was pushed (caller keeps hardcoded copy).
 */
function hw_email_render($role, array $ctx, array $raw = []) {
    $tpl = hw_email_tpl($role);
    if (!$tpl) return null;
    $subject = hw_email_fill($tpl['subject'], $ctx, $raw);
    $body = hw_email_fill($tpl['body'], $ctx, $raw);
    $b = hw_email_branding();
    $logo = !empty($b['logo_url'])
        ? '<img src="' . esc_url($b['logo_url']) . '" alt="" style="max-height:60px;max-width:220px;">'
        : '<b style="font-size:18px;">' . esc_html($b['from_name'] ?? get_bloginfo('name')) . '</b>';
    $footer = !empty($b['footer_html']) ? $b['footer_html'] : '';
    $html = '<div style="background:#f6f4f0;padding:24px 0;">'
        . '<div style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:10px;'
        . 'font-family:Georgia,serif;color:#1b1b1b;font-size:15px;line-height:1.55;">'
        . '<div style="padding:22px 28px;border-bottom:1px solid #eee7dc;">' . $logo . '</div>'
        . '<div style="padding:24px 28px;">' . $body . '</div>'
        . ($footer ? '<div style="padding:16px 28px;border-top:1px solid #eee7dc;'
            . 'font-size:12px;color:#6b6b6b;">' . $footer . '</div>' : '')
        . '</div></div>';
    return [$subject, $html];
}

function hw_email_from_header() {
    $b = hw_email_branding();
    if (!empty($b['from_email'])) {
        $name = $b['from_name'] ?: get_bloginfo('name');
        return 'From: ' . $name . ' <' . $b['from_email'] . '>';
    }
    return '';
}

// ── [3] interception ─────────────────────────────────────────────────────────
// Raw wp_mail senders set $GLOBALS['hw_email_role'] + ['hw_email_ctx'] (and
// optionally ['hw_email_raw'] token names) right before calling wp_mail.
add_filter('wp_mail', function ($args) {
    $role = $GLOBALS['hw_email_role'] ?? '';
    if (!$role) return $args;
    $ctx = $GLOBALS['hw_email_ctx'] ?? [];
    $raw = $GLOBALS['hw_email_raw'] ?? [];
    unset($GLOBALS['hw_email_role'], $GLOBALS['hw_email_ctx'], $GLOBALS['hw_email_raw']);
    try {
        $rendered = hw_email_render($role, (array) $ctx, (array) $raw);
        if ($rendered) {
            [$args['subject'], $args['message']] = $rendered;
        }
        // branded From for tagged guest mail lacking one (pushed or not)
        $headers = $args['headers'] ?? [];
        if (is_string($headers)) $headers = $headers === '' ? [] : [$headers];
        $has_from = false;
        foreach ($headers as $h) { if (stripos((string) $h, 'from:') === 0) { $has_from = true; break; } }
        $from = hw_email_from_header();
        if (!$has_from && $from) { $headers[] = $from; }
        // Reply-To = the agency's Desk support address, so a guest's answer lands in the Inbox
        $b = hw_email_branding();
        $reply_to = sanitize_email((string) ($b['reply_to'] ?? ''));
        if ($reply_to) {
            $has_rt = false;
            foreach ($headers as $h) { if (stripos((string) $h, 'reply-to:') === 0) { $has_rt = true; break; } }
            if (!$has_rt) { $headers[] = 'Reply-To: ' . $reply_to; }
        }
        if ($rendered) {
            $has_ct = false;
            foreach ($headers as $h) { if (stripos((string) $h, 'content-type:') === 0) { $has_ct = true; break; } }
            if (!$has_ct) { $headers[] = 'Content-Type: text/html; charset=UTF-8'; }
        }
        $args['headers'] = $headers;
    } catch (\Throwable $e) {
        error_log('[HW-EMAIL-TPL] render failed for ' . $role . ': ' . $e->getMessage());
    }
    return $args;
}, 5);

// EmailSender path (pending / canceled transaction emails): map class -> role.
add_filter('hw_mailer_email_args', function ($args, $email) {
    static $map = null;
    if ($map === null) {
        $map = ['HW\Email\Templates\PendingTransaction'  => 'wp-payment-pending',
                'HW\Email\Templates\CanceledTransaction' => 'wp-payment-canceled'];
    }
    $tpl_obj = $GLOBALS['hw_email_template_obj'] ?? null;
    $cls = is_object($tpl_obj) ? get_class($tpl_obj) : '';
    $role = $map[$cls] ?? '';
    if (!$role) return $args;
    unset($GLOBALS['hw_email_template_obj']);
    try {
        $ctx = $GLOBALS['hw_email_ctx'] ?? [];
        unset($GLOBALS['hw_email_ctx']);
        $rendered = hw_email_render($role, (array) $ctx);
        if ($rendered) {
            [$args['subject'], $args['body']] = $rendered;
        }
        $from = hw_email_from_header();
        if ($from) {
            $headers = is_array($args['headers'] ?? null) ? $args['headers']
                : (empty($args['headers']) ? [] : [$args['headers']]);
            $has_from = false;
            foreach ($headers as $h) { if (stripos((string) $h, 'from:') === 0) { $has_from = true; break; } }
            if (!$has_from) { $headers[] = $from; }
            $args['headers'] = $headers;
        }
    } catch (\Throwable $e) {
        error_log('[HW-EMAIL-TPL] mailer render failed for ' . $role . ': ' . $e->getMessage());
    }
    return $args;
}, 10, 2);

// The EmailSender doesn't tell the filter which template class is sending, so
// observe it: wrap send() timing via the hw_emailing hooks isn't available —
// instead the two hook sites store the object themselves. hw-email-hooks.php
// (pending) and Models/Transaction.php (canceled) both construct the template
// immediately before EmailSender->send(); the small shims below capture ctx for
// them without editing the theme: recompute from the transaction on the same
// hooks, one priority earlier.
add_action('hw_pos_payment_processed', function ($transaction) {
    if (!is_object($transaction) || !method_exists($transaction, 'getStatus')) return;
    if ($transaction->getStatus() !== \HW\Enums\TransactionStatus::PENDING->value) return;
    $GLOBALS['hw_email_template_obj'] = class_exists('\HW\Email\Templates\PendingTransaction')
        ? new \HW\Email\Templates\PendingTransaction($transaction) : null;
    $GLOBALS['hw_email_ctx'] = hw_email_tx_ctx($transaction);
}, 9);

add_action('hw_model_transactions_metadata_updated', function ($name, $value, $old, $model) {
    if ($name !== 'hw_status' || $value !== \HW\Enums\TransactionStatus::CANCELED->value) return;
    // the theme only emails on the PENDING -> CANCELED transition — mirror it,
    // so the globals never sit stale for an email that won't send
    if ($old !== \HW\Enums\TransactionStatus::PENDING->value) return;
    if (!is_object($model)) return;
    $GLOBALS['hw_email_template_obj'] = class_exists('\HW\Email\Templates\CanceledTransaction')
        ? new \HW\Email\Templates\CanceledTransaction($model) : null;
    $GLOBALS['hw_email_ctx'] = hw_email_tx_ctx($model);
}, 9, 4);

/** Shared token context off a transaction (guest name, installment, amount…). */
function hw_email_tx_ctx($tx) {
    $ctx = [];
    try {
        $invoice = method_exists($tx, 'getInvoice') ? $tx->getInvoice() : null;
        $quote = $invoice && method_exists($invoice, 'getQuote') ? $invoice->getQuote() : null;
        $qid = $quote ? $quote->getID() : 0;
        $type = $invoice ? get_post_meta($invoice->getID(), 'hw_type', true) : '';
        if (is_array($type)) { $type = $type['value'] ?? ($type['label'] ?? reset($type)); }
        $label = function_exists('hw_get_invoice_type_label')
            ? (hw_get_invoice_type_label(strtolower((string) $type)) ?: ucfirst((string) $type))
            : ucfirst((string) $type);
        $ctx = [
            'GUEST_NAME'      => trim((string) get_post_meta($qid, 'hw_first_name', true)),
            'INSTALLMENT'     => $label,
            'AMOUNT'          => '$' . number_format((float) $tx->getAmount(), 2),
            'TRANSACTION_REF' => method_exists($tx, 'getTransactionID') ? (string) $tx->getTransactionID() : '',
            'CARD_LAST4'      => '',
        ];
        $card = json_decode((string) get_post_meta($tx->getID(), 'hw_card', true), true);
        if (is_array($card) && !empty($card['number'])) {
            $ctx['CARD_LAST4'] = substr(preg_replace('/\D/', '', (string) $card['number']), -4);
        }
    } catch (\Throwable $e) { /* tokens are a nicety */ }
    return $ctx;
}
