<?php

use HW\API\Operations\Hotels;
use HW\API\Operations\HotelRooms;
use HW\API\Operations\Insurances;
use HW\API\Operations\Insurance;
use HW\API\Models\Room;
use HW\Cache;
use HW\Enums\CardType;

/**
 * return session object
 *
 * @return object
 */
function hw_get_session(){

    return hw()->session;
}

/**
 * Load translations
 */
add_action( 'init', function() {
    
    load_plugin_textdomain( 'hola-weddings', false, dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages' );
});

/**
 * BCP 47 locale for Intl date and number formatting in the React booking form.
 *
 * The Spanish translation (es_ES) is written for Latin American customers, so
 * Spanish formats as Latin America (es-419) rather than Spain.
 *
 * @param string|null $locale WordPress locale; defaults to the current one, which
 *                            TranslatePress sets per request.
 * @return string
 */
function hw_get_intl_locale( $locale = null ){

    $locale = $locale ?: determine_locale();
    $intl   = str_starts_with( $locale, 'es' ) ? 'es-419' : str_replace( '_', '-', $locale );

    return apply_filters( 'hw_intl_locale', $intl, $locale );
}

/**
 * Translate content with the translations stored in TranslatePress.
 *
 * TranslatePress translates page HTML and admin-ajax responses as they are sent,
 * but not REST responses, so the booking form's REST responses call this for the
 * data they return (room names, descriptions). Same lookup TranslatePress does on
 * the legacy form: exact match against the original text.
 *
 * In a REST request this only translates when called from the
 * rest_pre_echo_response filter; TranslatePress returns content untouched
 * anywhere else in a REST request (see hw-rest-hooks.php).
 *
 * The language is passed explicitly: TranslatePress takes it from the URL, and
 * the form's REST calls carry it in X-HW-Locale instead (see hw-rest-hooks.php),
 * which determine_locale() reflects.
 *
 * Returns the content unchanged when TranslatePress isn't active, when the
 * content isn't a non-empty string, or when no translation exists.
 *
 * @param mixed $content
 * @return mixed
 */
function hw_trp_translate( $content ){

    if ( ! is_string( $content ) || trim( $content ) === '' || ! function_exists( 'trp_translate' ) ) {
        return $content;
    }

    // false: return plain translated content, without the markers TranslatePress
    // adds to stop page output from being translated a second time.
    return trp_translate( $content, determine_locale(), false );
}

/**
 * Whether the plugin can serve the given WordPress locale: English, or any
 * locale the plugin ships a translation for.
 *
 * @param string $locale
 * @return bool
 */
function hw_is_supported_locale( $locale ){

    if ( ! is_string( $locale ) || ! preg_match( '/^[a-z]{2,3}(_[A-Z]{2})?$/', $locale ) ) {
        return false;
    }

    return $locale === 'en_US' || file_exists( HW_PLUGIN_PATH . "/languages/hola-weddings-{$locale}.mo" );
}

function hw_get_url(){
    return HW_URL;
}

/*
 * Returns the wedding class
 */
function wedding(){
    return hw()->wedding;
}

/**
 * return start date
 *
 * @return void
 */
function get_start_date(){

    return hw_get_option('hw_start_date', '' );
}

/**
 * Return end date
 *
 * @return void
 */
function get_end_date(){
    
    return hw_get_option('hw_end_date', '' );
}

/*
 * Returns true is payment is enabled
 */
function is_payment_enabled(){
    
    $value = hw_get_option('hw_enable_payment', false);
    return !empty($value) && $value == 1 ? true : false;
}

/*
 * Wrapper function to return an option
 */
function hw_get_option( $key, $default = null){
    
    return is_network_admin() ? get_site_option($key, $default) : get_option($key, $default);
}

/**
 * Returns if transfer is enabled
 *
 * @return bool
 */
function hw_is_transfer_enabled(){
    
    return intval(hw_get_option('hw_has_transfer', 0)) == 1;
}

/*
 * Returns the service fee a.k.a. transport fee
 */
function hw_get_service_fee(){
    
    return intval(hw_get_option('hw_service_fee', 0));
}

/*
 * Returns the service fee a.k.a. transport fee
 */
function hw_get_child_service_fee(){
    
    return intval(hw_get_option('hw_service_fee_child', 0));
}

/*
 * Returns the insurance id
 */
function hw_get_insurance_id(){
    
    return hw_get_option('hw_insurance', null);
}

/*
 * Return currency code
 */
function hw_get_currency(){
    
    return hw_get_option('hw_currency', 'USD');
}

/*
 * Return currency symbol
 */
function hw_get_currency_symbol(){
    
    return hw_get_option('hw_currency_symbol', '$');
}

/**
 * return transportation mode
 *
 * @return string
 */
function hw_get_transportation_mode(){

    return hw_get_option('hw_transfer_multiplier', 'room');
}

/*
 * Return cart mode
 * options:
 * - room
 * - package
 */
function hw_get_mode(){
    
    return hw_get_option('hw_cart_mode', 'room');
}

/*
 * Return true or false if basic price is enabled
 */
function hw_basic_price_enabled(){
    
    return hw_get_option('hw_enable_basic_price', false);
}

/*
 * Return true or false if teen person type is enabled
 */
function hw_is_teen_enabled(){
    
    return hw_get_option('hw_enable_teen', false);
}

/*
 * Return true or false if 2nd teen is enabled
 */
function hw_is_2_teen_enabled(){
    
    return hw_get_option('hw_enable_2_teen', false);
}

/*
 * Return true or false if 2nd teen is enabled
 */
function hw_is_2_child_enabled(){
    
    return hw_get_option('hw_enable_2_child', false);
}

/*
 * Return true if inventory is enabled
 */
function hw_is_inventory_enabled(){
    
    return hw_evaluate_string_to_boolean( hw_get_option('hw_enable_inventory', false) );
}

/*
 * Return true if dynamic prices is enabled
 */
function hw_is_dynamic_prices_enabled(){
    
    return hw_get_option('hw_enable_dynamic_prices', false);
}

/**
 * returns the default rates disclosure
 *
 * @return string
 */
function hw_get_default_rates_disclosure_label(){

    return __('* Rates are per person per night and include hotel taxes. Local Tourist and/or Eco Taxes could apply.', 'hola-weddings');
}

/**
 * return the rates disclosure
 *
 * @return string
 */
function hw_get_rates_disclosure_label(){

    return hw_get_option('hw_label_rates_disclosure', hw_get_default_rates_disclosure_label());
}

/*
 * Return min nights
 */
function hw_get_min_nights(){
    
    $nights = hw_get_option('hw_min_nights', 1);

    if(empty($nights)){
        $nights = 1;
    }

    return $nights;
}

/*
 * return statuses
 */
function hw_get_quote_statuses(){
    
    return [
        'confirmed' => 'Confirmed',
        'pending' => 'Pending',
        'canceled' => 'Canceled'
    ];
}

/**
 * js messages
 *
 * @return array
 */
function hw_get_i18n_messages(){
    return [
        'ajax_login_email_required' => __('Insert valid email', 'hola-weddings'),
        'ajax_login_pwd_required' => __('Insert valid password', 'hola-weddings'),
        'ajax_login_generic_error' => __('An error ocurred with the request. Try again. If the error persist contact us.', 'hola-weddings'),

        // Sign-up pane. Mirrored server-side in HW\Auth\RegistrationService::validate();
        // these are only to save an obviously-doomed round trip.
        'ajax_signup_first_name_required' => __('Please enter your first name.', 'hola-weddings'),
        'ajax_signup_email_required'      => __('Please enter your email address.', 'hola-weddings'),
        'ajax_signup_email_invalid'       => __('Please enter a valid email address.', 'hola-weddings'),
        'ajax_signup_password_required'   => __('Please choose a password.', 'hola-weddings'),
        'ajax_signup_password_short'      => __('Password must be at least 8 characters.', 'hola-weddings'),
        'ajax_signup_password_mismatch'   => __('The passwords do not match.', 'hola-weddings'),
        'ajax_signup_generic_error'       => __('An error ocurred with the request. Try again. If the error persist contact us.', 'hola-weddings'),
    ];
}

/*
 * Format the price
 */
function hw_format_price( $price, $currency = null, $symbol = null){
    
    $currency = !empty($currency) ? $currency : hw_get_currency();
    $symbol = !empty($symbol) ? $currency : hw_get_currency_symbol();
    
    return sprintf('%s%s %s', $symbol, $price, $currency);
}

/**
 * return insurance from API
 *
 * @param  int|null $id
 * @return Insurance|null
 */
function hw_get_insurance($id = null){
    
    static $insurance;
    
    if(!is_null($insurance)){
        
        return $insurance;
    }
    
    if(is_null($id)){
        $id = hw_get_insurance_id();
    }
    
    if(empty($id)){
        
        return null;
    }
    
    $insurance = Insurance::get($id);
    
    return $insurance;
}

function hw_get_insurance_plans($id = null){
    
    static $plans;
    
    if(!is_null($plans)){
        
        return $plans;
    }
    
    if(is_null($id)){
        $id = hw_get_insurance_id();
    }
    
    if(empty($id)){
        return false;
    }
    
    $plans = Insurance::getPlans($id);
    
    return $plans;
}

/*
 * Whether a plan of the given insurance can be sold to a customer of the given
 * US state. Classic insurance (id 2) does not sell the Elite plan (id 4) to New
 * York residents. Mirrored client side in assets/booking-form/utils/insurance.js
 */
function hw_is_insurance_plan_allowed($insurance_id, $plan_id, $state){

    if(intval($insurance_id) === 2 && intval($plan_id) === 4 && strtoupper(trim((string) $state)) === 'NY'){

        return false;
    }

    return true;
}

function hw_get_hotel_id(){

    return hw_get_option('hw_hotel_id', null);
}

/**
 * Return the URL of the booking-form page.
 *
 * Scans for a published page assigned either the new React booking-form
 * template or the legacy one, preferring the new template. Falls back to the
 * site home if none is found.
 *
 * @return string
 */
function hw_get_booking_form_url(){

    $templates = [ 'templates/booking-form.php', 'endpoints/book.php' ];

    foreach ( $templates as $template ) {

        $pages = get_pages([
            'meta_key'    => '_wp_page_template',
            'meta_value'  => $template,
            'post_type'   => 'page',
            'post_status' => 'publish',
            'number'      => 1,
        ]);

        if ( $pages ) {

            return get_permalink( $pages[0] );
        }
    }

    return home_url( '/' );
}

/**
 * return hotel data
 *
 * @return array|bool
 */
function hw_get_hotel(){
    
    static $hotel;
    static $fetching = false;

    if(!is_null($hotel)){

        return $hotel;
    }

    // Re-entered while fetching: this runs during App's constructor (via
    // PersonTypeConfig), and the API call goes through hw(), which builds a new
    // App because the singleton isn't stored yet - so without this it recurses forever.
    if($fetching){

        return false;
    }

    // if transient expired (or holds a previously cached error) then update it from API
    if ( !is_array( $data = hw_get_hotel_transient() ) ) {

        $fetching = true;

        try {
            $data = hw_update_hotel_transient();
        } finally {
            $fetching = false;
        }
    }

    // don't memoize failures, otherwise later calls in the request get the WP_Error
    if( !is_array($data)){

        return false;
    }

    return $hotel = $data;
}

/**
 * return hotel transient
 *
 * @return array|bool
 */
function hw_get_hotel_transient(){

    return  HW\Cache::get( 'hotel' );
}

/**
 * update hotel transient from API
 *
 * @return array|\WP_Error
 */
function hw_update_hotel_transient(){

    $id = hw_get_hotel_id();

    if(empty($id)){
        return new \WP_Error('no-hotel', 'Hotel was not configured');
    }

    // Get it from API
    $hotel = Hotels::getHotel($id);

    // Set transient, only on success so API errors aren't cached
    if( is_array($hotel)){
        HW\Cache::set( 'hotel', $hotel );
    }

    return $hotel;
}

/**
 * Get Room from API
 *
 * Return room if found or false
 *
 * @param integer $id
 * @return array|false
 */
function hw_get_room( int $id ){
    
    static $rooms;
    
    if(!empty($rooms[$id])){
        
        return $rooms[$id];
    }

    $cache = Cache::getInstance();
    $api_room = $cache->get_network('api_room_' . $id);

    // if transient expired then update it from API
    if ( false === $api_room || is_wp_error( $api_room ) ) {
        
        $api_room = HotelRooms::getRoom($id);

        if( is_wp_error($api_room) ){

            return false;
        }

        // the room doesn't exist, set the transient as false
        if( empty($api_room) ){

            $cache->set_network('api_room_' . $id, false, 43200);
            return false;
        }

        // room exists, set the data
        $cache->set_network('api_room_' . $id, $api_room, 43200); // expires in 12 hours
    }

    $rooms[$id] = $api_room;
    
    return $api_room;
}

/*
 * Get Rooms from API
 */
function hw_get_hotel_rooms($id = null){
    
    if(empty($id)){
        
        $id = hw_get_hotel_id();
    }
    
    return HotelRooms::get($id);
}

function hw_get_hotel_amenities( $id = null){
    
    if(empty($id)){
        
        $id = hw_get_hotel_id();
    }
    
    return Hotels::getAmenities($id);
}

function hw_get_events(){
    
    return hw_get_option('hw_events', null);
}

function hw_get_date($formatted = true, $format = 'F d, Y'){
    
    if( $formatted && !empty($format) ){
        return wedding()->getFormattedDate($format);
    }
    
    return wedding()->getDate($format);
}

if( !function_exists( 'hw_get_svg_icon' ) ){
    
    function hw_get_svg_icon( $id ){
        
        $icons_path = HW_ASSETS_PATH . DIRECTORY_SEPARATOR . 'icons' . DIRECTORY_SEPARATOR;
        
        if( $id && file_exists( $icons_path . $id . '.svg' ) ){
            
            echo file_get_contents( $icons_path . $id . '.svg' );
        }
    }
}

function hw_get_gallery(){
    
    return explode(',', get_option('hw_product_image_gallery',[]));
}

function hw_get_hotel_location($hotel){
    return sprintf( '%s, %s', $hotel->state['name'], $hotel->country['name']);
}

function hw_get_hotel_full_address($hotel){
    return sprintf( '%s, %s, %s, %s, %s',
        $hotel->address['line_1'],
        $hotel->address['suburb'],
        $hotel->city['name'],
        $hotel->address['post_code'],
        hw_get_hotel_location($hotel)
    );
}

function hw_get_rooms_data(){
    
    return hw_get_option('hw_rooms', []);
}

function hw_get_cancellation_policy(){
    
    return hw_get_option('hw_cancellation_policy', '');
}

function hw_get_terms(){
    
    return hw_get_option('hw_terms', '');
}

function hw_get_rooms_order(){
    
    return hw_get_option('hw_rooms_order', []);
}

/**
 * Wrapping function to return hotel rooms
 *
 * @param int $hotel_id
 * @return array|\WP_Error
 */
function hw_get_rooms( $hotel_id = null ){
    
    if(empty($hotel_id)){
        
        $hotel_id = hw_get_hotel_id();
    }
    
    $rooms = HotelRooms::get($hotel_id);

    if(is_wp_error($rooms)){

        return $rooms;
    } 
    
    if($rooms != false){

        foreach($rooms as $i => $room){
            $rooms[$i] = new Room($room);
        }
    }
    
    return $rooms;
}

function hw_get_frontend_formatted_rooms($data){
    
    $room_blocks = hw_get_cart_rooms($data);
    $return_data = [];
    
    foreach($room_blocks as $room_block){
        
        $id = $room_block->ID;
        
        $return_data[$id] = [
            'id' => $id,
            'name' => $room_block->post_title,
            'prices' => $room_block->hw_occupancy_rules
        ];
    }
    
    return $return_data;
}

function generateDateArr($book_from,$book_to, $format = null){
			
	if(empty($format)){
		$begin = new DateTime( $book_from );
		$end = new DateTime( $book_to );
	}else{
		$begin = DateTime::createFromFormat( $format, $book_from );
		$end = DateTime::createFromFormat( $format, $book_to );
	}
	$end = $end->modify( '+1 day' ); 
			
	$interval = new DateInterval('P1D');
	$daterange = new DatePeriod($begin, $interval ,$end);
			
	$tmp = array();
	foreach($daterange as $date){
				
		$tmp[] = $date->format('Y-m-d');
	}
			
	return $tmp;
}

function getNights($book_from,$book_to){
    
    return (count(generateDateArr($book_from, $book_to)) - 1);
}

function get_person_types(){
    
    $types =  [
        'adult' => __('Adult', 'hola-weddings'),
        'child' => __('Child', 'hola-weddings')
    ];
    
    if(hw_is_2_child_enabled()){
        
        $types['child2'] = __('Child 2', 'hola-weddings');
    }
    
    if(hw_is_teen_enabled()){
        
        $types['teen'] = __('Teen', 'hola-weddings');
        
        if(hw_is_2_teen_enabled()){
        
            $types['teen2'] = __('Teen 2', 'hola-weddings');
        }
    }
    
    return $types;
}

/**
 * Return the corresponding person type label
 *
 * @param string $slug
 * @return void
 */
function hw_get_person_type_label($slug){

    $types = get_person_types();
    $label = $types[$slug];

    switch($slug){
        case 'teen':
        case 'teen2':
            return hw_get_teen_label($label);
            break;
        case 'child':
        case 'child2':
            return hw_get_child_label($label);
            break;
        case 'infant':
            return hw_get_infant_label($label);
            break;
        default:
            return $label;
            break;
    }
}

/**
 * return teen label with ages
 *
 * @param string $label
 * @return void
 */
function hw_get_teen_label($label){

    $ages = hw_get_option('hw_ages');

    if(empty($ages['teen_min']) || empty($ages['teen_max'])){
        return $label;
    }

    return sprintf('%s (%s - %s)', $label, $ages['teen_min'], $ages['teen_max']);
}

/**
 * return infant label with ages
 *
 * @param string $label
 * @return void
 */
function hw_get_infant_label($label){

    $ages = hw_get_option('hw_ages');

    if($ages['infant_max'] <= 0){
        $ages['infant_max'] = 1;
    }

    return sprintf('%s (0 - %s)', $label, $ages['infant_max']);
}

/**
 * return child label with ages
 *
 * @param string $label
 * @return void
 */
function hw_get_child_label($label, $format = '%s (%s - %s)'){

    $ages = hw_get_option('hw_ages');

    if($ages['children_min'] < 0 || empty($ages['children_max'])){
        return $label;
    }
    
    return sprintf($format, $label, $ages['children_min'], $ages['children_max']);
}

/**
 * return age labels
 *
 * @return array
 */
function hw_get_age_labels(){

    $saved_value =  hw_get_option('hw_ages');

    return [
        'teen_min' => $saved_value['teen_min'] ?: 13,
        'teen_max' => $saved_value['teen_max'] ?: 17,
        'children_min' => $saved_value['children_min'] ?: 2,
        'children_max' => $saved_value['children_max'] ?: 12,
        'infant_max' => $saved_value['infant_max'] ?: 1,
    ];
}

/*
 * Get rate types
 *
 * Return WP_Query object
 */
function get_packages(){
    
    $args = [
        'post_type' => 'packages',
        'posts_per_page' => -1,
        'order_by' => 'menu_order ID',
        'order' => 'ASC'
    ];
    
    return new \WP_Query($args);
}

/*
 * Old rate determination
 */
function determineRateType($adults, $kids){
    
    switch(true){
        
        case $adults == 1 && $kids == 0:
            return [ 'adult' => [ 'rate' => 'single', 'quantity' => 1 ]];
            break;
        case $adults == 2 && $kids == 0:
            return [ 'adult' => [ 'rate' => 'double', 'quantity' => 2 ]];
            break;
        case $adults == 3 && $kids == 0:
            return [ 'adult' => [ 'rate' => 'triple', 'quantity' => 3 ]];
            break;
        case $adults == 1 && $kids == 1:
            return [ 'adult' => [ 'rate' => 'double', 'quantity' => 2 ]];
            break;
        case $adults == 2 && $kids == 1:
            return [ 'adult' => [ 'rate' => 'double', 'quantity' => 2 ], 'child' => 1];
            break;
        case $adults == 3 && $kids == 1:
            return [ 'adult' => [ 'rate' => 'triple', 'quantity' => 3 ], 'child' => 1];
            break;
        case $adults == 2 && $kids == 2:
            return [ 'adult' => [ 'rate' => 'double', 'quantity' => 2 ], 'child' => 2];
            break;
        case $adults == 1 && $kids == 2:
            return [ 'adult' => [ 'rate' => 'double', 'quantity' => 2 ], 'child' => 1];
            break;
    }
}

/**
 * check if a room has occupancy rule configuration according to search
 *
 * @param int $room_id
 * @param int $adults
 * @param int $children
 * @param integer $teen
 * @return bool
 */
function hw_determine_room_rate_type( $room_id, $adults, $children, $teen = 0){

    $room = hw_get_room($room_id);
    $rules = $room['occupancyrules'];
    $teenEnabled = hw_get_option('hw_enable_teen', false);
    
    foreach($rules as $rule){
        
        $configurations = $rule['configurations'];

        if(empty($configurations)){
            continue;
        }

        foreach($configurations as $configuration){
                
            $conf_adults = $configuration['adults'];
            $conf_teens = isset($configuration['teen']) && $configuration['teen'] != '' ? $configuration['teen'] : 0;
            $conf_children = isset($configuration['children']) && $configuration['children'] != '' ? $configuration['children'] : 0;

            // Check against adults and children
            if(!$teenEnabled){
                
                if($conf_adults == $adults && $conf_children == $children ){
                    
                    return [
                        'rate' => [
                            'id' => $rule['id'],
                            'slug' => $rule['slug']
                        ],
                        'configuration' => $configuration
                    ];
                }
            }
            // Check against teens too
            else{
                
                if($conf_adults == $adults && $conf_children== $children
                && $conf_teens == $teen){
                    
                    return [
                        'rate' => [
                            'id' => $rule['id'],
                            'slug' => $rule['slug']
                        ],
                        'configuration' => $configuration
                    ];
                }
            }
        }
    }

    return false;
}

/*
 * Return the rate by slug
 */
function hw_get_rate_by_slug($rates, $slug){
    
    return $rates[$slug] ?? false;
}

function hw_get_insurance_range( $plan, $target ){
            
    $result = false;
            
    foreach($plan['prices'] as $id => $price){
                    
        if( (int)$price['min'] <= $target && (int)$price['max'] >= $target){
            
            $result = $price;
        }
    };
            
    return $result;
}

function hw_write_log($log) {
    //if (true === WP_DEBUG) {
        if (is_array($log) || is_object($log)) {
            error_log(print_r($log, true));
        } else {
            error_log($log);
        }
    //}
}

function on_mail_error($wp_error){
    hw_write_log($wp_error);
}
add_action( 'wp_mail_failed', 'on_mail_error', 10, 1 ); 

function hw_calc_package_nights($id){
    
    $ci = date_create_from_format('Y-m-d', get_field('from', $id) );
    $co = date_create_from_format('Y-m-d', get_field('to', $id) );
    
    return date_diff($ci, $co)->days;
}

/**
 * return label according to card type
 *
 * @param string $type
 * @return string
 */
function hw_get_card_type_label($type){

    switch($type){
        case 'visa':
            return 'Visa';
            break;
        case 'mc':
            return 'MasterCard';
            break;
        case 'amex':
            return 'American Express';
            break;
        default:
            return '';
            break;
    }
}

/**
 * return the date the availability is disable from
 * This date represent the day the program should stop confirming reservations.
 * 
 * It is compared againts server current time in business timezone (cancun)
 *
 * @return string|bool
 */
function hw_get_unavailable_date(){

    return hw_get_option('hw_date_unavailable', false);
}

/**
 * return card types
 *
 * @return array
 */
function hw_get_card_types(){

    return CardType::options();
}