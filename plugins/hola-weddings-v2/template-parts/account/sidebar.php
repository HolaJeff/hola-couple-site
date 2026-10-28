<?php
/**
 * Account sidebar (collapsible rail / mobile drawer)
 */

$view            = function_exists( 'hw_get_account_view' ) ? hw_get_account_view() : '';
$is_dashboard    = in_array( $view, [ 'dashboard', '' ], true );
$is_reservations = in_array( $view, [ 'reservations', 'reservation' ], true );
$is_profile      = ( $view === 'profile' );

// tenant-support: per-site support details, Hola defaults only when empty.
$hs            = function_exists( 'hw_tenant_support' ) ? (array) hw_tenant_support() : [];
$support_email = ! empty( $hs['email'] ) ? $hs['email'] : 'hola@holaweddings.com';
$support_phone = ! empty( $hs['phone'] ) ? $hs['phone'] : '+1 888 472 7077';
$support_tel   = preg_replace( '/[^0-9+]/', '', $support_phone );
?>
<aside class="account-sidebar" id="account-sidebar">

    <div class="account-sidebar__top">
        <a href="<?= home_url() ?>" class="account-sidebar__brand" aria-label="Hola Weddings">
            <img class="account-sidebar__logo" src="<?= HW_ASSETS_URL ?>/img/logo.png" alt="Hola Weddings">
        </a>
        <button type="button" class="account-sidebar__close" @click="$store.accountSidebar.close()" aria-label="<?= esc_attr__( 'Close menu', 'hola-weddings' ) ?>">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <nav class="account-nav" aria-label="<?= esc_attr__( 'Account navigation', 'hola-weddings' ) ?>">

        <a class="account-nav__link <?= $is_dashboard ? 'is-active' : '' ?>" href="<?= hw_get_account_url() ?>" data-title="<?= esc_attr__( 'Dashboard', 'hola-weddings' ) ?>">
            <span class="account-nav__icon"><i class="fas fa-th-large"></i></span>
            <span class="account-nav__label"><?= __( 'Dashboard', 'hola-weddings' ) ?></span>
        </a>

        <a class="account-nav__link <?= $is_reservations ? 'is-active' : '' ?>" href="<?= hw_get_reservations_url() ?>" data-title="<?= esc_attr__( 'Reservations', 'hola-weddings' ) ?>">
            <span class="account-nav__icon"><i class="fas fa-calendar-alt"></i></span>
            <span class="account-nav__label"><?= __( 'Reservations', 'hola-weddings' ) ?></span>
        </a>

        <a class="account-nav__link <?= $is_profile ? 'is-active' : '' ?>" href="<?= hw_get_account_profile_url() ?>" data-title="<?= esc_attr__( 'My Profile', 'hola-weddings' ) ?>">
            <span class="account-nav__icon"><i class="fas fa-user"></i></span>
            <span class="account-nav__label"><?= __( 'My Profile', 'hola-weddings' ) ?></span>
        </a>

    </nav>

    <div class="account-sidebar__bottom">

        <!-- Full contact card (expanded) -->
        <div class="account-help">
            <div class="account-help__title">
                <i class="fas fa-life-ring"></i>
                <span><?= __( 'Need assistance?', 'hola-weddings' ) ?></span>
            </div>
            <a class="account-help__link" href="mailto:<?= esc_attr( $support_email ) ?>"><?= esc_html( $support_email ) ?></a>
            <a class="account-help__link" href="tel:<?= esc_attr( $support_tel ) ?>"><?= esc_html( $support_phone ) ?></a>
        </div>

        <!-- Contact icons (collapsed rail) -->
        <div class="account-help__icons">
            <a class="account-help__icon" href="mailto:<?= esc_attr( $support_email ) ?>" aria-label="<?= esc_attr__( 'Email us', 'hola-weddings' ) ?>" title="<?= esc_attr( $support_email ) ?>">
                <i class="fas fa-envelope"></i>
            </a>
            <a class="account-help__icon" href="tel:<?= esc_attr( $support_tel ) ?>" aria-label="<?= esc_attr__( 'Call us', 'hola-weddings' ) ?>" title="<?= esc_attr( $support_phone ) ?>">
                <i class="fas fa-phone"></i>
            </a>
        </div>
    </div>
</aside>
