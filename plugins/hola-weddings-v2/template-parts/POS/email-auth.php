<?php
/**
 * PoS — reservation email authentication.
 *
 * Grants temporary access to an invoice by verifying the reservation email.
 * Submits via Alpine to the `hw_pos_invoice_verify_email` AJAX action, and
 * falls back to a plain POST (handled in POS::init()) when JS is disabled.
 */

$invoiceID = isset( $_GET['invoiceID'] ) ? sanitize_text_field( $_GET['invoiceID'] ) : '';
$nonce     = wp_create_nonce( 'hw-pos-verify-email-' . $invoiceID );
?>
<div class="hw-pos-auth"
    x-data="{
        email: '',
        loading: false,
        error: '',
        submit() {
            if (this.loading) return;
            this.error = '';
            if (!this.email) {
                this.error = '<?= esc_js( __( 'Please enter your reservation email.', 'hola-weddings' ) ) ?>';
                return;
            }
            this.loading = true;
            fetch('<?= esc_js( admin_url( 'admin-ajax.php' ) ) ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'hw_pos_invoice_verify_email',
                    invoice_id: '<?= esc_js( $invoiceID ) ?>',
                    _wpnonce: '<?= esc_js( $nonce ) ?>',
                    email: this.email
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) { window.location.assign(data.redirect); return; }
                this.error = data.message || '<?= esc_js( __( 'Something went wrong. Please try again.', 'hola-weddings' ) ) ?>';
                this.loading = false;
            })
            .catch(() => {
                this.error = '<?= esc_js( __( 'Something went wrong. Please try again.', 'hola-weddings' ) ) ?>';
                this.loading = false;
            });
        }
    }">

    <div class="hw-pos-auth__card">

        <div class="hw-pos-auth__icon">
            <i class="fas fa-file-invoice-dollar"></i>
        </div>

        <h1 class="hw-pos-auth__title"><?= __( 'Pay your invoice', 'hola-weddings' ) ?></h1>
        <p class="hw-pos-auth__subtitle">
            <?= __( 'Enter the email address you used for your reservation to securely access your invoice.', 'hola-weddings' ) ?>
        </p>

        <form class="hw-pos-auth__form" method="post" action="" @submit.prevent="submit()">

            <div class="hw-pos-auth__field">
                <label class="hw-pos-auth__label" for="pos-auth-email"><?= __( 'Reservation email', 'hola-weddings' ) ?></label>
                <div class="hw-pos-auth__input-wrap" :class="{ 'is-invalid': error }">
                    <i class="fas fa-envelope hw-pos-auth__input-icon"></i>
                    <input
                        id="pos-auth-email"
                        type="email"
                        name="email"
                        class="hw-pos-auth__input"
                        placeholder="<?= esc_attr__( 'you@example.com', 'hola-weddings' ) ?>"
                        x-model="email"
                        @input="error = ''"
                        :disabled="loading"
                        autocomplete="email"
                        required
                    >
                </div>
                <p class="hw-pos-auth__error" x-show="error" x-text="error" x-cloak></p>
            </div>

            <input type="hidden" name="action" value="hw_pos_invoice_verify_email">

            <button type="submit" class="hw-pos-auth__submit" :disabled="loading">
                <span x-show="!loading">
                    <div class="d-flex align-items-center justify-content-center">
                        <i class="fas fa-sign-in-alt mr-2 mt-1"></i>    
                        <?= __( 'Access Invoice', 'hola-weddings' ) ?>
                    </div>
                </span>
                <span class="hw-pos-auth__loading" x-show="loading" x-cloak>
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    <?= __( 'Verifying…', 'hola-weddings' ) ?>
                </span>
            </button>
        </form>
    </div>
</div>
