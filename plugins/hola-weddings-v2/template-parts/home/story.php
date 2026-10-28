<?php
$story_img = hw_get_option('hw_story_image');
$title = get_sub_field('title');
?>
<div id="story" class="section story-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-6">
                <h2 class="section-title"><?= $title ?></h2>
                <?= hw_get_option('hw_story'); ?>
            </div>
            <div class="col-lg-6 d-lg-flex align-items-center">
                <?= wp_get_attachment_image( $story_img , 'full'); ?>
            </div>
        </div>
    </div>
</div>