<?php

if(!isset($hotel)){
    $hotel = (object) hw_get_hotel();
}

?>
<div class="hotel__description">
    <?= $hotel->description ?>
</div>