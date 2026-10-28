<?php
$wedding = hw()->getWedding();
$bride_photo_id = $wedding->getBridePhoto();
$groom_photo_id = $wedding->getGroomPhoto();
$title = get_sub_field('title') ?? __('Getting Married', 'hola-weddings');
$subtitle = get_sub_field('subtitle') ?? __('We Are', 'hola-weddings');
$hide_names =  get_sub_field('hide_names') ?? false;
?>
<div id="persons-section" class="section persons-section">
    <div class="container">
        <h3 class="section-subtitle text-center secondary-color mb-0 mt-5 mt-md-0"><?= $subtitle ?></h3>
        <h2 class="section-title primary-color mb-0"><?= $title ?></h2>
        <div class="inner mb-5">
            <div class="persons-wrapper">
                <div class="person">
                    <?php if($bride_photo_id): ?>
                        <div class="photo-holder">
                            <div class="photo" style="background-image: url(<?= wp_get_attachment_image_url($bride_photo_id, 'full'); ?>);"></div>
                        </div>
                    <?php endif; ?>
                    <div class="content">
                        <?php if(!$hide_names): ?>
                            <h3 class="name"><?= $wedding->getHerName() ?></h3>
                        <?php endif; ?>
                        <div class="bio"><?= $wedding->getBrideBio() ?></div>
                    </div>
                </div>
                <div class="person mt-5 mt-lg-0">
                    <?php if($groom_photo_id): ?>
                        <div class="photo-holder">
                            <div class="photo" style="background-image: url(<?= wp_get_attachment_image_url( $groom_photo_id, 'full'); ?>);"></div>
                        </div>
                    <?php endif; ?>
                    <div class="content">
                        <?php if(!$hide_names): ?>
                            <h3 class="name"><?= $wedding->getHisName() ?></h3>
                        <?php endif; ?>
                        <div class="bio"><?= $wedding->getGroomBio() ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>