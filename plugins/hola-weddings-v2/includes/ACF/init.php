<?php

// Add fields
add_action('acf/include_field_types', 	function(){
    
    new \HW\ACF\Fields\RoomPrices();
});

