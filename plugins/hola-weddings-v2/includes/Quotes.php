<?php

namespace HW;

use HW\API\Operations\Insurance;
use HW\Booking\Managers\PersonTypeManager;
use HW\Email\EmailSender;
use HW\Email\Templates\NewQuoteAdminNotification;
use \HW\Fields\AgeSelector;
use HW\Models\Booking\PersonType;

class Quotes{
    
    use Traits\Singleton;
    
    public static $confirmed_status = 'confirmed';
    
    public static $pending_status = 'pending';
    
    // LEGACY, DECRYPT-ONLY: superseded by the per-environment HW_CARD_KEY
    // (wp-config.php) + AES-256-GCM in the hw-card-crypto mu-plugin. New card
    // data is never written with this key; it remains solely so pre-v2 blobs
    // can be decrypted and migrated. Remove after migration + non-prod scrub.
    public static $key = 'j|(aoMy3"h-W?Z|fZ]R(*MxtrLqFDK';

    public static $cipher = "aes-256-ctr";
    
    protected $errors = [];
    
    protected $data = [];
    
    public function __construct(){
        
        add_action( 'init', [ $this, 'process_quote_submission'] );
        add_action( 'hw_after_create_quote', [ $this, 'send_email_to_admin'], 10, 2 );
        add_action( 'after_delete_post', [$this, 'on_delete_quote'] );
        
        if(is_admin()){
            
            add_action('init', function(){
                
                global $post;
                
                if(isset($_GET['hw_delete_card']) && $_GET['hw_delete_card'] == 1 && current_user_can('administrator')){
                    
                    $id = $_GET['post'];
                    $quote = hw_get_quote($id);

                    if($quote->exists() && $quote->hasCardData()){

                        $card = $quote->getCardData();
                        $card->setCCV('');
                        $quote->setCardData($card);
                    }
                }
            });

            add_action('admin_init', [$this, 'handle_admin_actions']);
        }
    }
    
    /*
     * Trigger dates release when the post is deleted
     */
    public function on_delete_quote( $post_id ){
        
        hw_release_dates($post_id);
    }

    /**
     * utility function to redirect to cart
     *
     * @param array $data
     * @return void
     */
    public function redirect_to_cart($data){

        do_action('hw_before_cart_redirect');

        wp_redirect( add_query_arg( [ 
            'ci' => $data['ci'],
            'co' => $data['co'],
            'adults' => $data['adults'],
            'teens' => $data['teen'],
            'children' => $data['children'],
            'infants' => $data['infants']
            ], 
            strtok($_SERVER["REQUEST_URI"], '?') ) );
        
        exit;
    }
    
    /**
     * Process quote submission
     *
     * @return void
     */
    public function process_quote_submission(){
        
        global $wp;

        
        if( isset($_POST['action']) && $_POST['action'] == 'hw_process_quote' ){
            
            if(!wp_verify_nonce( $_REQUEST['_wpnonce'], 'hw-process-quote' )){
                return;
            }
            
            $session = hw_get_session();
            $hotel = (object) hw_get_hotel();
            
            
            $data['mode'] = hw_get_mode();
            $data['ci'] = $_POST['checkin'];
            $data['co'] = $_POST['checkout'];
            $data['adults'] = $_POST['adults'];
            $data['children'] = 0;
            $data['teen'] = 0;
            $data['infants'] = 0;
            $data['guests'] = $_POST['guests'];
            $data['additional_notes'] = $_POST['additional_notes'] ?? '';
            $data['customer'] = $customer = $_POST['customer'];
            $data['payment'] = $payment = $_POST['payment'] ?? [];
            $data['signature'] = $_POST['signature'];
            $data['deposit'] = $_POST['deposit'] ?? [];
            $data['children'] = 0;
            
            $data['toc'] = $_POST['terms_and_conditions'];
            $data['extra_checkboxes'] = $_POST['extra_checkboxes'] ?? [];
            $data['insurance_decline'] = !empty($_POST['insurance_decline']) ? 1 : 0;
            $data['insurance_declined'] = 0;
            $ins_offer = hw_get_insurance();
            if(hw_get_insurance_decline_text() !== '' && ($_POST['customer']['country'] ?? '') == 'US' && $ins_offer && !is_wp_error($ins_offer)
                && !empty($ins_offer['plans']) && !intval($_POST['insurance_plan'] ?? 0)){

                // travel protection was offered and not chosen: the decline must be explicit
                $data['insurance_declined'] = $data['insurance_decline'] ? 1 : -1;
            }
            $bed = sanitize_text_field(wp_unslash($_POST['bed_preference'] ?? ''));
            $data['bed_preference'] = in_array($bed, hw_get_bed_preferences(), true) ? $bed : '';
            $data['transfer_opt_in'] = hw_guest_wants_transfer() ? 1 : 0;
            $data['quote_status'] = self::$pending_status;

            if(hw_get_mode() == 'room'){
                
                $data['room_block_id'] = $_POST['room_block_id'];
            }else{
                
                $data['package_id'] = $_POST['package_id'];
                $data['ci'] = get_field('from', $data['package_id']);
                $data['co'] = get_field('to', $data['package_id']);
            }
            
            // Makes sure if hotel is adults only we do not accept kids
            if(!$hotel->adults_only){
                
                $data['children'] = $_POST['children'];  
                $data['infants'] = $_POST['infants'];  
            }
            
            if(hw_is_teen_enabled()){
                
                $data['teen'] = $_POST['teens'];  
            }
            
            if(hw_get_mode() == 'package'){
                
                $package = get_post($data['package_id']);
                $data['room_block_id'] = get_field('hw_room', $package->ID);
            }

            // clear errors
            $session->set( 'quote_errors', [] );

            // save data in session, if is not valid will refill the form
            $session->set('quote_data', $data);

            // Get the room block
            $room_block = hw_get_room_block($data['room_block_id']);

            if(!$this->validate_quote_data($data, $room_block)){
                
                $this->redirect_to_cart($data);
            }

            /**
             * Ownership of a reservation comes from being signed in, never from
             * the contact email on the form.
             *
             * This block used to create a WordPress account for any unrecognised
             * email and log the visitor straight in, and to authenticate against
             * an existing account when the address matched. Both are gone: an
             * anonymous submission now stays anonymous and the reservation is
             * stored ownerless, to be claimed once the person registers and
             * confirms that address (see hw_claim_guest_quotes).
             */
            
            $total = 0;
            $nights = hw_get_mode() == 'room' ? getNights($data['ci'], $data['co']) : 1;

            /**
             * Process customer data
             */
            $this->process_customer_data($data);

            /*
             * Calc room rate
             */
            $room = $room_block->getRoom();
            $prices = $room_block->getOccupancyRules();
            $room_id = $data['room_id'] = $room['id'];
            
            $rate_type = hw_determine_room_rate_type($room_id, $data['adults'], $data['children'], $data['teen']);
            
            /*
             * return and array with total and rates per day
             */
            $room_quote_totals = hw_calc_room_quote_total($room_block, $rate_type, $data);
            $room_total = $room_quote_totals['total'];
            $total += $room_total;
            
            /*
             * Add Transfer Fee
             */
            $transfer_fee = hw_get_service_fee();
            $transfer_mode = hw_get_transportation_mode();
            $trasfer_total = 0;
            switch($transfer_mode){
                case 'room':
                    $trasfer_total = $transfer_fee;
                    break;
                case 'person':
                    $child_fee = hw_get_child_service_fee() ?: $transfer_fee;
                    $transfer_multiplier = $data['adults'] + $data['teen'];
                    $child_multiplier = $data['children'];
                    $trasfer_total = intval($transfer_multiplier) * $transfer_fee;
                    if($child_multiplier > 0){
                        $trasfer_total += $child_multiplier * $child_fee;
                    }
                    break;
            }
            if(!hw_guest_wants_transfer()){

                // optional transfers, and this guest said no
                $trasfer_total = 0;
            }
            $data['service_fee'] = $trasfer_total;
            $total += $trasfer_total;
            
            /*
             * Cal insurance cost
             */
            $data['insurance_id'] = null;
            $data['insurance_plan_id'] = null;

            $insurance =  hw_get_insurance();
            
            $insurance_total = 0;
            $insurance_rates = [];
            $insurance_plan_id = intval( $_POST['insurance_plan'] );

            if($customer['country'] == 'US' && $insurance && $insurance_plan_id){

                $data['insurance_id'] = $insurance['id'];
                $data['insurance_plan_id'] = $insurance_plan_id;

                $plan = Insurance::getPlan($insurance_plan_id);
                
                if($plan){

                    switch($insurance['mode']){
                        case 'night':
                            $nights_multiplier = hw_get_mode() == 'room' ? $nights : hw_calc_package_nights($data['package_id']);
                            $rates = hw_get_insurance_range($plan, $nights_multiplier);
                            $insurance_total += ( $rates['adult'] * ( $data['adults'] + $data['teen'] ) );
                            $insurance_total += ( $rates['child'] * $data['children'] );
                            $insurance_rates = $rates;
                            break;
                        case 'price':
                            $rates = hw_get_insurance_range($plan, $room_total);
                            $insurance_total += ( $rates['adult'] * ( $data['adults'] + $data['children'] + $data['teen']) );
                            $insurance_rates = $rates;
                            break;
                        case 'person_total':
                            // this insurance is calculated with adult price, even if there are children or teens
                            $adult_transfer_cost = 0;
                            $child_transfer_cost = 0;

                            // alter transfer cost to be added to the person total only if transfer is enabled
                            if(hw_is_transfer_enabled() && hw_guest_wants_transfer()){

                                switch(hw_get_transportation_mode()){
                                    case 'room':
                                        $total_persons = $data['adults'] +  $data['teen'] + $data['children'] + $data['infants'];
                                        $adult_transfer_cost = $child_transfer_cost = hw_get_service_fee() / $total_persons;
                                        break;
                                    case 'person':
                                        $adult_transfer_cost = hw_get_service_fee();
                                        $child_service_fee = hw_get_child_service_fee();
                                        $child_transfer_cost = $child_service_fee > 0 ? $child_service_fee : $adult_transfer_cost;
                                        break;
                                }
                            }

                            // adult
                            $adult_total = hw_calc_person_type_total($room_block, $rate_type, 'adult', $data['ci'], $data['co']) + $adult_transfer_cost;
                            $rates = hw_get_insurance_range($plan, $adult_total);
                            $insurance_total += $rates['adult'] * $data['adults'];
                            $insurance_rates['adult'] = $rates['adult'];

                            // teen
                            if($data['teen'] > 0){
                                $teen_total = hw_calc_person_type_total($room_block, $rate_type, 'teen', $data['ci'], $data['co']) + $adult_transfer_cost;
                                $rates = hw_get_insurance_range($plan, $teen_total);
                                $insurance_total += $rates['teen'] * $data['teen'];
                                $insurance_rates['teen'] = $rates['teen'];
                            }

                            // child
                            if($data['children'] > 0){
                                $child_total = hw_calc_person_type_total($room_block, $rate_type, 'child', $data['ci'], $data['co']) + $child_transfer_cost;
                                $rates = hw_get_insurance_range($plan, $child_total);
                                $insurance_total += $rates['child'] * $data['children'];
                                $insurance_rates['child'] = $rates['child'];
                            }

                            // infant
                            if($data['infants'] > 0){
                                $infant_total = 0;
                                $rates = hw_get_insurance_range($plan, $infant_total);
                                $insurance_total += $rates['infant'] * $data['infants'];
                                $insurance_rates['infant'] = $rates['infant'];
                            }

                            break;
                        case 'total_cost_per_person':
                            // CV insurance calculation, get total ( room + transfer ) / amount of people (including infants), infants always don't pay so they are in the lower tier
                            $total = $room_total + $trasfer_total;
                            $persons_count = $data['adults'] + $data['children'] + $data['teen'] + $data['infants'];
                            $price_per_person = $total / $persons_count;

                            $prices = hw_get_insurance_range($plan, $price_per_person);

                            $insurance_total = ( $data['adults'] * $prices['adult'] ) 
                                + ( $data['teen'] * $prices['teen'] ) 
                                + ( $data['children'] * $prices['child'] ) 
                                + ( $data['infants'] * $prices['infant'] );

                            $insurance_rates = $prices;
                            break;
                        case 'room_total':
                            $rates = hw_get_insurance_range($plan, $room_total);
                            $insurance_rates = $rates;
                            $cost = 0;
                            
                           switch($plan['calculation_type']){
                                case 'fixed':
                                default:
                                    $cost =  $rates['amount'];
                                    break;
                                case 'percentage':
                                    $cost = round( $room_total * ( $rates['amount'] / 100 ), 0, PHP_ROUND_HALF_UP );
                                    break;
                            }

                            $insurance_total += $cost;
                            break;
                    }
                }
                
                $total += $insurance_total;
            }

            $is_available = $room_block->isAvailable( $data['ci'], $data['co']);

            /**
             * validate deposit here, due to the dynamic deposit we must know the total
             */
            if($is_available && !$this->validate_deposit($data, $room_quote_totals)){

                $this->redirect_to_cart($data);
            }

            /**
             * modify quote status
             */
            if((int) $data['adults'] === 1){ // single occupancy is always on-request — TO approval, card not charged yet
            	$data['quote_status'] = self::$pending_status;
            }elseif(hw_force_quote_status()){ // force status

                $data['quote_status'] = hw_forced_quote_status();
            }else{
                // follow normal flow, if the room is available status is confirmed, otherwise is pending
                if($is_available){

                    $data['quote_status'] = self::$confirmed_status;
                }else{
    
                    $data['quote_status'] = self::$pending_status;
                }
            }
            
            // PCI: the CVV is validated above but NEVER stored — Classic accepts
            // charges without it (proven 2026-08-31), and persisting it is
            // prohibited (Req 3.3.1). Held cards charge CVV-less on confirm.
            $payment_to_store = (array) $data['payment'];
            unset($payment_to_store['card-ccv']);

            // Encrypt the data — v2 crypto (per-environment key, authenticated) via the
            // hw-card-crypto mu-plugin; legacy CTR only if the mu-plugin is absent.
            $iv = null;
            if (function_exists('hw_card_encrypt')
                && ($card_info = hw_card_encrypt($payment_to_store)) !== null) {

                // v2 blobs carry their own IV; hw_iv stays unset for them
            } else {
                error_log('[CARD-CRYPTO] hw-card-crypto.php missing or no HW_CARD_KEY — storing card with LEGACY crypto (fix wp-config)');
                $ivlen = openssl_cipher_iv_length(self::$cipher);
                $iv = openssl_random_pseudo_bytes($ivlen);
                $card_info = openssl_encrypt( json_encode($payment_to_store), self::$cipher, self::$key, $options=0, $iv);
            }
            
            // ── Saved billing default ─────────────────────────────────────────
            // Persist the user's saved billing (user meta) only on explicit consent.
            // Identity (email/name) lives on the WP user and is not touched here.

            $user_id = get_current_user_id();

            // Opt-in account creation. validate_quote_data() has already checked
            // the address is unused and the password pair is sound. The account
            // starts unverified, so the reservation only surfaces under My
            // Reservations once they click the link.
            if ( ! $user_id && $this->is_creating_account() ) {

                $new_user_id = ( new \HW\Auth\RegistrationService() )->register( [
                    'first_name' => $customer['first_name'] ?? '',
                    'last_name'  => $customer['last_name'] ?? '',
                    'email'      => $customer['email'] ?? '',
                    'password'   => (string) ( $_POST['password'] ?? '' ),
                ] );

                // A failure here must not lose the booking: fall through and
                // store it as a guest reservation, which they can still claim
                // later by registering with the same address.
                if ( ! is_wp_error( $new_user_id ) ) {
                    $user_id = $new_user_id;
                }
            }

            if ( ! empty( $_POST['update_customer_data'] ) && $user_id ) {
                hw_update_user_billing_information( $user_id, new \HW\Models\CRM\BillingInformation( $customer ) );
            }

            $post_data = [
                'post_type' => 'quote',
                'post_title' => $customer['first_name'] . ' ' . $customer['last_name'],
                'post_status' => 'publish',
                'meta_input' => [
                    'hw_mode' => $data['mode'],
                    // 0, never null - see QuoteController; SQL NULL hides the
                    // quote from the guest-claim query.
                    'hw_owner_user_id' => $user_id ?: 0,
                    // Snapshot of the original submission; never updated after this.
                    'hw_customer_data' => $customer,
                    'hw_first_name' => $customer['first_name'],
                    'hw_last_name' => $customer['last_name'],
                    'hw_email' => $customer['email'],
                    'hw_phone' => $customer['phone_number'],
                    'hw_address' => $customer['address'] ?? '',
                    'hw_city' => $customer['city'] ?? '',
                    'hw_state' => $customer['state'],
                    'hw_country' => $customer['country'],
                    'hw_zip' => $customer['zip'] ?? '',
                    'hw_package_id' => $data['package_id'] ?: null,
                    'hw_room_block_id' => $data['room_block_id'],
                    'hw_room_id' => $data['room_id'],
                    'hw_adults' => $data['adults'],
                    'hw_teen' => $data['teen'],
                    'hw_children' => $data['children'],
                    'hw_infants' => $data['infants'],
                    'hw_rate_type' => $rate_type,
                    'hw_rates' => $prices[$rate_type['rate']['slug']],
                    'hw_rates_per_day' => $room_quote_totals['rates'],
                    'hw_room_total' => $room_total,
                    'hw_total' => $total,
                    'hw_guests' => $data['guests'],
                    'hw_additional_notes' => trim(($data['bed_preference'] !== '' ? sprintf(__('Bed preference (request): %s', 'hola-weddings'), $data['bed_preference']) . "\n" : '')
                        . (string) $data['additional_notes']),
                    'hw_insurance_id' =>  $data['insurance_id'],
                    'hw_insurance_plan_id' => $data['insurance_plan_id'],
                    'hw_insurance_total' => $insurance_total,
                    'hw_insurance_rates' => $insurance_rates,
                    'hw_toc_accepted' => (new \DateTime('NOW'))->format('Y-m-d H:i:s'),
                    'hw_consent_ip' => function_exists('hw_get_the_user_ip') ? hw_get_the_user_ip() : ($_SERVER['REMOTE_ADDR'] ?? ''),
                    'hw_insurance_declined_at' => !empty($data['insurance_declined']) ? (new \DateTime('NOW'))->format('Y-m-d H:i:s') : '',
                    'hw_insurance_decline_text' => !empty($data['insurance_declined']) ? hw_get_insurance_decline_text() : '',
                    'hw_bed_preference' => $data['bed_preference'],
                    'hw_transfer_choice' => hw_is_transfer_optional() ? (hw_guest_wants_transfer() ? 'yes' : 'no') : '',
                    'hw_extra_checkboxes' => hw_extra_checkbox_answers($data['extra_checkboxes']),
                    'hw_nights' => $nights,
                    'hw_checkin' => $data['ci'],
                    'hw_checkout' =>  $data['co'],
                    'hw_service_fee' => $data['service_fee'],
                    'hw_sales_agent' => '',
                    'hw_admin_agent' => '',
                    'hw_deposit' => $data['deposit'],
                    'hw_card' => $card_info,
                    'hw_iv' => $iv !== null ? bin2hex($iv) : '',
                    'hw_quote_status' => $data['quote_status'],
                    'hw_signature' => $data['signature'],
                    'hw_api_hotel_id' => $hotel->id
                ]
            ];
            
            /**
             * Fires before the quote is created
             *
             * @param array $post_data
             * @param HW\Models\RoomBlock $room_block
             * 
             * @return void
             */
            $post_data = apply_filters('hw_on_before_create_quote', $post_data, $room_block);
            
            $post_id = wp_insert_post($post_data);
            
            if(is_wp_error($post_id)){
                
                $this->add_error('submission', __('There was a problem submitting your request', 'hola-weddings'));
                hw_write_log($post_id);
                $this->redirect_to_cart($data);
            }

            // block the room, for the moment let's keep track of all bookings
            hw_block_room_dates( $room_block, $data['ci'], $data['co'], $post_id);
            
             /**
             * Fires after the quote is created
             *
             * @param array $post_data
             * @param array post meta
             * 
             * @return void
             */
            do_action('hw_after_create_quote', $post_id, $post_data['meta_input'], $room_block );

            $session->set('confirmation_endpoint_booking_id', $post_id);

            if( wp_redirect( hw_get_booking_confirmation_url() ) ){
                exit;
            };
        }
    }
    
    /**
     * VAlidate data
     *
     * @param array $data
     * @return bool
     */
    /**
     * Whether this submission asks for a user account to be created.
     *
     * Account creation is optional: it happens only when a logged-out visitor
     * fills in a password, and never when the site hides that box
     * ( hw_booking_form_hide_login_cta ). Mirrors
     * QuoteController::isCreatingAccount() for the REST booking form.
     *
     * @return bool
     */
    protected function is_creating_account(){

        if( is_user_logged_in() || hw_hide_form_login_cta() ){
            return false;
        }

        return trim( $_POST['password'] ?? '' ) !== ''
            || trim( $_POST['confirm-password'] ?? '' ) !== '';
    }

    public function validate_quote_data(&$data, $room_block){
        
        $hotel = (object)hw_get_hotel();
        $payment_enabled = hw_get_option('hw_enable_payment', false);
        $errors = false;

        // first of all check if selected room block exists
        if(!$room_block->exists()){

            $this->add_error('room', __('Please select a valid room', 'hola-weddings'));
        }
        
        if(hw_get_mode() == 'room'){
            
            if(!$data['ci'] || !(new \DateTime($data['ci']))){
                $this->add_error('checkin', __('Please select a checkin date', 'hola-weddings'));
                $errors = true;
            }
        
            
            if(!$data['co'] || !(new \DateTime($data['co']))){
                $this->add_error('checkout', __('Please select a checkout date', 'hola-weddings'));
                $errors = true;
            }

            $min_nights = hw_get_min_nights();
            $checkin_date = new \DateTime($data['ci']);
            $checkout_date = new \DateTime($data['co']);
            
            if($checkin_date->diff($checkout_date)->days < $min_nights){
                $this->add_error('checkout', sprintf(__('A min. of %d nights is required', 'hola-weddings'), $min_nights));
                $errors = true;
            }
        }
        
        if(!$data['adults'] || $data['adults'] == 0 ){
            $this->add_error('adults', __('Please select adults number', 'hola-weddings'));
            $errors = true;
        }
        
        $n_guests = $data['adults'] + $data['children'] + $data['infants'];
        
        if(hw_is_teen_enabled() && is_numeric($data['teen'])){
            
            $n_guests += $data['teen'];
        }
        
        for($i=1; $i <= $n_guests; $i++){
            
            if( empty($data['guests'][$i]['name']) ){
                $this->add_error('guest-name-' . $i , sprintf(__('Please insert guest #%s name'), $i ));
                $errors = true;
            }

            if( empty($data['guests'][$i]['lastname']) ){
                $this->add_error('guest-name-' . $i , sprintf(__('Please insert guest #%s name'), $i ));
                $errors = true;
            }

            $date = $data['guests'][$i]['date'];

            // check validity
            if( !checkdate($date['m'], $date['d'], $date['y']) ){

                $this->add_error('guest-date-' . $i, sprintf(__('Invalid guest #%s date of birth', 'hola-weddings'), $i ));
                $errors = true;
            }

            $date = AgeSelector::format_data_to_date($data['guests'][$i]['date']);
            $dateObj = new \DateTime($date);
            $checkoutDateObj = new \DateTime($data['co']);
            $age = hw_calc_guest_age($dateObj, $checkoutDateObj);
            $person_type = PersonTypeManager::getInstance()->get($data['guests'][$i]['person_type']);
            $min_age = $person_type->getMinAge();
            $max_age = $person_type->getMaxAge();


            if($i == 1){
                $min_age = 18;
            }
            
            if($hotel->adults_only && $age < 18){
                $this->add_error('guest-date-' . $i, sprintf(__('Guest #%s must be at least 18 years old at time of travel.', 'hola-weddings'), $i ));
                $errors = true;
            }

            if($min_age && $age < $min_age){
                $this->add_error('guest-date-' . $i, sprintf(__('Guest #%s must be at least %d years old at time of travel.', 'hola-weddings'), $i, $min_age ));
                $errors = true;
            }

            if($max_age && $age > $max_age){
                $this->add_error('guest-date-' . $i, sprintf(__('Guest #%s maximum age must be of %d years old at time of travel.', 'hola-weddings'), $i, $max_age ));
                $errors = true;
            }
        }
        
        /**
         * Additional notes
         */
        if(hw_is_additional_notes_enabled() && hw_is_additional_notes_is_required() && empty(trim($data['additional_notes']))){

            $this->add_error('additional_notes', sprintf(__('Please insert %s', 'hola-weddings'), hw_get_additional_notes_label()));
            $errors = true;
        }

        /*
         * Customer
         */ 
        if(empty($data['customer']['first_name'])){
            $this->add_error('first_name', __('Please insert first name', 'hola-weddings'));
            $errors = true;
        }
        
        if(empty($data['customer']['last_name'])){
            $this->add_error('last_name', __('Please insert last name', 'hola-weddings'));
            $errors = true;
        }
        
        if(empty($data['customer']['phone_number'])){
            $this->add_error('phone', __('Please insert phone number', 'hola-weddings'));
            $errors = true;
        }
        
        if(empty($data['customer']['email']) || !filter_var( $data['customer']['email'] ,FILTER_VALIDATE_EMAIL)){
            $this->add_error('email', __('Please insert a valid email', 'hola-weddings'));
            $errors = true;
        }

        if(empty($data['customer']['address'])){
            $this->add_error('address', __('Please insert your address', 'hola-weddings'));
            $errors = true;
        }

        if(empty($data['customer']['city'])){
            $this->add_error('city', __('Please insert your City', 'hola-weddings'));
            $errors = true;
        }

        if(empty($data['customer']['zip'])){
            $this->add_error('zip', __('Please insert your ZIP code', 'hola-weddings'));
            $errors = true;
        }
        
        if(empty($data['customer']['state'])){
            $this->add_error('state', __('Please insert your State/Province', 'hola-weddings'));
            $errors = true;
        }

        if(empty($data['customer']['country'])){
            $this->add_error('country', __('Please insert your Country', 'hola-weddings'));
            $errors = true;
        }

        // the plan is not part of $data yet, it is resolved later from the raw POST
        if(($data['customer']['country'] ?? '') == 'US' && !hw_is_insurance_plan_allowed(hw_get_insurance_id(), $_POST['insurance_plan'] ?? 0, $data['customer']['state'] ?? '')){
            $this->add_error('state', __('The selected insurance plan is not available in your state. Please choose a different plan.', 'hola-weddings'));
            $errors = true;
        }

        // ── Optional account creation ─────────────────────────────────────────
        // Opt-in, matching the REST booking form: leaving both password fields
        // blank books as a guest and creates no WordPress user. Once one of them
        // is typed into, the whole set has to be coherent, so a half-filled pair
        // still fails loudly rather than silently dropping the account.
        if( $this->is_creating_account() ){

            $password     = trim( $_POST['password'] ?? '' );
            $confirmation = trim( $_POST['confirm-password'] ?? '' );
            $account_email = sanitize_email( $data['customer']['email'] ?? '' );

            if( strlen($password) < 8 ){
                $this->add_error('password', __('Password must be at least 8 characters.', 'hola-weddings'));
                $errors = true;
            }

            if( $confirmation !== $password ){
                $this->add_error('confirm-password', __('Passwords do not match.', 'hola-weddings'));
                $errors = true;
            }

            // Stop rather than adopting the existing account: typing an address
            // proves nothing about owning it. Nothing has been stored or charged
            // yet, so they can sign in and resubmit, or book as a guest.
            if( $account_email && email_exists( $account_email ) ){
                $this->add_error('email', __('An account already exists for this email address. Please sign in first, or book without creating an account.', 'hola-weddings'));
                $errors = true;
            }
        }
        
        /*
         * Payment
         * Unavailable rooms used to skip card validation entirely (card-less
         * on-request). They now submit a card AUTHORIZATION (stored, charged on
         * TO confirm) — so whenever card fields were posted, validate them.
         */
        if( ($payment_enabled && !hw_is_inventory_enabled()) ||
            ($payment_enabled && $room_block->isAvailable( $data['ci'], $data['co'])) ||
            ($payment_enabled && !empty($data['payment']['card-number'])) ){
            
            // Card owner
            if(empty($data['payment']['card-owner'])){
                $this->add_error('card-owner', __('Please insert the card owner name', 'hola-weddings'));
                $errors = true;
            }

            // Card type
            if(empty($data['payment']['card-type']) || !in_array(strtolower($data['payment']['card-type']), ['visa', 'mc', 'amex'])){
                $this->add_error('card-owner', __('Please choose a valid card type', 'hola-weddings'));
                $errors = true;
            }
            
            // Card number
            if(empty($data['payment']['card-number'])){
                
                $this->add_error('card-number', __('Please insert the card number', 'hola-weddings'));
                $errors = true;
            }else{
                $data['payment']['card-number'] = str_replace('-', '', $data['payment']['card-number']);
                
                $card_length = $data['payment']['card-type'] == 'amex' ? 15 : 16;
                if(strlen($data['payment']['card-number']) != $card_length){
                    
                    $this->add_error('card-number', __('The card number must have ' . $card_length . ' numbers', 'hola-weddings'));
                    $errors = true;
                }
            }
            
            // Card month
            if(empty($data['payment']['card-month'])){
                $this->add_error('card-month', __('Please insert the card expiration month', 'hola-weddings'));
                $errors = true;
            }else{
                
                // check if the month is in the range 1-12
                
                if( intval($data['payment']['card-month']) < 1 || intval($data['payment']['card-month']) > 12){
                    
                    $this->add_error('card-month', __('Invalid expiration month', 'hola-weddings'));
                    $errors = true;
                }
                
                // if current year check if the chosen month is at least the current
                if(intval($data['payment']['card-year']) == intval(date("Y")) && intval($data['payment']['card-month']) < intval(date("n"))){
                    
                    $this->add_error('card-month', __('Invalid expiration month', 'hola-weddings'));
                    $errors = true;
                }
            }
            
            // Card year
            if(empty($data['payment']['card-year'])){
                $this->add_error('card-year', __('Please insert the card expiration year', 'hola-weddings'));
                $errors = true;
            }else{
                
                if( intval($data['payment']['card-year']) < intval(date("Y"))){
                    
                    $this->add_error('card-year', __('Invalid expiration year', 'hola-weddings'));
                    $errors = true;
                }
            }
            
            // CCV
            if(empty($data['payment']['card-ccv'])){
                $this->add_error('card-ccv', __('Please insert the card CCV', 'hola-weddings'));
                $errors = true;
            }else{
                
                $ccv_length = $data['payment']['card-type'] == 'amex' ? 4 : 3;
                $ccv_max = $data['payment']['card-type'] == 'amex' ? 10000 : 1000;

                if( intval($data['payment']['card-ccv']) < 0 || intval($data['payment']['card-ccv']) > $ccv_max
                   || strlen($data['payment']['card-ccv']) > $ccv_length ){
                    
                    $this->add_error('card-ccv', __('Invalid card CCV', 'hola-weddings'));
                    $errors = true;
                }
            }

            // signature
            if(empty($data['signature'])){

                $this->add_error('signature', __('Signature is required', 'hola-weddings'));
                $errors = true;
            }
        }
        
        if(empty($data['toc']) || $data['toc'] != 1){
            $this->add_error('toc', __('Please accept the terms and conditions', 'hola-weddings'));
            $errors = true;
        }

        if(($data['insurance_declined'] ?? 0) === -1){

            $this->add_error('insurance_decline', __('Please choose a travel protection plan, or confirm that you decline it.', 'hola-weddings'));
            $errors = true;
        }

        // configurable extra checkboxes: required ones must be ticked
        foreach(hw_get_extra_checkboxes() as $cb){

            if($cb['required'] && empty($data['extra_checkboxes'][$cb['index']])){

                $this->add_error('extra_cb_' . $cb['index'],
                    sprintf(__('Please confirm: %s', 'hola-weddings'), $cb['label']));
                $errors = true;
            }
        }
        
        if($errors){
            return false;
        }
        
        return true;
    }

    /**
     * validate the deposit, this is on a separate function to allow calculate totals, in case the deposit type is dynamic
     *
     * @param array $totals
     * @return bool
     */
    protected function validate_deposit($data, $totals){

        if(!is_payment_enabled()){

            return true;
        }

        // Deposit
        if(empty($data['deposit']['amount']) || !in_array($data['deposit']['amount'], ['deposit', 'full', 'custom'])){
                
            $this->add_error('deposit-amount', __('Please choose valid deposit option', 'hola-weddings'));
            $errors = true;
        }else{

            // always set min deposit, can't be zero
            $min_deposit = hw_get_min_deposit();

            // if dynamic, let's calculate it
            if( hw_get_deposit_type() == 'dynamic'){

                $min_deposit = ( reset($totals['total_per_day']) ?: $min_deposit ) * hw_get_deposit_nights_multiplier();
            }

            if($data['deposit']['custom_amount'] < $min_deposit){

                $this->add_error('deposit-custom-amount', __('Insert valid custom payment amount', 'hola-weddings'));
                $errors = true;
            }       
        }

        // Deposit charge acceptance
        if(empty($data['deposit']['accept_charge']) || $data['deposit']['accept_charge'] != 1){

            $this->add_error('deposit-acceptance', __('Please read and accept deposit charge disclaimer', 'hola-weddings'));
            $errors = true;
        }

        if($errors){
            return false;
        }

        return true;
    }

    /**
     * process submitted customer data
     *
     * @param array $data
     * @return void
     */
    protected function process_customer_data(&$data){

        $payment_enabled = hw_get_option('hw_enable_payment', false);
        $n_guests = $data['adults'] + $data['children'] + $data['infants'];
        
        if(hw_is_teen_enabled() && is_numeric($data['teen'])){
            
            $n_guests += $data['teen'];
        }

        for($i=1; $i <= $n_guests; $i++){
            
            $data['guests'][$i]['date'] = AgeSelector::format_data_to_date($data['guests'][$i]['date']);
            $dateObj = new \DateTime($data['guests'][$i]['date']);
            $checkoutDateObj = new \DateTime($data['co']);
            $data['guests'][$i]['age'] = hw_calc_guest_age( $dateObj, $checkoutDateObj);
        }

        $data['deposit']['ip_address'] = hw_get_the_user_ip();
        $data['deposit']['accepted_on'] = time();
    }
    
    /**
     * add errors to session
     *
     * @param string $key
     * @param string $text
     * @return void
     */
    protected function add_error($key, $text){
        
        $session = hw_get_session();
        $errors = $session->get( 'quote_errors', array() );
    
        if ( ! empty( $text ) ) {
            $errors[$key] = $text;
        }
    
        $session->set( 'quote_errors', $errors );
    }
    
    /**
     * send email confirmation for a new quote to admin
     *
     * $post_meta is whatever the hw_after_create_quote hook happened to pass and
     * is ignored: the quote is re-read through the model instead, so a resend
     * (see `wp hw test-quote-email`) renders exactly the same message as the
     * original, and so fields the hook never carried - the resolved insurance
     * plan, the room behind the room block - are actually present.
     *
     * @param int $post_id
     * @param array $post_meta Deprecated, kept for hook compatibility.
     * @return void
     */
    public function send_email_to_admin( $post_id, $post_meta = [] ){

        $quote = hw_get_quote( $post_id );

        if( ! $quote->exists() ){

            hw_write_log( sprintf( 'Cannot send the new quote email: quote %s not found', $post_id ) );
            return;
        }

        $result = ( new EmailSender() )->send( new NewQuoteAdminNotification( $quote ) );

        if( is_wp_error( $result ) || ! $result ){

            hw_write_log( 'There was a problem sending out the quote email' );
        }
    }

    /**
     * return card information
     *
     * @param int $id
     * @return bool|array
     */
    public static function get_card_information($id = null){

        if(empty($id)){
            $id = get_the_ID();
        }

        $iv = get_post_meta($id, 'hw_iv', true);
        $encrypted_card = get_post_meta($id, 'hw_card', true);

        if($encrypted_card){

            if (function_exists('hw_card_decrypt')) {

                $map = hw_card_decrypt($encrypted_card, (string) $iv, 'Quotes::get_card_information #' . $id);
                return $map === null ? false : $map;
            }
            return json_decode(openssl_decrypt ( $encrypted_card, self::$cipher, self::$key, $options=0, hex2bin($iv)), true);
        }

        return false;
    }

    /**
     * Handle admin actions
     *
     * @return void
     */
    public function handle_admin_actions(){

        global $pagenow;

        if(isset($_REQUEST['hw_action'])){

            switch($_REQUEST['hw_action']){
                case 'hw_quote_pdf':
                    if($pagenow == 'post.php' && get_post_type( $_GET['post'] ) == 'quote'){
                        
                        $quotePdf = new \HW\PDF\AdminQuote($_GET['post']);
                        $quotePdf->output('Reservation_#'.$_GET['post'].'.pdf');
                        die;
                    }
                    break;
            }
        }
    }
}