<?php

namespace HW\Models;

use HW\API\Models\Room;
use HW\Utils\RoomOccupancy;

class RoomBlock extends DecoratorModel{

    public $room = null;

    public static $post_type = 'room_blocks';

    /**
     * constructor
     *
     * @param WP_Post|integer $post
     */
    public function __construct( $post = null )
    {
        parent::__construct($post);

        if($this->post){
            $room_id = $this->getRoomId();
            $this->room = $room_id ? hw_get_room($room_id) : null;
        }
    }

    /**
     * return a list of room blocks
     *
     * @return array Return and array on HW\Models\RoomBlocks
     */
    public static function getByRoomID( $room_id ){

        $default_args = [
            'post_type' => 'room_blocks',
            'posts_per_page' => 1,
            'post_status' => 'any',
            'meta_query' => [
                [
                    'key' => 'hw_room_id',
                    'value' => $room_id
                ]
            ]
        ];

        $query = new \WP_Query($default_args);

        if($query->have_posts()){
            return new static($query->posts[0]); 
        }

        return false;
    }

    /**
     * return prices by occupancy rules
     *
     * @return array
     */
    public function getOccupancyRules(){
        return $this->hw_occupancy_rules;
    }

    /**
     * return inventory data
     *
     * @return array
     */
    public function getInventory(){
        return $this->hw_inventory;
    }

    /**
     * Return API room id
     *
     * @return int|bool
     */
    public function getRoomId(){
        return $this->hw_room_id;
    }

    /**
     * return room from API
     *
     * @return array|bool
     */
    public function getRoom(){
        return $this->room;
    }

    /**
     * return availability
     *
     * @param string $ci
     * @param string $co
     * @return boolean
     */
    public function isAvailable($ci, $co){
        return hw_is_room_available($this, $ci, $co, 1);
    }

    /**
     * return an array of booked units by date
     *
     * @param string $ci
     * @param string $co
     * @return array
     */
    public function getBookedUnits($ci, $co){
        return hw_get_booked_units( $this, $ci, $co);
    }

    /**
     * return the rate type for given configuration
     *
     * @param int $adults
     * @param int $children
     * @param integer $teen
     * @return array
     */
    public function getRoomRateType( $adults, $children, $teen = 0){

        $room = $this->getRoom();
        return hw_determine_room_rate_type($room['id'], $adults, $children, $teen);
    }

    /**
     * return rates by day
     *
     * @param string $checkin
     * @param string $checkout
     * @return array
     */
    public function getDailyRates($checkin, $checkout){

        return hw_get_dates_room_rate($this, $checkin, $checkout);
    }

    /*
    * Calculate the total quote amount of a room
    */
    public function calculateTotal($checkin, $checkout, RoomOccupancy $occupation){
        
        $rateType = $this->getRoomRateType($occupation->getAdults(), $occupation->getChildren(), $occupation->getTeens());
        $slug = $rateType['rate']['slug'];
        
        $ratesPerDay = $this->getDailyRates( $checkin, $checkout);
        $result = ['rates' => [], 'total_per_day' => [], 'total' => 0];
        
        foreach($ratesPerDay as $date => $rates ){
            
            $rate = $rates[$slug];
            
            $result['rates'][$date] = $rate;
            $result['total_per_day'][$date] = $this->calculateDayTotal($rate, $rateType, $occupation);
            $result['total'] += $result['total_per_day'][$date];
        }
        
        return $result;
    }

    /**
     * calculate the day total according to the given rate
     *
     * @param array $rate
     * @param array $rate_type
     * @param RoomOccupancy $occupation
     * @return void
     */
    public function calculateDayTotal( $rate, $rate_type, RoomOccupancy $occupation ){
    
        $price = 0;
        $configuration = $rate_type['configuration'];
        
        $adult_multiplier = $configuration['adults_multiplier'];
        $children_multiplier = $configuration['children_multiplier'];
        $teen_multiplier = $configuration['teen_multiplier'];
        
        $price += $rate['adult'] * $adult_multiplier;
        
        if(hw_is_teen_enabled() && $occupation->getTeens() > 0){
                        
            if(hw_is_2_teen_enabled() && $teen_multiplier > 1 && $rate['teen2'] != ''){
                
                $price += $rate['teen'] * 1;
                $price += $rate['teen2'] * ( $teen_multiplier - 1 );
            }else{
                
                $price += $rate['teen'] * $teen_multiplier;
            }
        }
                    
        if($occupation->getChildren() > 0){
            
            if(hw_is_2_child_enabled() && $children_multiplier > 1 && $rate['child2'] != ''){
                            
                $price += $rate['child'] * 1;
                $price += $rate['child2'] * ( $children_multiplier - 1 );
            }else{
                            
                $price += $rate['child'] * $children_multiplier ;
            }
        }
        
        return $price;
    }
}