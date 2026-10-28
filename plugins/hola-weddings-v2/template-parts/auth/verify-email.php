<?php
/**
 * Confirm your email address.
 *
 * Two audiences land here:
 *   - someone who just clicked a verification link (the controller has already
 *     consumed it and left a notice), and
 *   - someone the account gate redirected because their address is unconfirmed.
 *
 * The resend form is a plain POST that works with JavaScript switched off —
 * EmailVerificationController::handle_resend() answers it and redirects. Alpine
 * only intercepts the submit when it is running, swapping the round trip for a
 * fetch, so nothing here depends on JS being available.
 */

$user     = wp_get_current_user();
$verified = hw_user_email_is_verified();
?>

<div class="hw-verify-card">

    <?php if ( $verified ) : ?>

        <div class="hw-verify-card__icon hw-verify-card__icon--ok" aria-hidden="true">
            <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                <path d="M21.801 10A10 10 0 1 1 17 3.335"/><path d="m9 11 3 3L22 4"/>
            </svg>
        </div>

        <h1 class="hw-verify-card__title"><?= esc_html__( 'Your email is confirmed', 'hola-weddings' ) ?></h1>

        <p class="hw-verify-card__text">
            <?= esc_html__( 'Thanks — your account is all set. Your reservations are available now.', 'hola-weddings' ) ?>
        </p>

        <div class="hw-verify-card__actions">

            <a href="<?= esc_url( hw_get_reservations_url() ) ?>" class="btn btn-primary hw-verify-card__btn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                    <path d="M8 2v4M16 2v4M3 10h18"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="m9 16 2 2 4-4"/>
                </svg>
                <?= esc_html__( 'Go to my reservations', 'hola-weddings' ) ?>
            </a>

            <a href="<?= esc_url( home_url( '/' ) ) ?>" class="btn btn-outline-primary hw-verify-card__btn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                    <path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"/><path d="M3 10a2 2 0 0 1 .709-1.528l7-5.999a2 2 0 0 1 2.582 0l7 5.999A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                </svg>
                <?= esc_html__( 'Go to home', 'hola-weddings' ) ?>
            </a>
        </div>

    <?php else : ?>

        <div class="hw-verify-card__icon" aria-hidden="true">
            <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                <rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
            </svg>
        </div>

        <h1 class="hw-verify-card__title"><?= esc_html__( 'Confirm your email address', 'hola-weddings' ) ?></h1>

        <p class="hw-verify-card__text">
            <?php
            printf(
                /* translators: %s: the user's email address */
                esc_html__( 'We sent a confirmation link to %s. Please click it to unlock your reservations.', 'hola-weddings' ),
                '<strong>' . esc_html( $user->user_email ) . '</strong>'
            );
            ?>
        </p>

        <p class="hw-verify-card__hint mt-2">
            <?= esc_html__( "Can't find it? Check your spam folder, or request a new one.", 'hola-weddings' ) ?>
        </p>

        <?php
        /*
         * x-data + @submit.prevent only bind when Alpine is running. Without it
         * the browser submits the form normally and handle_resend() answers, so
         * this is progressive enhancement rather than a JS dependency.
         */
        ?>
        <form method="post" action="" x-data="hwResendVerification" @submit.prevent="send">

            <?php wp_nonce_field( \HW\Auth\EmailVerificationController::RESEND_ACTION . '_' . $user->ID ); ?>
            <input type="hidden" name="action" value="<?= esc_attr( \HW\Auth\EmailVerificationController::RESEND_ACTION ) ?>">

            <button type="submit" class="btn btn-primary hw-verify-card__btn" :disabled="busy">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"
                     :class="busy && 'hw-spin'">
                    <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/>
                </svg>
                <?= esc_html__( 'Request new link', 'hola-weddings' ) ?>
            </button>

            <?php /* Only ever filled in by Alpine; the no-JS path uses notices instead. */ ?>
            <p class="hw-verify-card__result" x-cloak x-show="message"
               :class="status === 'error' ? 'hw-verify-card__result--error' : 'hw-verify-card__result--ok'"
               x-text="message" role="status"></p>
        </form>

        <p class="hw-verify-card__signout">
            <?= esc_html__( 'Not you?', 'hola-weddings' ) ?>
            <a href="<?= esc_url( wp_logout_url( home_url( '/' ) ) ) ?>">
                <?= esc_html__( 'Log out', 'hola-weddings' ) ?>
            </a>
        </p>

    <?php endif; ?>

</div>
