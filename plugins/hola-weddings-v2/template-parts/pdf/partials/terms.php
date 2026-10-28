<?php 

$terms = apply_filters( 'the_content', hw_get_terms($id)) ;
$cancellation = apply_filters( 'the_content', hw_get_cancellation_policy($id)) ;

if(!empty($terms) || !empty($cancellation)): ?>
    <?php if(!empty($terms)): ?>
        <h6 class="uppercase"><?= __('Terms and conditions', 'hola-weddings') ?></h6>
        <p><?= $terms ?></p>
    <?php endif; ?>

    <?php if(!empty($cancellation)): ?>
        <h6 class="uppercase"><?= __('Cancellation Policy', 'hola-weddings') ?></h6>
        <p><?= $cancellation ?></p>
    <?php endif; ?>
<?php endif; ?>