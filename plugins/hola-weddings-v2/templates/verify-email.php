<?php
/**
 * Template: confirm your email address.
 *
 * Deliberately the site's own layout rather than the account dashboard shell —
 * someone here has no access to the dashboard yet, so framing the screen with a
 * sidebar full of links they cannot use would be misleading. A single centred
 * card on the normal page furniture is the whole design.
 */

get_header();
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">

        <div class="hw-verify-page">
            <div class="container">
                <div class="row justify-content-center">
                    <?php /* One third of the grid on desktop, full width on phones. */ ?>
                    <div class="col-12 col-md-8 col-lg-5">

                        <?php
                        // The account shell used to print these on hw_account_before_content;
                        // this template is on its own, so it prints them itself.
                        hw_print_notices();

                        hw_get_template( 'template-parts/auth/verify-email' );
                        ?>

                    </div>
                </div>
            </div>
        </div>

    </main>
</div>

<?php
get_footer();
