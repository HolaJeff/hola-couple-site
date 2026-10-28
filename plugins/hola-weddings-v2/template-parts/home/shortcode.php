<?php
$shortcode = get_sub_field('shortcode'); 
?>
<div class="section shortcode-section">
    <div class="shortcode">
        <?php do_shortcode($shortcode) ?>
    </div>
</div>
        