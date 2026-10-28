<?php

use \HW\Helpers\Assets\BlockUI;
use HW\Models\RoomBlock;

/*
 * Return the data to use in the cart page
 */
function hw_get_cart_data(){
    
    $data = hw_get_cart_search_data();
    
    $cart_data = [
        'checkin' => $data['ci'],
        'checkout' => $data['co'],
        'mode' => hw_get_mode(),
        'adults' => $data['adults'],
        'is_payment_enabled' => is_payment_enabled(),
        'is_teen_enabled' => hw_is_teen_enabled(),
        'is_teen_2_enabled' => hw_is_2_teen_enabled(),
        'is_child_2_enabled' => hw_is_2_child_enabled(),
        'teen' => $data['teens'] ?: 0,
        'children' => $data['children'] ?: 0,
        'nights' => hw_get_mode() == 'room' ? getNights($data['ci'], $data['co']) : 1,
        'min_nights' => hw_get_min_nights(),
        'transportation_mode' => hw_get_transportation_mode(),
        'has_transfer' => hw_is_transfer_enabled(),
        'min_deposit' => hw_get_min_deposit(),
        'deposit_type' => hw_get_deposit_type(),
        'insurance_id' => hw_get_insurance_id()
    ];

    if($cart_data['deposit_type'] == 'dynamic'){

        $cart_data['deposit_nights_multiplier'] = hw_get_deposit_nights_multiplier();
    }

    if(hw_is_transfer_enabled()){
        $cart_data['service_fee'] = hw_get_service_fee();
        $cart_data['child_service_fee'] = hw_get_child_service_fee();
    }
    
    return $cart_data;
}

/**
 * return available cart products
 *
 * @return void
 */
function hw_get_cart_products($data){

    $mode = hw_get_mode();

    if($mode == 'room'){
        return hw_get_cart_rooms($data);
    }else{
        return get_packages();
    }

    return false;
}

/*
 * Get rooms totals for current quote
 */
function hw_calc_packages_total( $rate_type, $data ){
    
    $packages = hw_get_cart_packages();
    $rooms_data = hw_get_rooms_data();
    $result = [];
    $slug = $rate_type['rate']['slug'];
    
    foreach($packages->posts as $package){
        
        $room_id = get_field('hw_room', $package->ID);
        $data['ci'] = $from = get_field('from', $package->ID);
        $data['co'] = $to = get_field('to', $package->ID);
        
        if(!$rooms_data[$room_id]['enabled']
            || (!hw_is_room_available($room_id, $from, $to, 1))){
            
            continue;
        }
        
        $result[$room_id] = hw_calc_room_quote_total($room_id, $rate_type, $data);
    }
    
    return $result;
}

/**
 * return adult total for given dates
 *
 * @param int $room_id
 * @param array $rate_type
 * @param string $ci
 * @param string $co
 * @return int
 */
function hw_calc_person_type_total($room_block, $rate_type, $person_type, $ci, $co){
    
    $slug = $rate_type['rate']['slug'];
    $ratesPerDay = hw_get_dates_room_rate($room_block, $ci, $co);
    $adult_total = 0;

    foreach($ratesPerDay as $date => $rates ){
        
        $rate = hw_get_rate_by_slug($rates, $slug);
        if($rate){
            $adult_total += $rate[$person_type];
        }
    }
    
    return $adult_total;
}

/*
 * Calculate the total quote amount of a room
 */
function hw_calc_room_quote_total($room_block, $rate_type, $data){
    
    $slug = $rate_type['rate']['slug'];
    $ratesPerDay = hw_get_dates_room_rate($room_block, $data['ci'], $data['co']);
    $result = ['rates' => [], 'total_per_day' => [], 'total' => 0];

    foreach($ratesPerDay as $date => $rates ){
        
        $rate = hw_get_rate_by_slug($rates, $slug);
        
        $result['rates'][$date] = $rate;
        $result['total_per_day'][$date] = hw_calc_day_total($rate, $rate_type, $data);
        $result['total'] += $result['total_per_day'][$date];
    }
    
    return $result;
}

/*
 * Calculate total of a rate depending rate type
 *
 * return total
 */
function hw_calc_day_total( $rate, $rate_type, $data ){
    
    $price = 0;
    $configuration = $rate_type['configuration'];
    
    $adult_multiplier = intval($configuration['adults_multiplier'] ?: 1);
    $children_multiplier = intval($configuration['children_multiplier'] ?: 0);
    $teen_multiplier = isset($configuration['teen_multiplier']) ? intval($configuration['teen_multiplier']) : 0;
    $price += intval($rate['adult']) * $adult_multiplier;
    
    if(hw_is_teen_enabled() && $data['teen'] > 0){
                    
        if(hw_is_2_teen_enabled() && $teen_multiplier > 1 && $rate['teen2'] != ''){
            
            $price += intval($rate['teen']) * 1;
            $price += intval($rate['teen2']) * ( $teen_multiplier - 1 );
        }else{
            
            $price += intval($rate['teen'] )* $teen_multiplier;
        }
    }
                
    if($data['children'] > 0){
        
        if(hw_is_2_child_enabled() && $children_multiplier > 1 && $rate['child2'] != ''){
                        
            $price += intval($rate['child']) * 1;
            $price += intval($rate['child2']) * ( $children_multiplier - 1 );
        }else{
                        
            $price += intval($rate['child']) * $children_multiplier ;
        }
    }
    
    return $price;
}

/*
 * Get rates per day
 *
 * return an array of rates, index is the date and value is the rate for that specific date
 */
function hw_get_dates_room_rate($room_block, $from, $to){
    
    $dates = hw_get_dates_from_range_for_rental($from, $to);
    $rates = [];
    
    foreach($dates as $date){
        
        $rates[$date] = hw_get_room_date_rates($room_block, $date);
    }
    
    return $rates;
}

/**
 * Get Date rate
 *
 * return the specific rate for a date
 *
 * @param RoomBlock $room_block
 * @param string $date
 * @return array
 */
function hw_get_room_date_rates(RoomBlock $room_block, $date){
    
    if(hw_is_dynamic_prices_enabled()){
        
        $rule = hw_get_date_dynamic_price($room_block->getID(), $date);
        
        if($rule != false && $rule->have_posts()){

            $post = $rule->posts[0];
            return get_field('prices', $post->ID);
        }
    }
    
    return $room_block->getOccupancyRules();
}

/*
 * Get filtered packages for cart
 */
function hw_get_cart_packages(){
    
    return get_packages();
}

/*
 * Delete rows in rooms inventory for a quote
 */
function hw_release_dates($quote_id){
    
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'rooms_inventory';
    
    $sql = "DELETE FROM {$table_name} WHERE quote_id = {$quote_id}";
    $wpdb->query($sql);
}

/*
 * Blocks dates for a room
 */
function hw_block_room_dates($room_block, $checkin, $checkout, $quote_id){
    
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'rooms_inventory';
    
    $dates = hw_get_dates_from_range_for_rental($checkin, $checkout);
    
    if(empty($dates)){
        
        return false;
    }
    
    $sql = "INSERT INTO {$table_name} (room_block_id, quote_id, date) VALUES ";
    $values = [];
    
    foreach($dates as $date){
        
        $values[] = "({$room_block->getID()}, {$quote_id}, '{$date}')";
    }
    
    $sql .= implode(',', $values);
    
    $result = $wpdb->query($sql);
    
    if($result === false){
        
        return false;
    }
    
    return true;
}

/**
 * Return available rooms to book
 *
 * @param array $data
 * @return array
 */
function hw_get_cart_rooms($data){
    
    $room_blocks = hw_get_published_room_blocks();
    $result = [];
    
    $underage = ( !empty($data['teens']) ? (int)$data['teens'] : 0 ) + ( !empty($data['children']) ? (int)$data['children'] : 0 )  + ( !empty($data['infants']) ? (int)$data['infants'] : 0 );
    
    foreach($room_blocks as $id => $room_block){

        $room = $room_block->getRoom();

        // if room wasn't set skip room block
        if(empty($room)){
            continue;
        }
        
        // remove rooms that are for adults only  
        if($underage > 0 && $room['adults_only']){

            continue;
        }

        // DO NOT check availability
        // Remember, rooms can be booked even if not available
        /*if(!$room_block->isAvailable( $data['ci'], $data['co'], 1)){

            continue;
        }*/
        
        // remove rooms that have no rate type
        if(false === ( $rate_type = hw_determine_room_rate_type($room['id'], $data['adults'], $data['children'], $data['teens']))){

            continue;
        }

        $rate_slug = $rate_type['rate']['slug'];
        $occupancy_rules = $room_block->getOccupancyRules();

        // remove rooms that have rate type disabled
        if(isset($occupancy_rules[$rate_slug]['enabled']) && $occupancy_rules[$rate_slug]['enabled'] == "1"){

            continue;
        }

        $data['teen'] = ( !empty($data['teens']) ? (int)$data['teens'] : 0 );

        // Calculate prices
        $room_block->prices = hw_calc_room_quote_total($room_block, $rate_type, $data);

        // if wasn't remove add it to result list
        $result[$room_block->getID()] = $room_block;
    }

    return $result;
}

/**
 * check if a room block has availability
 *
 * @param HW\Models\RoomBlock $room_block
 * @param string $checkin
 * @param string $checkout
 * @param integer $units
 * @return bool
 */
function hw_is_room_available($room_block, $checkin, $checkout, $units = 1){
    
    //$max_units = (int) $rooms_data[$room_id]['inventory'];
    
    // if inventory is not enabled always return true
    if(!hw_is_inventory_enabled()){

        return hw_get_default_availability();
    }

    // check if we reached or passed unavailable date
    $unavailable_date_str = hw_get_unavailable_date();
    if(!empty($unavailable_date_str)){

        $current_date = new DateTime( 'NOW' );
        //$current_date = new DateTime( '2023-04-16' ); // For testing purpose
        $unavailable_date = new DateTime( $unavailable_date_str );
        if($unavailable_date && $current_date >= $unavailable_date){
            
            return false;
        }
    }

    // check if any date in inventory is not available
    if(hw_room_has_inventory($room_block, $checkin, $checkout) == false){
        
        return false;
    }
    
    // check if booked units reach daily inventory
    if(hw_is_room_fully_booked($room_block, $checkin, $checkout) == true){
        
        return false;
    }
    
    return true;
}

/**
 * review if a room is fully booked for a date range
 *
 * @param HW\Models\RoomBlock $room_block
 * @param string $checkin
 * @param string $checkout
 * @return bool
 */
function hw_is_room_fully_booked($room_block, $checkin, $checkout){

    $booked_units = $room_block->getBookedUnits( $checkin, $checkout);
    $dates = hw_get_dates_from_range_for_rental($checkin, $checkout);
    $inventory = $room_block->getInventory();
    
    if(count($booked_units) > 0){

        foreach($dates as $date){
        
            if(isset($booked_units[$date]) && isset($inventory[$date]) && $booked_units[$date]['count'] >= $inventory[$date]){
                return true;
            }
        }
    }

    return false;
}

/**
 * Check inventory by date
 *
 * @param HW\Models\RoomBlock $room_block
 * @param string $checkin
 * @param string $checkout
 * @param integer $units
 * @return bool
 */
function hw_room_has_inventory( $room_block, $checkin, $checkout, $units = 1){

    $dates = hw_get_dates_from_range_for_rental($checkin, $checkout);
    
    $room_inventory_data = $room_block->getInventory();
    foreach($dates as $date){
        if($room_inventory_data[$date] <= 0){
            return false;
        }
    }
    return true;
}

/**
 * Return an array of dates and count of booked rooms per date
 *
 * @param HW\Models\RoomBlock $room_block
 * @param string $checkin
 * @param string $checkout
 * @return array
 */ 
function hw_get_booked_units($room_block, $checkin, $checkout){
    
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'rooms_inventory';
    $post_table = $wpdb->prefix . 'posts';
    
    $result = $wpdb->get_results(
        $wpdb->prepare( "SELECT COUNT(*) AS 'count', date FROM {$table_name} as a
            INNER JOIN {$post_table} as b ON a.quote_id = b.ID
            WHERE a.room_block_id = %d AND a.DATE >= '%s' AND a.DATE <= '%s' AND b.post_status = 'publish'
            GROUP BY DATE",
            $room_block->getID(),
            $checkin,
            $checkout
        ),
        ARRAY_A 
    );

    $new_result = [];

    if(count($result) > 0){
        foreach($result as $i => $data){
            $new_result[$data['date']] = $data;
        }
    }
    
    return $new_result;
}

/**
 * 
 * DEPRECATED
 * 
 * TODO: remove once finished
 * 
 * Return an array of dates and count of booked rooms per date
 *
 * @param int $room_id
 * @param string $checkin
 * @param string $checkout
 * @return array
 */ 
function hw_get_booked_units_by_room_id($room_id, $checkin, $checkout){
    
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'rooms_inventory';
    $post_table = $wpdb->prefix . 'posts';
    
    $result = $wpdb->get_results(
        $wpdb->prepare( "SELECT COUNT(*) AS 'count', date FROM {$table_name} as a
            INNER JOIN {$post_table} as b ON a.quote_id = b.ID
            WHERE a.room_id = %d AND a.DATE >= '%s' AND a.DATE <= '%s' AND b.post_status = 'publish'
            GROUP BY DATE",
            $room_id,
            $checkin,
            $checkout
        ),
        ARRAY_A 
    );

    $new_result = [];

    if(count($result) > 0){
        foreach($result as $i => $data){
            $new_result[$data['date']] = $data;
        }
    }
    
    return $new_result;
}

/*
 * Validate cart search data
 */
function hw_get_cart_search_data(){
    
    $hotel = (object) hw_get_hotel();
    
    $data = [];
    $data['co'] = $_GET['co'] ?? '';
    $data['ci'] = $_GET['ci'] ?? '';
    $data['adults'] = isset($_GET['adults']) ? intval($_GET['adults']) : 1;
    $data['children'] = 0;
    $data['teens'] = 0;
    $data['infants'] = 0;
    
    // Makes sure if hotel is adults only we do not accept kids
    if(!$hotel->adults_only){
        
        $data['teens'] = isset($_GET['teens']) ? intval($_GET['teens']) : 0;  
        $data['children'] = isset($_GET['children']) ? intval($_GET['children']) : 0; 
        $data['infants'] = isset($_GET['infants']) ? intval($_GET['infants']) : 0; 
    }

    // check min nights
    if(!empty($data['ci'])){

        $min_nigths = hw_get_min_nights();
        $checkin_date = new DateTime($data['ci']);
        if(!empty($data['co'])){
            
            $checkout_date = new DateTime($data['co']);
            // check that is greater than min nights
            if($checkout_date <= $checkin_date){
                $data['co'] = $checkin_date->modify('+1 day')->format('Y-m-d');
            }elseif($checkin_date->diff($checkout_date)->days < $min_nigths){
                $data['co'] = $checkin_date->modify("+{$min_nigths} days")->format('Y-m-d');
            }
            
        }else{
            $data['co'] = $checkin_date->modify('+1 day')->format('Y-m-d');
        }
    }
    
    return $data;
}

/*
 * Validate cart search data
 */
function hw_validate_cart_search($data){
    
    if( ( !isset($data['co']) ||empty($data['co']) ) 
            || 
        ( !isset($data['ci']) || empty($data['ci'])  ) ){
        
        return false;
    }
    
    if(!isset($data['adults']) || $data['adults'] < 1){
        
        return false;
    }
    
    return true;
}

/*
 * Get Max number of adults for cart select box
 */
function hw_get_cart_max_adults(){
    
    return intval(hw_get_option('hw_cart_max_adults', 3));
}

/*
 * Get Max number of adults for cart select box
 */
function hw_get_cart_max_children(){
    
    return intval(hw_get_option('hw_cart_max_children', 3));
}

/*
 * Get Max number of adults for cart select box
 */
function hw_get_cart_max_teen(){
    
    return intval(hw_get_option('hw_cart_max_teen', 3));
}

/**
 * Return an array or error messages to include in javascript
 *
 * @return array
 */
function get_i18n_cart_messages(){
    return [
        'checkout_min_nights' => sprintf(__('Min. %d nights', 'hola-weddings'), hw_get_min_nights()),
        'airport_transportation_round_trip' => __('Airport Transportation ( Round Trip )', 'hola-weddings') ,
        'adult' => __('Adult', 'hola-weddings'),
        'teen' => __('Teen', 'hola-weddings'),
        'child' => __('Child', 'hola-weddings'),
        'infant' => __('Infant', 'hola-weddings'),
        'adult_insurance' => __('Adult Insurance', 'hola-weddings'),
        'child_insurance' => __('Child Insurance', 'hola-weddings'),
        'insurance' => __('Insurance', 'hola-weddings'),
        'empty_cart' => __('There are no rooms in your cart', 'hola-weddings'),
        'room_id' => __('Please select a room', 'hola-weddings'),
        "customer_first_name" => __("Insert your first name", 'hola-weddings'),
        "customer_last_name" => __("Insert your last name", 'hola-weddings'),
        "customer_phone_number" => __("Insert the phone number", 'hola-weddings'),
        "customer_email" => [
            'required' => __("Please insert your email address", 'hola-weddings'),
            'email' => __("Insert a valid email address", 'hola-weddings')
        ],
        "customer_state" => __("Insert your State / Province", 'hola-weddings'),
        "customer_country" => __("Insert your Country", 'hola-weddings'),
        "customer_password_confirmation_wrong" => __('Password doesn\'t macth', 'hola-weddings'),
        "customer_password_confirmation_ok" => __('Password macth', 'hola-weddings'),
        "terms_and_conditions" => __("Please accept the terms and conditions", 'hola-weddings'),
        "payment_card_owner" => __("Insert card owner name", 'hola-weddings'),
        "payment_card_number"=> [
            'required' => __("Insert the card number", 'hola-weddings'),
            'minlength' => __("The card number must be 16 digits", 'hola-weddings'),
        ],
        "payment_card_month"=> [
            'required' => __("Required", 'hola-weddings'),
            'minlength' => __("2 digits", 'hola-weddings'),
            'maxlength' => __("2 digits", 'hola-weddings'),
            'digits' => __("Digits only", 'hola-weddings'),
        ],
        "payment_card_year"=> [
            'required' => __("Required", 'hola-weddings'),
            'minlength' => __("4 digits", 'hola-weddings'),
            'maxlength' => __("4 digits", 'hola-weddings'),
            'digits' => __("Digits only", 'hola-weddings'),
        ],
        "payment_card_ccv"=> [
            'required' => __("Required", 'hola-weddings'),
            'minlength' => __("3 digits", 'hola-weddings'),
            'maxlength' => __("3 digits", 'hola-weddings'),
            'digits' => __("Digits only", 'hola-weddings'),
        ],
        "invalid_age" => __('Guest doesn\'t meet age criteria.', 'hola-weddings'),
        "invalid_dob" => __('Invalid date.', 'hola-weddings')
    ];
}

/**
 * Enqueue cart scripts
 *
 * @return void
 */
function enqueue_cart(){

    hw_enqueue_signature();
    wp_enqueue_script('hw-cart');
    BlockUI::import();
}

function hw_enqueue_signature(){

    wp_enqueue_script('trim-signature', HW_URL . 'assets/vendor/trimcanvas/index.js');
    wp_enqueue_script('vendor-signature', 'https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js', ['trim-signature']);
    wp_enqueue_script('signature',  HW_URL . 'assets/js/signature.js', ['jquery', 'vendor-signature'], false, true);
}

/**
 * return cart errors stored in session
 *
 * @return array
 */
function hw_get_cart_errors(){
    
    return hw_get_session()->get('quote_errors', []);
}

/**
 * return submitted data stored in session
 *
 * @return array
 */
function hw_get_submitted_data(){
    
    return hw_get_session()->get('quote_data');
}

/**
 * calculate guest age
 *
 * @param DateTime $date
 * @param DateTime $comparisonDate
 * @return int
 */
function ea_calc_guest_age( DateTime $date, DateTime $comparisonDate){

    if(empty($date)){
        return false;
    }

    return $date->diff($comparisonDate)->y;
}

/**
 * return the deposit type
 *
 * @return string
 */
function hw_get_deposit_type(){

    return hw_get_option('hw_deposit_type', 'fixed');
}

/**
 * return min deposit
 *
 * @return int
 */
function hw_get_min_deposit(){

    $min_deposit = hw_get_option('hw_min_deposit', 200);

    if($increase_date_option = hw_get_deposit_increase_date()){

        $increase_date = new DateTime($increase_date_option);
        $today = new DateTime(date('Y-m-d'));
        
        if($increase_date <= $today){

            $min_deposit = hw_get_deposit_increase_amount();
        }
    }

    return $min_deposit;
}

/**
 * return deposit nights multiplier
 *
 * @return int
 */
function hw_get_deposit_nights_multiplier(){

    return hw_get_option('hw_deposit_nights_multiplier', 1);
}

/**
 * return a list of deposit options
 *
 * @return array
 */
function hw_get_deposit_options(){

    return [
        'deposit',
        'full',
        'custom'
    ];
}

/**
 * Return deposit value label
 *
 * @param string $value
 * @return string
 */
function hw_get_deposit_label($value){

    switch($value){
        case 'deposit':
            return __('Deposit', 'hola-weddings');
            break;
        case 'full':
            return __('Full Amount', 'hola-weddings');
            break;
        case 'custom':
            return __('Custom', 'hola-weddings');
            break;
        default:
            return '';
            break;
    }
}

/**
 * return the booking confirmation url
 *
 * @return string
 */
function hw_get_booking_confirmation_url(){

    return home_url('booking-confirmation');
}