<?php
$shortcode = get_sub_field('shortcode'); 
?>
<div class="section shortcode-section">
    <div class="shortcode">
        <?= do_shortcode($shortcode) ?>
    </div>
</div>
        