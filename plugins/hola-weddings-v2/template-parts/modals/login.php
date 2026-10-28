<?php
/**
 * Login modal.
 *
 * Styled to match the React booking form's login modal (.lm in the booking-form
 * bundle) — same card, icon, field and link treatment — while keeping the
 * Bootstrap 4 shell this one has always used.
 *
 * Which pane is showing is Alpine's job, not Bootstrap's. The panes used to be
 * .collapse elements, which animated their height and read as a slide; they are
 * now x-show bound to $store.hwAuth.mode, cross-fading instead. Bootstrap still
 * owns opening and closing the modal itself, because the theme and the
 * translated strings both point at #loginModal with data-toggle="modal".
 *
 * The markup is load-bearing for assets/src/js/app/index.js, so a few things are
 * fixed and must not be "tidied":
 *   - #ajax-login / #ajax-signup / #ajax-reset-password wrap each pane and are
 *     looked up by id
 *   - the inputs are found by name (username, password, email, signup_*)
 *   - each input's error element is addressed as .next(), so it has to stay the
 *     immediate sibling
 *   - .ajax-login-btn, .hw-signup-btn and .reset-password-btn are the handlers
 *   - .errorMessage is prepended to the pane by the AJAX callbacks
 */

$hw_auth_error = isset( $_GET['hw_auth_error'] ) ? sanitize_key( wp_unslash( $_GET['hw_auth_error'] ) ) : '';
?>
<!-- Login Modal -->
<div class="modal fade hw-lm-modal" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <?php /* Bare x-data: an Alpine scope so the panes' $store bindings run. */ ?>
        <div class="modal-content hw-lm" x-data>

            <button type="button" class="hw-lm__close" data-dismiss="modal" aria-label="<?= esc_attr__( 'Close', 'hola-weddings' ) ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                    <path d="M18 6 6 18M6 6l12 12"/>
                </svg>
            </button>

            <div class="hw-lm__body">

                <?php /* ── Sign in ─────────────────────────────────────────── */ ?>
                <div id="ajax-login" class="hw-lm__pane"
                     x-show="$store.hwAuth.mode === 'signin'"
                     x-transition:enter="hw-lm__pane--in"
                     x-transition:enter-start="hw-lm__pane--from"
                     x-transition:enter-end="hw-lm__pane--to">

                    <div class="hw-lm__icon-wrap" aria-hidden="true">
                        <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                            <path d="M18 20a6 6 0 0 0-12 0"/><circle cx="12" cy="10" r="4"/><circle cx="12" cy="12" r="10"/>
                        </svg>
                    </div>

                    <h2 class="hw-lm__title" id="loginModalLabel"><?= esc_html__( 'Welcome back', 'hola-weddings' ) ?></h2>
                    <p class="hw-lm__subtitle"><?= esc_html__( 'Sign in to continue with your booking.', 'hola-weddings' ) ?></p>

                    <?php if ( $hw_auth_error !== '' ) : ?>
                        <div class="hw-lm__alert" role="alert">
                            <?= esc_html( hw_get_google_auth_error_message( $hw_auth_error ) ) ?>
                        </div>
                    <?php endif; ?>

                    <div class="hw-lm__form">

                        <div class="hw-lm__field">
                            <label class="hw-lm__label" for="username"><?= esc_html__( 'Email or Username', 'hola-weddings' ) ?></label>
                            <input type="text" class="hw-lm__input" id="username" name="username" autocomplete="username" />
                            <?php /* .next() of the input — the JS writes errors here. */ ?>
                            <div id="ajax-login-username-Feedback" class="hw-lm__field-error invalid-feedback"></div>
                        </div>

                        <div class="hw-lm__field">
                            <label class="hw-lm__label" for="passowrd"><?= esc_html__( 'Password', 'hola-weddings' ) ?></label>
                            <input type="password" class="hw-lm__input" id="passowrd" name="password" autocomplete="current-password" />
                            <div id="ajax-login-password-Feedback" class="hw-lm__field-error invalid-feedback"></div>
                            <button class="hw-lm__link hw-lm__link--inline" type="button" @click="$store.hwAuth.set('reset')">
                                <?= esc_html__( 'Forgot your password?', 'hola-weddings' ) ?>
                            </button>
                        </div>

                        <button type="button" class="ajax-login-btn hw-lm__submit btn btn-primary">
                            <?= esc_html__( 'Sign In', 'hola-weddings' ) ?>
                        </button>

                        <?php /* Directly under the submit button, before the Google divider. */ ?>
                        <?php if ( hw_is_signup_enabled() ) : ?>
                            <p class="hw-lm__hint mb-0">
                                <?= esc_html__( "Don't have an account?", 'hola-weddings' ) ?>
                                <button class="hw-lm__link" type="button" @click="$store.hwAuth.set('signup')">
                                    <?= esc_html__( 'Sign up', 'hola-weddings' ) ?>
                                </button>
                            </p>
                        <?php endif; ?>
                    </div>

                    <?php do_action( 'hw_login_modal_after_form' ); ?>
                    <?php do_action( 'hw_auth_after_form', 'signin' ); ?>
                </div>

                <?php /* ── Sign up ─────────────────────────────────────────── */ ?>
                <?php if ( hw_is_signup_enabled() ) : ?>
                <div id="ajax-signup" class="hw-lm__pane" x-cloak
                     x-show="$store.hwAuth.mode === 'signup'"
                     x-transition:enter="hw-lm__pane--in"
                     x-transition:enter-start="hw-lm__pane--from"
                     x-transition:enter-end="hw-lm__pane--to">

                    <div class="hw-lm__icon-wrap" aria-hidden="true">
                        <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/>
                        </svg>
                    </div>

                    <h2 class="hw-lm__title"><?= esc_html__( 'Create your account', 'hola-weddings' ) ?></h2>
                    <p class="hw-lm__subtitle"><?= esc_html__( 'Save your details and follow your reservations.', 'hola-weddings' ) ?></p>

                    <div class="hw-lm__form">

                        <div class="hw-lm__field">
                            <label class="hw-lm__label" for="hw-signup-first-name"><?= esc_html__( 'First Name', 'hola-weddings' ) ?></label>
                            <input type="text" class="hw-lm__input" id="hw-signup-first-name" name="signup_first_name" autocomplete="given-name" />
                            <div class="hw-lm__field-error invalid-feedback"></div>
                        </div>

                        <div class="hw-lm__field">
                            <label class="hw-lm__label" for="hw-signup-last-name"><?= esc_html__( 'Last Name', 'hola-weddings' ) ?></label>
                            <input type="text" class="hw-lm__input" id="hw-signup-last-name" name="signup_last_name" autocomplete="family-name" />
                            <div class="hw-lm__field-error invalid-feedback"></div>
                        </div>

                        <div class="hw-lm__field">
                            <label class="hw-lm__label" for="hw-signup-email"><?= esc_html__( 'Email', 'hola-weddings' ) ?></label>
                            <input type="email" class="hw-lm__input" id="hw-signup-email" name="signup_email" autocomplete="email" />
                            <div class="hw-lm__field-error invalid-feedback"></div>
                        </div>

                        <div class="hw-lm__field">
                            <label class="hw-lm__label" for="hw-signup-password"><?= esc_html__( 'Password', 'hola-weddings' ) ?></label>
                            <input type="password" class="hw-lm__input" id="hw-signup-password" name="signup_password" autocomplete="new-password" />
                            <div class="hw-lm__field-error invalid-feedback"></div>
                        </div>

                        <div class="hw-lm__field">
                            <label class="hw-lm__label" for="hw-signup-password-confirm"><?= esc_html__( 'Confirm Password', 'hola-weddings' ) ?></label>
                            <input type="password" class="hw-lm__input" id="hw-signup-password-confirm" name="signup_password_confirm" autocomplete="new-password" />
                            <div class="hw-lm__field-error invalid-feedback"></div>
                        </div>

                        <?php /* After the last error div, so no input's .next() is displaced. */ ?>
                        <?php wp_nonce_field( 'hw_signup', 'hw_signup_nonce', false ); ?>

                        <button type="button" class="hw-signup-btn hw-lm__submit btn btn-primary">
                            <?= esc_html__( 'Create Account', 'hola-weddings' ) ?>
                        </button>

                        <p class="hw-lm__hint mb-0">
                            <?= esc_html__( 'Already have an account?', 'hola-weddings' ) ?>
                            <button class="hw-lm__link" type="button" @click="$store.hwAuth.set('signin')">
                                <?= esc_html__( 'Sign in', 'hola-weddings' ) ?>
                            </button>
                        </p>
                    </div>

                    <?php do_action( 'hw_auth_after_form', 'signup' ); ?>

                </div>
                <?php endif; ?>

                <?php /* ── Confirm your email ──────────────────────────────── */ ?>
                <?php
                /*
                 * Shown after a sign-in or sign-up that lands on an unconfirmed
                 * address, so the visitor is not thrown onto another page just to
                 * be told to check their inbox. templates/verify-email.php is the
                 * same content as a real page, and stays the fallback for the
                 * emailed link, for direct visits and for people without JS.
                 *
                 * The address and the resend nonce are unknown here — this markup
                 * is rendered while logged out — so both arrive with the auth
                 * response and are bound from the store.
                 */
                ?>
                <div id="hw-auth-verify" class="hw-lm__pane" x-cloak
                     x-show="$store.hwAuth.mode === 'verify'"
                     x-transition:enter="hw-lm__pane--in"
                     x-transition:enter-start="hw-lm__pane--from"
                     x-transition:enter-end="hw-lm__pane--to">

                    <div class="hw-lm__icon-wrap" aria-hidden="true">
                        <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                            <rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                        </svg>
                    </div>

                    <h2 class="hw-lm__title"><?= esc_html__( 'Confirm your email address', 'hola-weddings' ) ?></h2>

                    <p class="hw-lm__subtitle">
                        <?= esc_html__( 'We sent a confirmation link to', 'hola-weddings' ) ?>
                        <strong x-text="$store.hwAuth.verification.email"></strong>.
                        <?= esc_html__( 'Please click it to unlock your reservations.', 'hola-weddings' ) ?>
                    </p>

                    <form method="post" action="" x-data="hwResendVerification" @submit.prevent="send">

                        <input type="hidden" name="action" value="<?= esc_attr( \HW\Auth\EmailVerificationController::RESEND_ACTION ) ?>">
                        <?php /* Bound rather than printed: the account is only known once they authenticate. */ ?>
                        <input type="hidden" name="_wpnonce" :value="$store.hwAuth.verification.nonce">

                        <button type="submit" class="hw-lm__submit btn btn-primary" :disabled="busy">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"
                                 :class="busy && 'hw-spin'">
                                <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/>
                            </svg>
                            <?= esc_html__( 'Request new link', 'hola-weddings' ) ?>
                        </button>

                        <p class="hw-lm__result" x-cloak x-show="message"
                           :class="status === 'error' ? 'hw-lm__result--error' : 'hw-lm__result--ok'"
                           x-text="message" role="status"></p>
                    </form>

                    <p class="hw-lm__hint mt-2">
                        <?= esc_html__( "Can't find it? Check your spam folder.", 'hola-weddings' ) ?>
                    </p>
                </div>

                <?php /* ── Reset password ──────────────────────────────────── */ ?>
                <div id="ajax-reset-password" class="hw-lm__pane" x-cloak
                     x-show="$store.hwAuth.mode === 'reset'"
                     x-transition:enter="hw-lm__pane--in"
                     x-transition:enter-start="hw-lm__pane--from"
                     x-transition:enter-end="hw-lm__pane--to">

                    <div class="hw-lm__icon-wrap" aria-hidden="true">
                        <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                            <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                    </div>

                    <h2 class="hw-lm__title"><?= esc_html__( 'Reset password', 'hola-weddings' ) ?></h2>
                    <p class="hw-lm__subtitle"><?= esc_html__( "Enter your email and we'll send you a reset link.", 'hola-weddings' ) ?></p>

                    <div class="hw-lm__form">

                        <div class="hw-lm__field">
                            <label class="hw-lm__label" for="email"><?= esc_html__( 'Email', 'hola-weddings' ) ?></label>
                            <input type="email" class="hw-lm__input" id="email" name="email" autocomplete="email" />
                            <div id="ajax-reset-password-Feedback" class="hw-lm__field-error invalid-feedback"></div>
                        </div>

                        <button type="button" class="reset-password-btn hw-lm__submit btn btn-primary">
                            <?= esc_html__( 'Send Reset Link', 'hola-weddings' ) ?>
                        </button>
                    </div>

                    <?php do_action( 'hw_auth_after_form', 'reset' ); ?>

                    <button class="hw-lm__link hw-lm__back" type="button" @click="$store.hwAuth.set('signin')">
                        <?= esc_html__( '← Back to sign in', 'hola-weddings' ) ?>
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>
<?php if ( $hw_auth_error !== '' ) : ?>
    <script type="text/javascript">
        // A failed Google sign-in returns to this page with the modal closed, so
        // the message above would never be seen. Reopen it.
        jQuery(function ($) {
            $('#loginModal').modal('show');
        });
    </script>
<?php endif; ?>
