<?php

$options = get_field('hw_homepage_hero');

if($options['disabled']){
    return;
}

?>
<div id="hero" class="hero layout-<?= $options['type'] ?> position-relative">
    <div class="bg-container">
        <?php hw_get_template( 'template-parts/home/hero/' . $options['type'], ['options' => $options] ); ?>
    </div>
    <div class="content">
        <?php hw_get_template( 'template-parts/home/hero/content', ['options' => $options] ) ?>
    </div>
</div>