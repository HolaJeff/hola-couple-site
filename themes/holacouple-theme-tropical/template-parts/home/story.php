<?php
$story_img = hw_get_option('hw_story_image');
$title = get_sub_field('title');
?>
<div id="story" class="section story-section alt-bg">
    <div class="bg-overlay"></div>
    <div class="container">
        <div class="inner">
            <div class="row my-5 align-items-center justify-content-center">
                <?php if($story_img): ?>
                    <div class="col-lg-6 mb-5 mb-lg-0">
                        <div class="revealer animate-right">
                            <div class="revealer-content image-wrapper decoration-left">
                                <div class="image" style="background-image: url(<?= wp_get_attachment_image_url(hw_get_option('hw_story_image'), 'full'); ?>);"></div>
                            </div>
                            <div class="revealer-element"></div>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="col-lg-6">
                    <?php if( !empty($title) ): ?>
                        <div class="section-icon">
                            <?= hw_theme_get_svg_icon('two-hearts') ?>
                        </div>
                        <h2 class="section-title">
                            <?= $title ?>
                        </h2>
                    <?php endif; ?>
                    <div class="story-wrapper frame-decoration text-justify">                      
                        <?= hw_get_option('hw_story'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>