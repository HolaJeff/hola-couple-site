<?php
$content = get_sub_field('content'); 
$title = get_sub_field('title'); 
?>
<div class="section shortcode-section">
    <div class="container">
        <?php if(!empty($title)): ?>
            <h2 class="section-title"><?= $title ?></h2>
        <?php endif; ?>
        <div class="content">
            <?= $content ?>
        </div>
    </div>
</div>
        