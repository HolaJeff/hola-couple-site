<?php

/**
 * display names template in hero content
 *
 * @param array $options
 * @return void
 */
function hw_theme_elegance_home_hero_headline( $options ){

    ?>
    <p class="hero-subtitle text-uppercase">
        <?= __('Save the date', 'hw-theme-elegant' ) ?>
    </p>
    <?php
}
add_action( 'hw_home_hero_content', 'hw_theme_elegance_home_hero_headline', 5, 1);