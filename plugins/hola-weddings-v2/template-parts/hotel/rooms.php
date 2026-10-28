<?php

use HW\API\Models\Room;

$room_blocks = hw_get_published_room_blocks();
$person_types = get_person_types();

if(!empty($room_blocks)): ?>
    <div id="accommodation" class="rooms mt-5">
        <div class="rooms-list row">
            <?php foreach($room_blocks as $room_block):
                
                $room = $room_block->getRoom();

                if(empty($room)){
                    continue;
                }

                $room = new Room($room);

                $rate_types = $room->getOccupancyRules();
                $prices = $room_block->getOccupancyRules();
                ?>
                
                <div class="col-lg-6 room">
                    <div class="image-holder" style="background-image: url(<?= $room->getImage() ?>);"></div>
                    <h4 class="room__name mt-3"><?= $room->getName() ?></h4>
                    <?php if(!empty($room->getDescription())): ?>
                        <p class="room__description"><?= $room->getDescription() ?></p>
                    <?php endif; ?>
                    
                    <?php if(!empty($prices)): ?>
                        <div class="rates">
                            <table class="w-100 table table-bordered">
                                <thead>
                                    <tr>
                                        <td></td>
                                        <?php foreach($person_types as $slug => $type):
                                            
                                            if(($slug == 'teen' && !hw_is_teen_enabled()) ||
                                            (in_array($slug, ['child', 'teen', 'child2', 'teen2']) && $hotel->adults_only)){
                                                
                                                continue;
                                            }
                                            ?>
                                            <td><?= hw_get_person_type_label( $slug ) ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($rate_types as $rate): 
                                        $rate_name = $rate['name'];
                                        $rate_slug = $rate['slug'];
                                        ?>
                                        <tr>
                                            <td><?= $rate_name ?></td>
                                            <?php foreach($person_types as $slug => $type):

                                                if(($slug == 'teen' && !hw_is_teen_enabled()) ||
                                                (in_array($slug, ['child', 'teen', 'child2', 'teen2']) && $hotel->adults_only)){
                                                        
                                                    continue;
                                                }

                                                $price_html = '';

                                                if(isset($prices[$rate_slug][$slug])){
                    
                                                    $price = $prices[$rate_slug][$slug];
                                                    
                                                    switch(true){
                                                        case $price === '':
                                                            $price_html = 'N/A';
                                                            break;
                                                        case (int)$price === 0:
                                                            $price_html = __('Free', 'hola-weddings');
                                                            break;
                                                        default:
                                                            $price_html = hw_format_price( $price );
                                                            break;
                                                    }
                                                }else{
                                                    $price_html = 'N/A';
                                                }
                                                ?>
                                                <td><?= $price_html ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <div>
                                <p><?= hw_get_rates_disclosure_label() ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>