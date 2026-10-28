<?php
/**
 * "Continue with Google" button.
 *
 * Rendered through hw_google_login_button(), which resolves the URL and only
 * calls this when Google sign-in is configured for the site.
 *
 * @var string $url     Sign-in URL, already carrying the return address.
 * @var string $label   Button text.
 * @var string $class   Classes for the anchor.
 * @var string $divider Optional separator text rendered above the button.
 */

if ( ! empty( $divider ) ) : ?>
    <div class="hw-auth-divider"><span><?= esc_html( $divider ) ?></span></div>
<?php endif; ?>

<a class="hw-google-btn <?= esc_attr( $class ) ?>"
   href="<?= esc_url( $url ) ?>">
    <!-- Google's official mark, inlined: Feather carries no brand icons. -->
    <svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true" focusable="false">
        <path fill="#4285F4" d="M17.64 9.2c0-.64-.06-1.25-.16-1.84H9v3.48h4.84a4.14 4.14 0 0 1-1.8 2.72v2.26h2.92a8.78 8.78 0 0 0 2.68-6.62z"/>
        <path fill="#34A853" d="M9 18c2.43 0 4.47-.8 5.96-2.18l-2.92-2.26c-.81.54-1.84.86-3.04.86a5.36 5.36 0 0 1-5.03-3.7H1.05v2.34A9 9 0 0 0 9 18z"/>
        <path fill="#FBBC05" d="M3.97 10.72a5.4 5.4 0 0 1 0-3.44V4.94H1.05a9 9 0 0 0 0 8.12l2.92-2.34z"/>
        <path fill="#EA4335" d="M9 3.58c1.32 0 2.51.45 3.44 1.35l2.58-2.59A9 9 0 0 0 1.05 4.94l2.92 2.34A5.36 5.36 0 0 1 9 3.58z"/>
    </svg>
    <span><?= esc_html( $label ) ?></span>
</a>
