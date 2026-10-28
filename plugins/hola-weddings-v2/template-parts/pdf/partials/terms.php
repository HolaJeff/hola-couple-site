<?php

/** @var int $id */

// Editor content goes straight into the PDF, so it has to be normalized:
// Html2Pdf's parser rejects the loosely-closed markup a WYSIWYG produces.
$terms        = hw_pdf_safe_html( apply_filters( 'the_content', hw_get_terms( $id ) ) );
$cancellation = hw_pdf_safe_html( apply_filters( 'the_content', hw_get_cancellation_policy( $id ) ) );

if ( ! empty( $terms ) || ! empty( $cancellation ) ) : ?>
    <?php if ( ! empty( $terms ) ) : ?>
        <h6 class="uppercase"><?= __( 'Terms and conditions', 'hola-weddings' ) ?></h6>
        <?= $terms ?>
    <?php endif; ?>

    <?php if ( ! empty( $cancellation ) ) : ?>
        <h6 class="uppercase"><?= __( 'Cancellation Policy', 'hola-weddings' ) ?></h6>
        <?= $cancellation ?>
    <?php endif; ?>
<?php endif; ?>
