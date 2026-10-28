<?php

namespace HW\RestApi;

use HW\Traits\Singleton;

class BookingEndpoints {

    use Singleton;

    const NAMESPACE = 'hw/v1';

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes() {

        register_rest_route( self::NAMESPACE, '/rooms', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_rooms' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'checkin'  => [ 'required' => true,  'sanitize_callback' => 'sanitize_text_field' ],
                'checkout' => [ 'required' => true,  'sanitize_callback' => 'sanitize_text_field' ],
                'adults'   => [ 'required' => false, 'default' => 2, 'sanitize_callback' => 'absint' ],
                'teens'    => [ 'required' => false, 'default' => 0, 'sanitize_callback' => 'absint' ],
                'children' => [ 'required' => false, 'default' => 0, 'sanitize_callback' => 'absint' ],
                'infants'  => [ 'required' => false, 'default' => 0, 'sanitize_callback' => 'absint' ],
            ],
        ] );

        register_rest_route( self::NAMESPACE, '/rooms/(?P<id>\d+)/details', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_room_details' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'id' => [ 'required' => true, 'sanitize_callback' => 'absint' ],
            ],
        ] );

        register_rest_route( self::NAMESPACE, '/packages', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_packages' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'checkin'  => [ 'required' => true,  'sanitize_callback' => 'sanitize_text_field' ],
                'checkout' => [ 'required' => true,  'sanitize_callback' => 'sanitize_text_field' ],
                'adults'   => [ 'required' => false, 'default' => 2, 'sanitize_callback' => 'absint' ],
            ],
        ] );

        register_rest_route( self::NAMESPACE, '/quote', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'submit_quote' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( self::NAMESPACE, '/session', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'save_session' ],
            'permission_callback' => '__return_true',
        ] );
    }

    /**
     * GET /hw/v1/rooms
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_rooms( \WP_REST_Request $request ) {
        $data = [
            'checkin'  => $request->get_param( 'checkin' ),
            'checkout' => $request->get_param( 'checkout' ),
            'ci'  => $request->get_param( 'checkin' ), // legacy
            'co' => $request->get_param( 'checkout' ), // legacy
            'adults'   => $request->get_param( 'adults' ),
            'teens'    => $request->get_param( 'teens' ),
            'children' => $request->get_param( 'children' ),
            'infants'  => $request->get_param( 'infants' ),
        ];

        $room_blocks = hw_get_cart_rooms( $data );
        $result      = [];

        foreach ( $room_blocks as $room_block ) {
            $room     = $room_block->getRoom();
            $result[] = [
                'id'          => $room_block->getID(),
                // Translated with TranslatePress on the way out, see the
                // rest_pre_echo_response hook in hw-rest-hooks.php.
                'name'        => $room_block->post_title,
                'description' => $room['description'] ?? null,
                'image'       => $room['main_image'] ?? null,
                'prices'      => $room_block->prices,
                'available'   => (bool) $room_block->isAvailable( $data['checkin'], $data['checkout'] ),
            ];
        }

        return rest_ensure_response( $result );
    }

    /**
     * GET /hw/v1/rooms/{id}/details
     *
     * Returns the rate table data for the room details modal:
     *   person_types  { slug: label }  — columns, server-controlled (respects teen/child2 settings)
     *   rate_types    [ { name, slug } ] — rows
     *   prices        { rate_slug: { person_slug: price|'' } }
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_room_details( \WP_REST_Request $request ) {
        $id         = (int) $request->get_param( 'id' );
        $room_block = hw_get_room_block( $id );

        if ( ! $room_block || ! $room_block->post ) {
            return new \WP_Error( 'not_found', 'Room not found', [ 'status' => 404 ] );
        }

        $room         = (object) $room_block->getRoom();
        $person_types = get_person_types();
        $rate_types   = $room->occupancyrules ?? [];
        $prices       = $room_block->getOccupancyRules() ?? [];

        return rest_ensure_response( [
            'person_types' => $person_types,
            'rate_types'   => array_values( $rate_types ),
            'prices'       => $prices,
        ] );
    }

    /**
     * GET /hw/v1/packages
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_packages( \WP_REST_Request $request ) {
        // TODO: port logic from package query
        return rest_ensure_response( [] );
    }

    /**
     * POST /hw/v1/quote
     *
     * Expected payload fields:
     *   checkin, checkout, adults, teens, children, infants  — search params
     *   roomBlockId      int     — post ID of the room block
     *   packageId        int|null
     *   insurancePlanId  int     — 0 = no insurance
     *   notes            string
     *   customer         { firstName, lastName, email, phone, address, city, state, country, zip }
     *   guests           [ { title, name, lastname, personType, dob: { day, month, year } } ]
     *   payment          { cardType, cardOwner, cardNumber, cardMonth, cardYear, cvv,
     *                      depositType, depositAmount|null, acceptCharge }
     *   termsAccepted    bool
     *   signature        string|null
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function submit_quote( \WP_REST_Request $request ) {
        $body = $request->get_json_params() ?? [];

        if ( ! empty( $body['_test'] ) && current_user_can( 'administrator' ) ) {
            return $this->handle_test_submission( $body );
        }

        $controller = new \HW\Booking\QuoteController();
        $result     = $controller->process( $body );

        if ( ! $result['success'] ) {
            return new \WP_Error(
                'quote_failed',
                $result['message'] ?? 'Submission failed.',
                [ 'status' => 422, 'errors' => $result['errors'] ?? [] ]
            );
        }

        return rest_ensure_response( [
            'success'          => true,
            'quote_id'         => $result['quote_id'],
            'message'          => 'Quote submitted successfully.',
            'confirmation_url' => hw_get_booking_confirmation_url(),
        ] );
    }

    /**
     * Return a test response without creating a real booking.
     * Only active for administrators when the request carries _test = true.
     *
     * @param array $body Decoded JSON body from the request.
     * @return \WP_REST_Response|\WP_Error
     */
    private function handle_test_submission( array $body ) {
        $outcome  = $body['_test_outcome'] ?? 'success';
        $received = $body;
        unset( $received['_test'], $received['_test_outcome'] );

        if ( $outcome === 'error' ) {
            return new \WP_Error(
                'test_error',
                'Simulated server error',
                [ 'status' => 422, 'received' => $received ]
            );
        }

        return rest_ensure_response( [
            'success'  => true,
            'test'     => true,
            'message'  => 'Test submission accepted.',
            'quote_id' => 0,
            'received' => $received,
        ] );
    }

    /**
     * POST /hw/v1/session
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function save_session( \WP_REST_Request $request ) {
        // TODO: port logic from Ajax::store_form_data_session()
        return rest_ensure_response( [ 'success' => false, 'message' => 'Not implemented' ] );
    }
}
