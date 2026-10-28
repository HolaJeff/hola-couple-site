<?php

namespace HW;

use HW\API\Operations\HotelRooms;
use HW\Admin\Settings;
use HW\Email\EmailSender;
use HW\Email\Templates\RequestInvoicePaymentLink;
use HW\Models\RoomBlock;

class Ajax{
    
    use Traits\Singleton;
    
    public function __construct(){
        
        add_action( 'wp_ajax_get_hotel_rooms', [$this, 'get_hotel_rooms'] );
        
        add_action( 'wp_ajax_get_room_overview', [$this, 'get_room_overview'] );
        add_action( 'wp_ajax_nopriv_get_room_overview', [$this, 'get_room_overview'] );
        
        add_action( 'wp_ajax_get_package_overview', [$this, 'get_package_overview'] );
        add_action( 'wp_ajax_nopriv_get_package_overview', [$this, 'get_package_overview'] );

        add_action( 'wp_ajax_hw_update_hotel', [ $this, 'update_hotel' ] );

        add_action( 'wp_ajax_hw_import_room', [ $this, 'import_room' ] );

        add_action( 'wp_ajax_nopriv_ajax_login' , [ $this, 'login']);
        add_action( 'wp_ajax_ajax_login' , [ $this, 'login']);
        
        add_action( 'wp_ajax_nopriv_hw_cart_save_form_data_into_session', [ $this, 'store_form_data_session']);
        add_action( 'wp_ajax_hw_cart_save_form_data_into_session', [ $this, 'store_form_data_session']);

        add_action( 'wp_ajax_nopriv_hw_reset_password', [ $this, 'reset_password'] );

        // cache
        add_action( 'wp_ajax_hw_refresh_hotel_cache', [ $this, 'refresh_hotel_cache' ] );
        add_action( 'wp_ajax_hw_clear_rooms_cache', [ $this, 'clear_rooms_cache' ]);

        // account
        add_action( 'wp_ajax_hw_send_email_request_invoice_payment_link', [ $this, 'send_email_request_invoice_payment_link' ]);

        // customer
        add_action( 'wp_ajax_hw_update_quote_customer', [ $this, 'update_quote_customer' ] );
    }

    /**
     * handle clear ajax call
     *
     * @return void
     */
    public function refresh_hotel_cache(){

        if(!current_user_can('administrator')){

            wp_send_json(['success' => false, 'message' => 'Not authorized']);
        }

        hw_update_hotel_transient();

        wp_send_json(['success' => true]);
    }

    /**
     * clear rooms cache
     *
     * @return json
     */
    public function clear_rooms_cache(){

        if(!current_user_can('administrator')){

            wp_send_json(['success' => false, 'message' => 'Not authorized']);
        }

        $cache = Cache::getInstance();

        $cache->clear_network('api_room_');

        wp_send_json(['success' => true]);
    }

    /**
     * handle reset password
     *
     * @return json
     */
    public function reset_password(){

        if(!isset($_POST['email']) || empty(trim($_POST['email'])) || filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL) == false){
            
            wp_send_json(['success' => false, 'message' => __('Invalid email address', 'hola-weddings')]);
        }

        $email = trim($_POST['email']);

        $errors = retrieve_password($email);

        if ( is_wp_error( $errors ) ) {
            
            wp_send_json(['success' => false, 'message' => $errors->get_error_message()]);
        }

        wp_send_json(['success' => true, 'message' => __('The reset link was sent to your email.', 'hola-weddings')]);
    }

    /**
     * store data in session
     *
     * @return void
     */
    public function store_form_data_session(){

        $session = hw_get_session();

        $data['guests'] = $_POST['guests'];
        $data['customer'] = $_POST['customer'];
        $data['payment'] = $_POST['payment'];
        $data['deposit'] = $_POST['deposit'];
        $data['insurance_plan_id'] = $_POST['insurance_plan'];
        $data['toc'] = $_POST['terms_and_conditions'];
        $data['room_id'] = $_POST['room_id'];

        $session->set('quote_data', $data);

        wp_send_json(['success' => true]);
    }

    /**
     * handle ajax login
     *
     * @return json
     */
    public function login(){

        if (empty(hw_get_the_user_ip())) {
            die();
        }

        // add this for the new booking form testing purpose
        if( is_user_logged_in() && !current_user_can( 'administrator' ) ){
            wp_send_json( ['errorMessage' => 'Unauthorized request'] , 403 );
        }

        $status_code = 200;
        $response = array(
            'isLoggedIn' => false,
            'errorMessage' => '',
        );

        if (empty($username = sanitize_text_field($_POST['username']))) {

            $response['errorMessage'] = __( 'No user name', 'hola-weddings') ;
        } elseif (empty($password = sanitize_text_field($_POST['password']))) {

            $response['errorMessage'] = 'No password';
        } elseif (!is_a($user = wp_authenticate($username, $password), 'WP_User')) {

            $response['errorMessage'] = 'Invalid login';
        } else {

            // Logged in OK.
            $response['isLoggedIn'] = true;

            // We need to do this so wp_send_json() can return the cookie in
            // our response.
            wp_set_auth_cookie(
               $user->ID,
               False// << This is the "remember me" option.
            );

            // wp_create_nonce() below reads the current user, which the cookie
            // alone does not set until the next request.
            wp_set_current_user( $user->ID );

            // A network user signing in on a wedding site they have never
            // visited would otherwise land with no capabilities there.
            hw_add_user_to_current_blog( $user->ID );

            // Lets the auth modal show the verification step in place rather
            // than bouncing the visitor to /account/verify-email/.
            $response['verification'] = hw_get_verification_payload( $user->ID );
        }
        
        if (!empty($response['errorMessage'])) {
            
            error_log($response['errorMessage']);
        }
         
        wp_send_json( $response, $status_code );
    }
    
    public function get_hotel_rooms(){
        
        $id = $_POST['hotel_id'];
        
        if(empty($id)){
            
            return wp_send_json(['success' => false, 'message' => 'Provide hotel id']);
        }
        
        $rooms = HotelRooms::get($id);
        
        if( is_wp_error($rooms) ){

            return wp_send_json(['success' => false, 'message' => $rooms->get_error_message()]);
        }
        
        ob_start();
        
        hw_get_template('templates/admin/settings/common/rooms', [ 'rooms' => $rooms, 'form' => (new Formr('bootstrap', false )) ] );
        
        return wp_send_json(['success' => true, 'data' => ob_get_clean()]);
        
    }
    
    public function get_room_overview(){
        
        $id = $_GET['room_id'];
        
        if(empty($id)){
            
            return wp_send_json(['success' => false, 'message' => 'No Room Selected']);
        }
        
        $room_block = hw_get_room_block($id);
        $room = (object)$room_block->getRoom();
        $person_types = get_person_types();
        $rate_types = $room->occupancyrules;
        $prices = $room_block->getOccupancyRules();
        ob_start();
        if(!empty($room)): ?>
        <div class="room-overview">
            <h4 class="title"><?= $room->name ?></h4>
            <div class="description"><?= $room->description ?></div>

                <div class="rates">
                    <table class="w-100 table table-bordered">
						<thead>
							<tr>
								<td></td>
								<?php foreach($person_types as $type):?>
									<td><?= $type ?></td>
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
            </div>
        </div>
        <?php else: ?>
            <p class="alert alert-warning"><?=  __('An error ocurred while fetching room data', 'hola-weddings'); ?></p>
        <?php endif;
        return wp_send_json(['success' => true, 'data' => ob_get_clean() ]);
    }
    
    public function get_package_overview(){
        
        $id = $_GET['package_id'];
        
        if(empty($id)){
            
            return wp_send_json(['success' => false, 'message' => 'No Package Id']);
        }
        
        $package = get_post($id);
        $room = (object)hw_get_room(get_field('hw_room'), $package->ID);
        $rooms_data = hw_get_rooms_data();
        $person_types = get_person_types();
       // $rate_types = get_rate_types();
        
        $description = get_the_content(null, false, $package->ID);
        
        ob_start();
        ?>
        <div class="package-overview">
            
            <div class="overview mb-5">
                <h4 class="title">About the package</h4>
                <div class="dates">
                    <strong><?= __('From:', 'hola-weddings') ?></strong>
                    <span class="dates"><?= (new \DateTime(get_field('from', $package->ID)))->format('F, j Y') ?></span>
                    -
                    <strong><?= __('To:', 'hola-weddings') ?></strong>
                    <span class="dates"><?= (new \DateTime(get_field('to', $package->ID)))->format('F, j Y') ?></span>
                </div>
                
                <?php if($description): ?>
                    <div class="description mt-3"><?= $description ?></div>
                <?php endif; ?>
                
            </div>
            
            <h4 class="title"><?= __('Flights Information', 'hola-weddings') ?></h4>
            
            <?= hw_get_template('template-parts/common/flights', ['id' => $package->ID]) ?>
            
        </div>
        <?php
        return wp_send_json(['success' => true, 'data' => ob_get_clean() ]);
    }

    /**
     * Handle ajax request to update hotel
     *
     * @return json
     */
    public function update_hotel(){

        hw_update_hotel_transient();

        return wp_send_json(['success' => true]);
    }

    /**
     * handle settings room import
     *
     * @return void
     */
    public function import_room(){

        $room_id = $_POST['room_id'];

        if(empty($room_id)){
            wp_send_json(['success' => false, 'message' => 'Missing room id' ]);
        }

        // check if a room block exists with the same room id
        $room_block = RoomBlock::getByRoomID($room_id);
        if($room_block instanceof RoomBlock){
            wp_send_json(['success' => false, 'message' => 'Room Block already exists for this room' ]);
        }

        $room = hw_get_room($room_id);

        $post_data = [
            'post_type' => 'room_blocks',
            'post_title' => $room['name'],
            'post_status' => 'publish',
            'meta_input' => [
                'hw_room_id' => $room['id'],
            ]
        ];

        $room_block_id = wp_insert_post($post_data);

        // if is wp error
        if(is_wp_error($room_block_id)){
            wp_send_json(['success' => false, 'message' => $room_block_id->get_error_message() ]);
        }

        // return the edit link
        wp_send_json(['success' => true, 'editLink' => get_edit_post_link($room_block_id) ]);
    }

    /**
     * handle the send email request for invoice payment link
     *
     * @return json
     */
    public function send_email_request_invoice_payment_link(){

        $invoice_id = isset( $_POST['invoice_id'] ) ? absint( $_POST['invoice_id'] ) : 0;

        if(empty($invoice_id)){

            wp_send_json(['success' => false, 'message' => __('Request failed, please contact our agents.', 'hola-weddings'), 'debug_message' => 'Missing invoice id' ]);
        }

        // This mails a payment link for whichever invoice id it is handed, so it
        // needs all three of these. Without them any signed-in customer could
        // walk the id space and have links sent for other people's invoices.

        // 1. The request came from a page we rendered.
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'hw_request_invoice_link_' . $invoice_id ) ) {

            wp_send_json([ 'success' => false, 'message' => __('Your session expired. Please refresh the page and try again.', 'hola-weddings') ], 403);
        }

        // 2. Somebody is asking.
        if ( ! is_user_logged_in() ) {

            wp_send_json([ 'success' => false, 'message' => __('Please sign in and try again.', 'hola-weddings') ], 403);
        }

        $invoice = hw_get_invoice($invoice_id);

        if ( ! $invoice || ! $invoice->exists() ) {

            wp_send_json(['success' => false, 'message' => __('Request failed, please contact our agents.', 'hola-weddings'), 'debug_message' => 'Unknown invoice' ]);
        }

        // 3. It is their invoice — or they are staff. Deliberately the same
        //    ownership test the account area uses for a reservation.
        if ( ! hw_user_has_role( 'administrator' ) && (int) $invoice->getOwner() !== get_current_user_id() ) {

            wp_send_json([ 'success' => false, 'message' => __('Not authorized.', 'hola-weddings') ], 403);
        }

        $template = new RequestInvoicePaymentLink($invoice);
        $sender = new EmailSender();

        // expect mail response true or false
        if(!$sender->send($template)){

            wp_send_json(['success' => false, 'message' => __('Request failed, please contact our agents.', 'hola-weddings'), 'debug_message' => 'Error sending email, check log.' ]);
        }

        // return the edit link
        wp_send_json(['success' => true, 'message' => __('Request sent successfully.') ]);
    }

    /**
     * Update the customer billing information linked to a quote.
     * Optionally reassigns the quote to a different customer via customer_id.
     * Admin-only, logged-in users only (wp_ajax_ prefix enforces this).
     *
     * @return void
     */
    public function update_quote_customer(): void {

        check_ajax_referer( 'hw_update_quote_customer', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( [ 'message' => __( 'Not authorized.', 'hola-weddings' ) ], 403 );
        }

        $quote_id = intval( $_POST['quote_id'] ?? 0 );

        if ( ! $quote_id ) {
            wp_send_json_error( [ 'message' => __( 'Invalid quote.', 'hola-weddings' ) ] );
        }

        $raw     = $_POST['data']['billing'] ?? [];
        $billing = new \HW\Models\CRM\BillingInformation( [
            'first_name'   => sanitize_text_field( $raw['first_name']   ?? '' ),
            'last_name'    => sanitize_text_field( $raw['last_name']    ?? '' ),
            'email'        => sanitize_email(      $raw['email']        ?? '' ),
            'phone_number' => sanitize_text_field( $raw['phone_number'] ?? '' ),
            'address'      => sanitize_text_field( $raw['address']      ?? '' ),
            'city'         => sanitize_text_field( $raw['city']         ?? '' ),
            'zip'          => sanitize_text_field( $raw['zip']          ?? '' ),
            'country'      => sanitize_text_field( $raw['country']      ?? '' ),
            'state'        => sanitize_text_field( $raw['state']        ?? '' ),
        ] );

        // Billing meta fields only. hw_customer_data records what was submitted
        // when the booking was made and is deliberately left as it was.
        hw_get_quote( $quote_id )->setBillingInformation( $billing );

        wp_send_json_success( [ 'message' => __( 'Billing information updated.', 'hola-weddings' ) ] );
    }
}