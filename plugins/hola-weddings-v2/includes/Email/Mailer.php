<?php

namespace HW\Email;

use HW\Traits\Singleton;
use WP_Error;

/**
 * Mailer class wrapping wp_mail and applying filters.
 */
class Mailer {

    use Singleton;

    public function send(EmailInterface $email): bool|WP_Error {

        $args = [
            'to'      => $email->getRecipients(),
            'subject' => $email->getSubject(),
            'body'    => $email->getBody(),
            'headers' => $email->getHeaders(),
        ];

        /**
         * Allow modification of all email args.
         * @param array $args ['to','subject','body','headers']
         * @param EmailInterface $email
         */
        $args = apply_filters('hw_mailer_email_args', $args, $email);

        if (is_wp_error($args)) {

            return $args;
        }

        return wp_mail(
            $args['to'],
            $args['subject'],
            $args['body'],
            $args['headers']
        );
    }
}