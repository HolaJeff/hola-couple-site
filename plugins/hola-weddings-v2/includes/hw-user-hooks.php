<?php

/**
 * Claim the guest quotes that belong to a user's verified email address.
 *
 * Bookings can be made without an account, in which case the quote is stored
 * with no owner. Once someone registers and *proves* that address is theirs,
 * the quotes made with it are handed over so they show up under My Reservations.
 *
 * This deliberately runs on verification rather than on registration. Matching
 * on the email alone is only safe once the address has been confirmed —
 * otherwise anyone could register with a stranger's address and inherit their
 * bookings.
 *
 * Only ownerless quotes are ever touched — a quote already assigned to someone
 * is never reassigned.
 *
 * @param int $user_id
 * @return int Number of quotes claimed.
 */
function hw_claim_guest_quotes( $user_id ){

    $user = get_userdata( $user_id );

    if ( ! $user || empty( $user->user_email ) ) {
        return 0;
    }

    /**
     * Guest quotes are matched on the contact email, which is why this only
     * fires once that address has been verified. Return false to disable the
     * hand-over, or narrow it per site.
     *
     * @param bool     $enabled
     * @param \WP_User $user
     */
    if ( ! apply_filters( 'hw_claim_guest_quotes_enabled', true, $user ) ) {
        return 0;
    }

    global $wpdb;

    /*
     * Deliberately raw SQL rather than a meta_query.
     *
     * "Ownerless" has four distinct storage shapes — no meta row at all, SQL
     * NULL, an empty string, and '0' — and a meta_query cannot express the NULL
     * one: `NOT EXISTS` misses it because the row is present, and
     * `IN ('','0')` misses it because any comparison with NULL is never true.
     * A guest booking stored that way used to fall through both branches and
     * stayed unclaimable forever, silently. A LEFT JOIN covers all four.
     */
    $quotes = $wpdb->get_col( $wpdb->prepare(
        "SELECT p.ID
           FROM {$wpdb->posts} p
     INNER JOIN {$wpdb->postmeta} em
             ON em.post_id = p.ID AND em.meta_key = 'hw_email' AND em.meta_value = %s
      LEFT JOIN {$wpdb->postmeta} om
             ON om.post_id = p.ID AND om.meta_key = 'hw_owner_user_id'
          WHERE p.post_type = 'quote'
            AND p.post_status = 'publish'
            AND ( om.meta_id IS NULL
               OR om.meta_value IS NULL
               OR om.meta_value = ''
               OR om.meta_value = '0' )",
        $user->user_email
    ) );

    $quotes = array_map( 'intval', (array) $quotes );

    foreach ( $quotes as $quote_id ) {

        /*
         * Ownership only. post_author is deliberately left alone.
         *
         * The two answer different questions: post_author is who created the
         * booking, hw_owner_user_id is whose reservation it is. An agent taking a
         * booking over the phone is the author of that quote and stays the
         * author — handing the reservation to the customer afterwards does not
         * rewrite who took it.
         *
         * Nothing reads the author for a quote: getAuthorID() has no callers, no
         * query filters by it, and hw_get_user_quotes() matches on this meta.
         * Overwriting it also risked quietly moving a quote out of reach of staff
         * whose role grants edit_published_posts but not edit_others_posts.
         */
        update_post_meta( $quote_id, 'hw_owner_user_id', $user_id );

        do_action( 'hw_guest_quote_claimed', $quote_id, $user_id );
    }

    return count( $quotes );
}

/**
 * Hand guest bookings over once the account has proven it owns that email.
 *
 * Fired by EmailVerificationService::markVerified(), which covers both the
 * emailed verification link and a Google sign-in carrying a verified claim.
 */
add_action( 'hw_email_verified', 'hw_claim_guest_quotes' );

/**
 * Hide the WordPress admin bar for subscribers.
 *
 * Customers end up as subscribers when they book, and the bar is nothing but
 * noise on top of the site for them. Every other role keeps it.
 *
 * @param boolean $show
 * @return void
 */
function hw_hide_admin_bar ( $show ){

    if ( ! is_user_logged_in() ) {
        return $show;
    }

    return hw_user_has_role( 'subscriber' ) ? false : $show;
}
add_filter( 'show_admin_bar', 'hw_hide_admin_bar' );

/**
 * Make the new session token readable in the request that issued it.
 *
 * wp_set_auth_cookie() sends the cookie header but leaves $_COOKIE alone, so
 * wp_get_session_token() still answers '' for the rest of that request. Any
 * nonce created after signing someone in would therefore be bound to an empty
 * token and fail verification on the very next request.
 *
 * That matters because the sign-in and sign-up responses hand the client a
 * resend nonce for the account they just authenticated.
 *
 * @param string $logged_in_cookie
 * @return void
 */
function hw_capture_logged_in_cookie( $logged_in_cookie ){

    $_COOKIE[ LOGGED_IN_COOKIE ] = $logged_in_cookie;
}
add_action( 'set_logged_in_cookie', 'hw_capture_logged_in_cookie' );
