<?php
/**
 * Account topbar (sticky header)
 */

$user  = wp_get_current_user();
$first = $user->first_name ?: $user->display_name;
$last  = $user->last_name;

$initials = strtoupper( mb_substr( (string) $first, 0, 1 ) . mb_substr( (string) $last, 0, 1 ) );
if ( trim( $initials ) === '' ) {
    $initials = strtoupper( mb_substr( (string) $user->display_name, 0, 1 ) );
}
?>
<header class="account-topbar">

    <div class="account-topbar__left">
        <button type="button" class="account-topbar__toggle" @click="$store.accountSidebar.toggle()" aria-label="<?= esc_attr__( 'Toggle menu', 'hola-weddings' ) ?>">
            <i class="fas fa-bars"></i>
        </button>

        <a href="<?= home_url() ?>" class="account-action__btn d-none d-lg-flex" aria-label="<?= esc_attr__( 'Go to Homepage', 'hola-weddings' ) ?>" title="<?= esc_attr__( 'Go to Homepage', 'hola-weddings' ) ?>">
            <i class="fas fa-globe-americas"></i>
            <span class="d-none d-md-block"><?= __('Go to Homepage', 'hola-weddings') ?> </span>
        </a>

        <a href="<?= home_url() ?>" class="account-topbar__logo">
            <img src="<?= HW_ASSETS_URL ?>/img/logo.png" alt="Hola Weddings">
        </a>
    </div>

    <div class="account-topbar__right">
        <div class="account-user">
            <a href="<?= home_url() ?>" class="account-action__btn d-lg-none" aria-label="<?= esc_attr__( 'Go to Homepage', 'hola-weddings' ) ?>" title="<?= esc_attr__( 'Go to Homepage', 'hola-weddings' ) ?>">
                <i class="fas fa-globe-americas"></i>
                <span class="d-none d-md-block"><?= __('Go to Homepage', 'hola-weddings') ?> </span>
            </a>
            <a href="<?= wp_logout_url( home_url() ) ?>" class="account-action__btn account-user__logout" aria-label="<?= esc_attr__( 'Logout', 'hola-weddings' ) ?>" title="<?= esc_attr__( 'Logout', 'hola-weddings' ) ?>">
                <i class="fas fa-sign-out-alt"></i>
            </a>
            <span class="account-user__avatar"><?= esc_html( $initials ) ?></span>
            <span class="account-user__meta">
                <span class="account-user__hello"><?= __( 'Hello', 'hola-weddings' ) ?></span>
                <span class="account-user__name"><?= esc_html( $first ) ?></span>
            </span>
        </div>
    </div>
</header>
