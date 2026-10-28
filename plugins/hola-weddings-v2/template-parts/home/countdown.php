<?php
$title = get_sub_field('title');
$subtitle = get_sub_field('subtitle');
?>
<div class="section countdown-section">
    <div class="container">
        <?php if(!empty($subtitle)): ?>
            <h3 class="section-subtitle">
                <?= $subtitle ?>
            </h3>
        <?php endif; ?>
        <?php if(!empty($title)): ?>
            <h2 class="section-title">
                <?= $title ?>
            </h2>
        <?php endif; ?>
        <?= do_shortcode('[countdown]') ?>
    </div>
</div>