<?php
$images = hw_get_gallery();
?>
<div id="gallery-section" class="section gallery pb-0">
    <h2 class="section-title"><?= __('Gallery', 'hola-weddings') ?></h2>
    <?php if(!empty($images)): ?>
        <div class="gallery">
            <?php foreach($images as $image): ?>
                <a class="gallery-item" href="<?= wp_get_attachment_image_url($image, 'full') ?>" data-fancybox="gallery">
                    <img src="<?= wp_get_attachment_image_url($image, 'gallery-thumb', 600, 600) ?>" />
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>