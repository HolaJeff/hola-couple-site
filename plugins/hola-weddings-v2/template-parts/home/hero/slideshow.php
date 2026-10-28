<?php

// is empty, return
if(empty($options['slideshow'])){
    return;
}

?>
<div class="hero-slideshow">
    <?php foreach($options['slideshow'] as $slide): ?>
        <div class="slide" style="background-image: url(<?= wp_get_attachment_url($slide) ?>);"></div>
    <?php endforeach; ?>
</div>
<script>
    jQuery(function($){

        $('.hero-slideshow').slick({
            dots: false,
            arrows: false,
            slidesToShow: 1,
            slidesToScroll: 1,
            autoplay: true,
            infinite: true,
            speed: 1000,
            fade: true,
            cssEase: 'linear',
            pauseOnHover: false
        });
    });
</script>