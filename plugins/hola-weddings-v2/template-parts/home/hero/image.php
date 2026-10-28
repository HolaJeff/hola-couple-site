<?php

$hero_img = hw_get_option('hw_main_image', '' );
$old_img_align = hw_get_option('hw_main_img_position', 'center' );
$vertical_align = $options['image_vertical_align'] ?? ( $old_img_align ?? 'center' );
$horizontal_align = $options['image_horizontal_align'] ?? 'center';
?>
<div class="hero-image" style="background-image: url(<?= wp_get_attachment_url($hero_img) ?>); background-position: <?= $vertical_align ?> <?= $horizontal_align ?>"></div>