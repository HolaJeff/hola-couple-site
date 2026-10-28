<?php

$wedding = hw()->getWedding();
$bride_photo_id = $wedding->getBridePhoto();
$groom_photo_id = $wedding->getGroomPhoto();
$hide_names =  get_sub_field('hide_names') ?? false;
$title = get_sub_field('title') ?? __('Getting Married', 'hola-weddings');
$subtitle = get_sub_field('subtitle') ?? __('We Are', 'hola-weddings');
?>
<div id="couple-section" class="section couple-section <?= $bride_photo_id && $groom_photo_id ? 'has-photos' : '' ?>">
    <div class="container text-center">

        <?php if(!empty($subtitle)): ?>
            <h3 class="subtitle primary-color mb-0 mt-5 mt-md-0"><?= $subtitle ?></h3>
        <?php endif ?>

        <?php if(!empty($title)): ?>
            <h2 class="section-title primary-color"><?= $title ?></h2>
        <?php endif ?>

        <?php if(get_sub_field('display_pictures') && $bride_photo_id && $groom_photo_id): ?>
            <div class="couple-photos row">
                <div class="col-12 col-md-6">
                    <div class="photo-wrapper">
                        <div class="photo" style="background-image: url(<?= wp_get_attachment_image_url($bride_photo_id, 'large') ?>);"></div>
                        <div class="details frame-decoration">
                            <?php if(!$hide_names): ?>
                                <h3 class="title"><?= $wedding->getHerName() ?></h3>
                            <?php endif; ?>
                            <p class="bio"><?= $wedding->getBrideBio() ?></p>
                        </div>
                    </div>
                </div>

                <div class="d-md-none d-lg-none divider">
                    <div class="icon-wrapper d-inline-block">
                        <?= hw_theme_get_svg_icon('two-hearts') ?>
                    </div>
                </div>
                
                <div class="col-12 col-md-6">
                    <div class="photo-wrapper">    
                        <div class="photo" style="background-image: url(<?= wp_get_attachment_image_url($groom_photo_id, 'large') ?>);"></div>
                        <div class="details frame-decoration">
                            <?php if(!$hide_names): ?>
                                <h3 class="title"><?= $wedding->getHisName() ?></h3>
                            <?php endif; ?>
                            <p class="bio"><?= $wedding->getGroomBio() ?></p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif ?>
        
        <div class="divider">
            <div class="icon-wrapper d-inline-block">
                <?= hw_theme_get_svg_icon('two-hearts') ?>
            </div>
        </div>
    </div>
</div>