<?php
$title = get_sub_field('heading');
?>
<div id="story" class="section story-section">
    <div class="container">
        <?php if(!empty($title)): ?>
            <h2 class="section-title primary-color mb-5"><?= $title ?></h2>
        <?php endif ?>
        <div class="story-wrapper frame-decoration">
            <div class="inner">
                <?= hw_get_option('hw_story'); ?>
            </div>
        </div>
    </div>
</div>