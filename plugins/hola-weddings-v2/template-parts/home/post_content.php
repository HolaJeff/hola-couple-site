<?php
$title = get_sub_field('title'); 
?>
<div class="section">
    <div class="container">
        <?php if(!empty($title)): ?>
            <h2 class="section-title"><?= $title ?></h2>
        <?php endif; ?>
        <div class="content">
            <?php the_content(); ?>
        </div>
    </div>
</div>
        