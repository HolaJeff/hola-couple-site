<?php

namespace HW\Email\Templates;

use HW\Email\AbstractTemplate;

/**
 * Asks a newly registered user to prove the email address on their account.
 *
 * Carries the raw token in the link and nothing else — no hash, no password, no
 * reservation detail.
 */
class VerifyEmail extends AbstractTemplate {

    protected \WP_User $user;

    protected string $verification_url;

    public function __construct( \WP_User $user, string $verification_url ) {

        $this->user             = $user;
        $this->verification_url = $verification_url;
    }

    public function getSubject(): string {

        return sprintf( __( 'Confirm your email address for %s', 'hola-weddings' ), get_bloginfo( 'name' ) );
    }

    public function getTemplate(): string {

        return 'templates/emails/account/verify-email.php';
    }

    public function getData(): array {

        $name = $this->user->first_name ?: $this->user->display_name;

        return [
            'name'             => $name,
            'verification_url' => $this->verification_url,
            'site_name'        => get_bloginfo( 'name' ),
            'expires_in'       => human_time_diff( time(), time() + \HW\Auth\EmailVerificationService::TOKEN_TTL ),
        ];
    }

    public function getRecipients(): array {

        return [ $this->user->user_email ];
    }
}
