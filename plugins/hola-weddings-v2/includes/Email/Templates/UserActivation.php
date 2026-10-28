<?php

namespace HW\Email\Templates;

use HW\Email\AbstractTemplate;

/**
 * Replaces the plain-text invitation WordPress sends when an administrator adds
 * a user to a site on the network.
 *
 * Core builds that message with a hardcoded `Content-Type: text/plain` header
 * and hands it straight to wp_mail(), so filtering the body alone would only
 * deliver raw HTML source. The whole notification is suppressed and re-sent
 * through the plugin's mailer instead, which wraps it in the standard layout.
 *
 * Carries the same information core's version did — site, role, activation link
 * and the fact the invitation expires — and nothing more.
 */
class UserActivation extends AbstractTemplate {

    protected string $user_login;

    protected string $user_email;

    protected string $activation_url;

    protected string $site_title;

    protected string $role;

    public function __construct(
        string $user_login,
        string $user_email,
        string $activation_url,
        string $site_title,
        string $role = ''
    ) {
        $this->user_login     = $user_login;
        $this->user_email     = $user_email;
        $this->activation_url = $activation_url;
        $this->site_title     = $site_title;
        $this->role           = $role;
    }

    public function getSubject(): string {

        return sprintf( __( 'Activate your account on %s', 'hola-weddings' ), $this->site_title );
    }

    public function getTemplate(): string {

        return 'templates/emails/account/user-activation.php';
    }

    public function getData(): array {

        return [
            'user_login'     => $this->user_login,
            'activation_url' => $this->activation_url,
            'site_title'     => $this->site_title,
            // Rendered as a sentence fragment, so an unknown role just drops out.
            'role_line'      => $this->role !== ''
                ? sprintf( __( 'You have been given the role of %s.', 'hola-weddings' ), $this->role )
                : '',
        ];
    }

    public function getRecipients(): array {

        return [ $this->user_email ];
    }
}
