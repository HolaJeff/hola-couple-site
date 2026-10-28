<?php
$wedding = hw()->getWedding();
$bride_photo_id = $wedding->getBridePhoto();
$groom_photo_id = $wedding->getGroomPhoto();
$title = get_sub_field('title') ?? __('Getting Married', 'hola-weddings');
$subtitle = get_sub_field('subtitle') ?? __('We Are', 'hola-weddings');
$hide_names =  get_sub_field('hide_names') ?? false;
?>
<div id="couple-section" class="section couple-section">
    <div class="container">
        <div class="inner col-lg-8 offset-lg-2">

            <?php if(!empty($subtitle)): ?>
                <h3 class="section-subtitle text-center secondary-color mb-0 mt-5 mt-md-0"><?= $subtitle ?></h3>
            <?php endif; ?>

            <?php if(!empty($title)): ?>
                <h2 class="section-title primary-color mb-5"><?= $title ?></h2>
            <?php endif; ?>
            
            <div class="row my-5 align-items-center justify-content-center">
                <div class="col-md-6 col mb-5 mb-md-0">
                    <div class="person">
                        <?php if($bride_photo_id): ?>
                            <div class="photo-holder">
                                <div class="photo" style="background-image: url(<?= wp_get_attachment_image_url($bride_photo_id, 'full'); ?>);"></div>
                            </div>
                        <?php endif; ?>
                        <?php if(!$hide_names): ?>
                            <h3 class="name"><?= $wedding->getHerName() ?></h3>
                        <?php endif; ?>
                        <div class="bio"><?= $wedding->getBrideBio() ?></div>
                    </div>
                </div>
                <div class="col-md-6 col">
                    <div class="person">
                        <?php if($groom_photo_id): ?>
                            <div class="photo-holder">
                                <div class="photo" style="background-image: url(<?= wp_get_attachment_image_url( $groom_photo_id, 'full'); ?>);"></div>
                            </div>
                        <?php endif; ?>
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