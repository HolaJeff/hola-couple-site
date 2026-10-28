<?php
$title = get_sub_field('title') ?? __('Our Story', 'hola-weddings');
$story = hw_get_option('hw_story');
?>
<?php if(!empty($story)): ?>
    <div id="story-section" class="section <?= $bride_photo_id && $groom_photo_id ? 'has-photos' : '' ?>">
        <div class="container text-center">

            <?php if(!empty($title)): ?>
                <h2 class="section-title primary-color"><?= $title ?></h2>
            <?php endif ?>

            <div class="story">
                <?= hw_get_option('hw_story'); ?>
            </div>
        </div>
    </div>
<?php endif ?>