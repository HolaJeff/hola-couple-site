<?php

namespace HW\Booking;

use HW\API\Operations\Insurance;
use HW\Booking\Managers\PersonTypeManager;
use HW\Enums\QuoteStatus;
use HW\Fields\AgeSelector;

class QuoteController {

    private const KEY    = 'j|(aoMy3"h-W?Z|fZ]R(*MxtrLqFDK';
    private const CIPHER = 'aes-256-ctr';

    private array $errors = [];

    // ── Public entry point ────────────────────────────────────────────────────

    /**
     * Process a REST API quote submission.
     *
     * @param array $body Decoded JSON request body (camelCase from React)
     * @return array { success: bool, quote_id: int|null, errors: array, message: string }
     */
    public function process( array $body ): array {

        $session = hw_get_session();
        $this->errors = [];

        $data = $this->normalize( $body );

        $room_block = hw_get_room_block( $data['room_block_id'] );

        if ( ! $this->validate( $data, $room_block ) ) {
            return $this->fail( 'Please review your submission and try again.' );
        }

        $user_id = $this->resolveUser( $data );

        if ( is_wp_error( $user_id ) ) {
            $this->addError( 'user', $user_id->get_error_message() );
            return $this->fail( 'Could not resolve user account.' );
        }

        $this->processCustomerData( $data );

        // ── Pricing ───────────────────────────────────────────────────────────

        $room    = $room_block->getRoom();
        $room_id = $data['room_id'] = $room['id'];
        $prices  = $room_block->getOccupancyRules();

        $rate_type = hw_determine_room_rate_type( $room_id, $data['adults'], $data['children'], $data['teen'] );

        if ( ! is_array( $rate_type ) ) {
            $this->addError( 'rate_type', __( 'Could not determine a rate for the selected occupancy.', 'hola-weddings' ) );
            return $this->fail( 'No matching rate type found.' );
        }

        $room_quote_totals = hw_calc_room_quote_total( $room_block, $rate_type, $data );
        $room_total        = $room_quote_totals['total'];

        // ── Transfer ─────────────────────────────────────────────────────────

        $transfer_total  = $this->calculateTransfer( $data );
        $data['service_fee'] = $transfer_total;

        $running_total = $room_total + $transfer_total;

        // ── Insurance ─────────────────────────────────────────────────────────

        $insurance = $this->calculateInsurance( $data, $room_total, $room_block, $rate_type );
        $running_total += $insurance['total'];

        // ── Availability & deposit ────────────────────────────────────────────

        $is_available = $room_block->isAvailable( $data['ci'], $data['co'] );
        $nights       = getNights( $data['ci'], $data['co'] );

        if ( $is_available && is_payment_enabled() && ! $this->validateDeposit( $data, $room_quote_totals, $running_total, $insurance['total'], $nights ) ) {
            return $this->fail( 'Invalid deposit selection.' );
        }

        // ── Status ────────────────────────────────────────────────────────────

        $status    = $this->determineStatus( $is_available, $data );

        // ── Card Encryption ───────────────────────────────────────────────────

        $encrypted = $this->encryptCard( $data['payment'] );

        // ── Saved billing default ─────────────────────────────────────────────
        // Persist the user's saved billing (user meta) only on explicit consent.
        // Identity (email/name) lives on the WP user and is not touched here.

        if ( ! empty( $data['update_customer_data'] ) && $user_id ) {
            hw_update_user_billing_information( $user_id, new \HW\Models\CRM\BillingInformation( $data['customer'] ) );
        }

        // ── Post creation ─────────────────────────────────────────────────────

        $hotel     = (object) hw_get_hotel();

        $meta = [
            // This controller only ever books rooms; packages still go through
            // the legacy form. Recorded rather than inferred from the site's
            // current cart mode, which can be switched after the fact.
            'hw_mode'              => 'room',
            // 0, never null: a null meta_value is stored as SQL NULL, which
            // matches neither NOT EXISTS nor IN ('','0'), making the guest
            // quote invisible to hw_claim_guest_quotes().
            'hw_owner_user_id'     => $user_id ?: 0,
            // Snapshot of exactly what was submitted. Never updated afterwards —
            // the billing fields below are the ones that get edited.
            'hw_customer_data'     => $data['customer'],
            'hw_first_name'        => $data['customer']['first_name'],
            'hw_last_name'         => $data['customer']['last_name'],
            'hw_email'             => $data['customer']['email'],
            'hw_phone'             => $data['customer']['phone_number'],
            'hw_address'           => $data['customer']['address'] ?? '',
            'hw_city'              => $data['customer']['city'] ?? '',
            'hw_state'             => $data['customer']['state'],
            'hw_country'           => $data['customer']['country'],
            'hw_zip'               => $data['customer']['zip'] ?? '',
            'hw_room_block_id'     => $data['room_block_id'],
            'hw_room_id'           => $data['room_id'],
            'hw_adults'            => $data['adults'],
            'hw_teen'              => $data['teen'],
            'hw_children'          => $data['children'],
            'hw_infants'           => $data['infants'],
            'hw_rate_type'         => $rate_type,
            'hw_rates'             => $prices[ $rate_type['rate']['slug'] ] ?? [],
            'hw_rates_per_day'     => $room_quote_totals['rates'],
            'hw_room_total'        => $room_total,
            'hw_total'             => $running_total,
            'hw_guests'            => $data['guests'],
            'hw_additional_notes'  => $data['additional_notes'],
            'hw_insurance_id'      => $data['insurance_id'],
            'hw_insurance_plan_id' => $data['insurance_plan_id'],
            'hw_insurance_total'   => $insurance['total'],
            'hw_insurance_rates'   => $insurance['rates'],
            'hw_toc_accepted'      => ( new \DateTime('NOW') )->format('Y-m-d H:i:s'),
            'hw_nights'            => $nights,
            'hw_checkin'           => $data['ci'],
            'hw_checkout'          => $data['co'],
            'hw_service_fee'       => $data['service_fee'],
            'hw_deposit'           => $data['deposit'],
            'hw_card'              => $encrypted['card'],
            'hw_iv'                => $encrypted['iv'],
            'hw_quote_status'      => $status,
            'hw_signature'         => $data['signature'],
            'hw_api_hotel_id'      => $hotel->id ?? null,
        ];

        /**
         * Fires before the quote is created
         *
         * @param array $post_data
         * @param \HW\Models\RoomBlock $room_block
         * 
         * @return void
         */
        $post_data = apply_filters( 'hw_on_before_create_quote', [
            'post_type'   => 'quote',
            'post_author' => $user_id,
            'post_title'  => $data['customer']['first_name'] . ' ' . $data['customer']['last_name'],
            'post_status' => 'publish',
            'meta_input'  => $meta,
        ], $room_block );

        // ── Insert the new post ──────────────────────────────────────────────────
        $post_id = wp_insert_post( $post_data );

        if ( is_wp_error( $post_id ) ) {
            hw_write_log( $post_id );
            $this->addError( 'submission', __( 'There was a problem submitting your request.', 'hola-weddings' ) );
            return $this->fail( 'Post creation failed.' );
        }

        // block the room, for the moment let's keep track of all bookings
        hw_block_room_dates( $room_block, $data['ci'], $data['co'], $post_id );

        /**
         * Fires after the quote is created
         *
         * @param array $post_id
         * @param array $post_data
         * @param array $room_block
         * 
         * @return void
         */
        do_action( 'hw_after_create_quote', $post_id, $post_data['meta_input'], $room_block );

        $session->set('confirmation_endpoint_booking_id', $post_id);

        return [
            'success'  => true,
            'quote_id' => $post_id,
            'errors'   => [],
            'message'  => 'Quote submitted successfully.',
        ];
    }

    // ── Normalization ─────────────────────────────────────────────────────────

    /**
     * Map the camelCase React JSON payload to the internal snake_case format
     * expected by legacy helpers.
     *
     * @param array $body
     * @return array
     */
    private function normalize( array $body ): array {

        $customer = $body['customer'] ?? [];
        $payment  = $body['payment']  ?? [];

        // Re-index guests from 0-based (React) to 1-based (legacy helpers)
        $guests = [];
        foreach ( array_values( $body['guests'] ?? [] ) as $i => $g ) {
            $dob = $g['dob'] ?? [];
            $guests[ $i + 1 ] = [
                'name'        => sanitize_text_field( $g['name']     ?? '' ),
                'lastname'    => sanitize_text_field( $g['lastname'] ?? '' ),
                'title'       => sanitize_text_field( $g['title']    ?? '' ),
                'person_type' => sanitize_text_field( $g['personType'] ?? 'adult' ),
                'date'        => [
                    'd' => intval( $dob['day'] ),
                    'm' => intval( $dob['month'] ),
                    'y' => intval( $dob['year'] ),
                ],
            ];
        }

        // Deposit — legacy uses deposit.amount / deposit.custom_amount / deposit.accept_charge
        $deposit = [
            'amount'        => sanitize_text_field( $payment['depositType']   ?? '' ),
            'custom_amount' => floatval( $payment['depositAmount'] ?? 0 ),
            'accept_charge' => ! empty( $payment['acceptCharge'] ) ? 1 : 0,
        ];

        // Payment card fields — legacy uses hyphen-separated keys
        $card = [
            'card-owner'  => sanitize_text_field( $payment['cardOwner']  ?? '' ),
            'card-type'   => sanitize_text_field( $payment['cardType']   ?? '' ),
            'card-number' => sanitize_text_field( $payment['cardNumber'] ?? '' ),
            'card-month'  => sanitize_text_field( $payment['cardMonth']  ?? '' ),
            'card-year'   => sanitize_text_field( $payment['cardYear']   ?? '' ),
            'card-ccv'    => sanitize_text_field( $payment['cvv']        ?? '' ),
        ];

        // Normalised customer
        $norm_customer = [
            'first_name'   => sanitize_text_field( $customer['firstName'] ?? '' ),
            'last_name'    => sanitize_text_field( $customer['lastName']  ?? '' ),
            'email'        => sanitize_email(      $customer['email']     ?? '' ),
            'phone_number' => sanitize_text_field( $customer['phone']     ?? '' ),
            'address'      => sanitize_text_field( $customer['address']   ?? '' ),
            'city'         => sanitize_text_field( $customer['city']      ?? '' ),
            'state'        => sanitize_text_field( $customer['state']     ?? '' ),
            'country'      => sanitize_text_field( $customer['country']   ?? '' ),
            'zip'          => sanitize_text_field( $customer['zip']       ?? '' ),
        ];

        $hotel  = (object) hw_get_hotel();

        return [
            'ci'                => sanitize_text_field( $body['checkin']  ?? '' ),
            'co'                => sanitize_text_field( $body['checkout'] ?? '' ),
            'adults'            => absint( $body['adults'] ?? 0 ),
            'teen'              => !$hotel->adults_only && hw_is_teen_enabled() ? absint( $body['teens'] ?? 0 ) : 0,
            'children'          => $hotel->adults_only ? 0 : absint( $body['children'] ?? 0 ),
            'infants'           => $hotel->adults_only ? 0 : absint( $body['infants']  ?? 0 ),
            'room_block_id'     => absint( $body['roomBlockId'] ?? 0 ),
            'additional_notes'  => sanitize_textarea_field( $body['notes'] ?? '' ),
            'account_email'     => sanitize_email( $body['accountEmail'] ?? '' ),
            'password'          => sanitize_text_field( $body['password']     ?? '' ),
            'signature'         => $body['signature'] ?? null,
            'toc'               => ! empty( $body['termsAccepted'] ) ? 1 : 0,
            'customer'          => $norm_customer,
            'guests'            => $guests,
            'payment'           => $card,
            'deposit'           => $deposit,
            'insurance_id'        => null,
            'insurance_plan_id'   => absint( $body['insurancePlanId'] ?? 0 ),
            'update_customer_data'=> ! empty( $body['updateCustomerData'] ),
            'quote_status'        => QuoteStatus::PENDING->value,
        ];
    }

    // ── Validation ────────────────────────────────────────────────────────────

    /**
     * Validate data
     *
     * @param array $data
     * @param \HW\Models\RoomBlock $room_block
     * @return boolean
     */
    private function validate( array $data, \HW\Models\RoomBlock $room_block ): bool {

        $hotel           = (object) hw_get_hotel();
        $payment_enabled = is_payment_enabled();
        $errors          = false;

        // ── Validate room block ──────────────────────────────────────────
        if ( ! $room_block->exists() ) {
            $this->addError( 'room', __( 'Please select a valid room.', 'hola-weddings' ) );
            $errors = true;
        }

        // ── Validate dates ──────────────────────────────────────────
        if ( empty( $data['ci'] ) || !(new \DateTime($data['ci'])) ) {
            $this->addError( 'checkin', __( 'Please select a valid check-in date.', 'hola-weddings' ) );
            $errors = true;
        }

        if ( empty( $data['co'] ) || !(new \DateTime($data['co'])) ) {
            $this->addError( 'checkout', __( 'Please select a valid check-out date.', 'hola-weddings' ) );
            $errors = true;
        }

        $min_nights    = hw_get_min_nights();
        $checkin_date  = new \DateTime( $data['ci'] );
        $checkout_date = new \DateTime( $data['co'] );

        if ( $checkin_date->diff( $checkout_date )->days < $min_nights ) {
            $this->addError( 'checkout', sprintf( __( 'A minimum of %d nights is required.', 'hola-weddings' ), $min_nights ) );
            $errors = true;
        }

        // ── Validate adults ──────────────────────────────────────────
        if ( empty( $data['adults'] ) ) {
            $this->addError( 'adults', __( 'Please select the number of adults.', 'hola-weddings' ) );
            $errors = true;
        }

        // ── Validate Guests ──────────────────────────────────────────

        if ( !empty( $data['guests'] ) ) {

            foreach ( $data['guests'] as $i => $guest ) {

                if ( empty( $guest['name'] ) ) {
                    $this->addError( 'guest-name-' . $i, sprintf( __( 'Please insert guest #%d first name.', 'hola-weddings' ), $i ) );
                    $errors = true;
                }

                if ( empty( $guest['lastname'] ) ) {
                    $this->addError( 'guest-lastname-' . $i, sprintf( __( 'Please insert guest #%d last name.', 'hola-weddings' ), $i ) );
                    $errors = true;
                }

                $date = $guest['date'] ?? [];
                if ( ! checkdate( $date['m'], $date['d'], $date['y'] ) ) {
                    $this->addError( 'guest-date-' . $i, sprintf( __( 'Invalid date of birth for guest #%d.', 'hola-weddings' ), $i ) );
                    $errors = true;
                    continue;
                }

                $date_str      = AgeSelector::format_data_to_date( $date );
                $date_obj      = new \DateTime( $date_str );
                $checkout_obj  = new \DateTime( $data['co'] );
                $age           = hw_calc_guest_age( $date_obj, $checkout_obj );
                $person_type   = PersonTypeManager::getInstance()->get( $guest['person_type'] );
                $min_age       = $person_type ? $person_type->getMinAge() : 18;
                $max_age       = $person_type ? $person_type->getMaxAge() : null;

                if ( $i === 1 ) {
                    $min_age = 18;
                }

                if ( $hotel->adults_only && $age < 18 ) {
                    $this->addError( 'guest-date-' . $i, sprintf( __( 'Guest #%d must be at least 18 years old at time of travel.', 'hola-weddings' ), $i ) );
                    $errors = true;
                }

                if ( $min_age && $age < $min_age ) {
                    $this->addError( 'guest-date-' . $i, sprintf( __( 'Guest #%d must be at least %d years old at time of travel.', 'hola-weddings' ), $i, $min_age ) );
                    $errors = true;
                }

                if ( $max_age && $age > $max_age ) {
                    $this->addError( 'guest-date-' . $i, sprintf( __( 'Guest #%d must be at most %d years old at time of travel.', 'hola-weddings' ), $i, $max_age ) );
                    $errors = true;
                }
            }
        }else{
            // guests is empty
            $this->addError( 'guests', __( 'Guest list is empty, at least one guest should be added.', 'hola-weddings' ) );
            $errors = true;
        }

        // ── Additional notes ──────────────────────────────────────────
        if(hw_is_additional_notes_enabled() && hw_is_additional_notes_is_required() && empty(trim($data['additional_notes']))){

            $this->addError('additional_notes', sprintf(__('Please insert %s', 'hola-weddings'), hw_get_additional_notes_label()));
            $errors = true;
        }

        // ── Customer fields ──────────────────────────────────────────
        // Labels complete "Please insert your %s." below.
        $required_customer = [
            'first_name'   => __( 'first name', 'hola-weddings' ),
            'last_name'    => __( 'last name', 'hola-weddings' ),
            'phone_number' => __( 'phone number', 'hola-weddings' ),
            'address'      => __( 'address', 'hola-weddings' ),
            'city'         => __( 'city', 'hola-weddings' ),
            'zip'          => __( 'ZIP code', 'hola-weddings' ),
            'state'        => __( 'state / province', 'hola-weddings' ),
            'country'      => __( 'country', 'hola-weddings' ),
        ];

        foreach ( $required_customer as $field => $label ) {
            if ( empty( $data['customer'][ $field ] ) ) {
                /* translators: %s: name of the missing field, e.g. "first name" */
                $this->addError( $field, sprintf( __( 'Please insert your %s.', 'hola-weddings' ), $label ) );
                $errors = true;
            }
        }

        if ( $data['customer']['country'] === 'US' && $data['insurance_plan_id'] && ! hw_is_insurance_plan_allowed( hw_get_insurance_id(), $data['insurance_plan_id'], $data['customer']['state'] ) ) {
            $this->addError( 'insurance', __( 'The selected insurance plan is not available in your state. Please choose a different plan.', 'hola-weddings' ) );
            $errors = true;
        }

        if ( empty( $data['customer']['email'] ) || ! filter_var( $data['customer']['email'], FILTER_VALIDATE_EMAIL ) ) {
            $this->addError( 'email', __( 'Please insert a valid email address.', 'hola-weddings' ) );
            $errors = true;
        }

        // ── User account fields (optional) ───────────────────────────
        // Creating an account is opt-in: a logged-out visitor can book without
        // one. The fields are validated only once the visitor has started
        // filling them in, so a half-filled pair still fails loudly.
        if ( $this->isCreatingAccount( $data ) ) {

            if ( empty( $data['account_email'] ) || ! is_email( $data['account_email'] ) ) {
                $this->addError( 'account_email', __( 'Please enter a valid email address for your account.', 'hola-weddings' ) );
                $errors = true;

            } elseif ( email_exists( sanitize_email( $data['account_email'] ) ) ) {

                // Stop here rather than adopting the existing account: typing an
                // address proves nothing about owning it. Nothing has been
                // stored or charged at this point, so the visitor can sign in
                // and submit again, or book as a guest.
                $this->addError( 'account_email', __( 'An account already exists for this email address. Please sign in first, or book without creating an account.', 'hola-weddings' ) );
                $errors = true;
            }

            if ( empty( $data['password'] ) ) {
                $this->addError( 'password', __( 'Please enter a password.', 'hola-weddings' ) );
                $errors = true;
            } elseif ( strlen( $data['password'] ) < 8 ) {
                $this->addError( 'password', __( 'Password must be at least 8 characters.', 'hola-weddings' ) );
                $errors = true;
            }
        }

        // ── Payment validation (only when payment is enabled and room is available) ──────
        $room_available_for_payment = $room_block->isAvailable( $data['ci'], $data['co'] );

        if ( $payment_enabled && $room_available_for_payment ) {

            $payment_data = $data['payment'];
            $card_type = strtolower( $payment_data['card-type'] );

            if ( empty( $payment_data['card-owner'] ) ) {
                $this->addError( 'card-owner', __( 'Please insert the cardholder name.', 'hola-weddings' ) );
                $errors = true;
            }

            if ( ! in_array( $card_type, [ 'visa', 'mc', 'amex' ], true ) ) {
                $this->addError( 'card-type', __( 'Please choose a valid card type.', 'hola-weddings' ) );
                $errors = true;
            }

            $card_number = str_replace( '-', '', $payment_data['card-number'] ?? '' );
            if ( empty( $card_number ) ) {
                $this->addError( 'card-number', __( 'Please insert the card number.', 'hola-weddings' ) );
                $errors = true;
            } else {
                $data['payment']['card-number'] = $card_number;
                $expected_length = $card_type === 'amex' ? 15 : 16;
                if ( strlen( $card_number ) !== $expected_length ) {
                    $this->addError( 'card-number', sprintf( __( 'Card number must be %d digits.', 'hola-weddings' ), $expected_length ) );
                    $errors = true;
                }
            }

            $month = intval( $payment_data['card-month'] );
            $year  = intval( $payment_data['card-year'] );

            if ( $month < 1 || $month > 12 ) {
                $this->addError( 'card-month', __( 'Invalid expiration month.', 'hola-weddings' ) );
                $errors = true;
            } elseif ( $year === intval( date('Y') ) && $month < intval( date('n') ) ) {
                $this->addError( 'card-month', __( 'Card has expired.', 'hola-weddings' ) );
                $errors = true;
            }

            if ( $year < intval( date('Y') ) ) {
                $this->addError( 'card-year', __( 'Invalid expiration year.', 'hola-weddings' ) );
                $errors = true;
            }

            // CCV
            $ccv      = $payment_data['card-ccv'] ?? '';
            $ccv_len  = $card_type === 'amex' ? 4 : 3;
            $ccv_max  = $card_type === 'amex' ? 10000 : 1000;

            if ( empty( $ccv ) ) {
                $this->addError( 'card-ccv', __( 'Please insert the card CVV.', 'hola-weddings' ) );
                $errors = true;
            } elseif ( intval( $ccv ) < 0 || intval( $ccv ) >= $ccv_max || strlen( $ccv ) > $ccv_len ) {
                $this->addError( 'card-ccv', __( 'Invalid CVV.', 'hola-weddings' ) );
                $errors = true;
            }
        }

        // ── Terms & Conditinos ──────────────────────────────────────────
        if ( empty( $data['toc'] ) || $data['toc'] != 1 ) {
            $this->addError( 'toc', __( 'Please accept the terms and conditions.', 'hola-weddings' ) );
            $errors = true;
        }

        // ── Signature ───────────────────────────────────────────────────
        if ( empty( $data['signature'] ) ) {
            $this->addError( 'signature', __( 'Signature is required.', 'hola-weddings' ) );
            $errors = true;
        }

        return ! $errors;
    }

    // ── User resolution ───────────────────────────────────────────────────────

    /**
     * Whether this submission asks for a user account to be created.
     *
     * Account creation is optional: it happens only when a logged-out visitor
     * fills in the create-account fields, and never when the site hides that
     * box ( hw_booking_form_hide_create_account ).
     */
    private function isCreatingAccount( array $data ): bool {

        if ( is_user_logged_in() || hw_booking_form_hide_create_account() ) {
            return false;
        }

        return ! empty( $data['account_email'] ) || ! empty( $data['password'] );
    }

    /**
     * Returns the WP user ID to associate with the quote.
     *
     * - Logged in     → current user; ownership comes from the session, never
     *                   from comparing the reservation's contact email
     * - No account    → 0, the quote is stored ownerless (guest booking) and no
     *                   WordPress user is created
     * - Create account→ a brand new, unverified user
     *
     * There is deliberately no "email already exists → use that account" branch.
     * An anonymous request typing an address is not proof of owning it, and
     * silently attaching the booking to that account would hand a stranger's
     * reservation to them — or theirs to a stranger. Validation rejects the
     * submission earlier instead, before anything is stored.
     */
    private function resolveUser( array $data ): int|\WP_Error {

        if ( is_user_logged_in() ) {
            return get_current_user_id();
        }

        if ( ! $this->isCreatingAccount( $data ) ) {
            return 0;
        }

        $email = sanitize_email( $data['account_email'] );

        $username = hw_get_unique_username(
            $data['customer']['first_name'][0] . $data['customer']['last_name']
        );

        $user_id = wp_insert_user( [
            'user_login' => $username,
            'user_pass'  => ! empty( $data['password'] ) ? $data['password'] : wp_generate_password( 16, true, false ),
            'user_email' => $email,
            'first_name' => $data['customer']['first_name'],
            'last_name'  => $data['customer']['last_name'],
        ] );

        if ( is_wp_error( $user_id ) ) {
            return $user_id;
        }

        // The account exists but is not trusted yet: the visitor has to prove
        // the address before any of it shows up under My Reservations.
        hw_email_verification()->sendVerificationEmail( $user_id );

        return $user_id;
    }

    // ── Customer data processing ──────────────────────────────────────────────

    /**
     * Format guest dates to strings, calculate ages, stamp deposit metadata.
     * Mutates $data in-place.
     *
     * @param array $data
     * @return void
     */
    private function processCustomerData( array &$data ): void {

        $n_guests = $data['adults'] + $data['children'] + $data['infants'];
        if ( hw_is_teen_enabled() && is_numeric( $data['teen'] ) ) {
            $n_guests += $data['teen'];
        }

        for ( $i = 1; $i <= $n_guests; $i++ ) {
            $date_str = AgeSelector::format_data_to_date( $data['guests'][ $i ]['date'] );
            $data['guests'][ $i ]['date'] = $date_str;
            $date_obj     = new \DateTime( $date_str );
            $checkout_obj = new \DateTime( $data['co'] );
            $data['guests'][ $i ]['age'] = hw_calc_guest_age( $date_obj, $checkout_obj );
        }
    }

    // ── Transfer fee ──────────────────────────────────────────────────────────

    /**
     * calculate transfer fee if enabled
     *
     * @param array $data
     * @return integer
     */
    private function calculateTransfer( array $data ): int {

        if ( ! hw_is_transfer_enabled() ) {
            return 0;
        }

        $total = 0;
        $fee   = hw_get_service_fee();
        $mode  = hw_get_transportation_mode();

        if ( $mode === 'room' ) {
            $total = intval( $fee );
        }

        if ( $mode === 'person' ) {
            $child_fee = hw_get_child_service_fee() ?: $fee;
            $total     = intval( $data['adults'] + $data['teen'] ) * $fee;

            if ( $data['children'] > 0 ) {
                $total += $data['children'] * $child_fee;
            }
        }

        return intval( $total );
    }

    // ── Insurance ─────────────────────────────────────────────────────────────

    /**
     * Calculate insurance cost, mirroring all 5 modes from the legacy class.
     *
     * Taken by reference: the plan the customer actually bought is resolved in
     * here, and insurance_id/insurance_plan_id are written back onto $data so
     * the caller can store them as hw_insurance_id / hw_insurance_plan_id. By
     * value they were resolved and then thrown away, so every quote was saved
     * with a null hw_insurance_id no matter what was purchased.
     *
     * @param array $data
     * @param integer $room_total
     * @param \HW\Models\RoomBlock $room_block
     * @param array $rate_type
     * @return array { total: int, rates: array }
     */
    private function calculateInsurance( array &$data, int $room_total, \HW\Models\RoomBlock $room_block, array $rate_type ): array {

        $zero = [ 'total' => 0, 'rates' => [] ];

        $plan_id   = intval( $data['insurance_plan_id'] );
        $insurance = hw_get_insurance();

        if ( $data['customer']['country'] !== 'US' || ! $insurance || ! $plan_id ) {
            return $zero;
        }

        $data['insurance_id']      = $insurance['id'];
        $data['insurance_plan_id'] = $plan_id;

        $plan = Insurance::getPlan( $plan_id );

        // getPlan() answers false for an unknown id and a WP_Error when the API
        // is unreachable. Either way there is nothing to charge for, so unwind
        // the ids above rather than storing a plan the quote does not have.
        if ( ! is_array( $plan ) ) {
            $data['insurance_id']      = null;
            $data['insurance_plan_id'] = null;
            return $zero;
        }

        $nights        = getNights( $data['ci'], $data['co'] );
        $transfer_total = $data['service_fee'] ?? 0;
        $ins_total     = 0;
        $ins_rates     = [];

        switch ( $insurance['mode'] ) {

            case 'night':
                $rates      = hw_get_insurance_range( $plan, $nights );
                $ins_total += $rates['adult'] * ( $data['adults'] + $data['teen'] );
                $ins_total += $rates['child'] * $data['children'];
                $ins_rates  = $rates;
                break;

            case 'price':
                $rates      = hw_get_insurance_range( $plan, $room_total );
                $ins_total += $rates['adult'] * ( $data['adults'] + $data['children'] + $data['teen'] );
                $ins_rates  = $rates;
                break;

            case 'person_total':
                // this insurance is calculated with adult price, even if there are children or teens
                $adult_tc = 0;
                $child_tc = 0;

                // alter transfer cost to be added to the person total only if transfer is enabled
                if ( hw_is_transfer_enabled() ) {
                    switch ( hw_get_transportation_mode() ) {
                        case 'room':
                            $persons  = $data['adults'] + $data['teen'] + $data['children'] + $data['infants'];
                            $per_head = $persons > 0 ? hw_get_service_fee() / $persons : 0;
                            $adult_tc = $child_tc = $per_head;
                            break;
                        case 'person':
                            $adult_tc = hw_get_service_fee();
                            $csf      = hw_get_child_service_fee();
                            $child_tc = $csf > 0 ? $csf : $adult_tc;
                            break;
                    }
                }

                $adult_total = hw_calc_person_type_total( $room_block, $rate_type, 'adult', $data['ci'], $data['co'] ) + $adult_tc;
                $rates       = hw_get_insurance_range( $plan, $adult_total );
                $ins_total  += $rates['adult'] * $data['adults'];
                $ins_rates['adult'] = $rates['adult'];

                if ( $data['teen'] > 0 ) {
                    $teen_total  = hw_calc_person_type_total( $room_block, $rate_type, 'teen', $data['ci'], $data['co'] ) + $adult_tc;
                    $rates       = hw_get_insurance_range( $plan, $teen_total );
                    $ins_total  += $rates['teen'] * $data['teen'];
                    $ins_rates['teen'] = $rates['teen'];
                }

                if ( $data['children'] > 0 ) {
                    $child_total = hw_calc_person_type_total( $room_block, $rate_type, 'child', $data['ci'], $data['co'] ) + $child_tc;
                    $rates       = hw_get_insurance_range( $plan, $child_total );
                    $ins_total  += $rates['child'] * $data['children'];
                    $ins_rates['child'] = $rates['child'];
                }

                if ( $data['infants'] > 0 ) {
                    // infants always pay lower tier, pass 0 as person type total
                    $rates       = hw_get_insurance_range( $plan, 0 );
                    $ins_total  += $rates['infant'] * $data['infants'];
                    $ins_rates['infant'] = $rates['infant'];
                }
                break;

            case 'total_cost_per_person':
                // CV insurance calculation, get total ( room + transfer ) / amount of people (including infants), infants always don't pay so they are in the lower tier
                $persons_count    = $data['adults'] + $data['children'] + $data['teen'] + $data['infants'];
                $price_per_person = $persons_count > 0 ? ( $room_total + $transfer_total ) / $persons_count : 0;
                $rates            = hw_get_insurance_range( $plan, $price_per_person );
                $ins_total        = ( $data['adults']   * $rates['adult']  )
                                  + ( $data['teen']     * $rates['teen']   )
                                  + ( $data['children'] * $rates['child']  )
                                  + ( $data['infants']  * $rates['infant'] );
                $ins_rates        = $rates;
                break;

            case 'room_total':
                $rates     = hw_get_insurance_range( $plan, $room_total );
                $ins_rates = $rates;
                $cost = 0;
                
                if( isset( $plan['calculation_type'] ) ){
                    if($plan['calculation_type'] === 'fixed'){
                        $cost = $rates['amount'];
                    }

                    if($plan['calculation_type'] === 'percentage'){
                        $cost = round( $room_total *  ( $rates['amount'] / 100 ), 0, PHP_ROUND_HALF_UP );
                    }
                }

                $ins_rates = $rates;
                $ins_total += $cost;
                break;
        }

        return [ 'total' => intval( $ins_total ), 'rates' => $ins_rates ];
    }

    // ── Quote status ──────────────────────────────────────────────────────────

    /**
     * Determine quote status
     *
     * @param bool $is_available
     * @param array $data
     * @return string
     */
    private function determineStatus( bool $is_available, array $data ): string {

        if ( hw_force_quote_status() ) {
            return hw_forced_quote_status();
        }

        return $is_available
            ? QuoteStatus::CONFIRMED->value
            : QuoteStatus::PENDING->value;
    }

    // ── Deposit validation ────────────────────────────────────────────────────

    /**
     * Compute the payable deposit amount and validate the deposit selection.
     * Mutates $data['deposit'] to set custom_amount, ip_address, and accepted_on.
     *
     * @param array $data            Normalized request data (passed by reference)
     * @param array $totals          Room quote totals from hw_calc_room_quote_total()
     * @param float $running_total   Grand total (room + transfer + insurance)
     * @param float $insurance_total Insurance portion of the running total
     * @param int   $nights          Number of nights
     * @return bool
     */
    private function validateDeposit( array &$data, array $totals, float $running_total, float $insurance_total, int $nights ): bool {

        $errors       = false;
        $deposit_type = $data['deposit']['amount'] ?? '';

        if ( empty( $deposit_type ) || ! in_array( $deposit_type, [ 'deposit', 'full', 'custom' ], true ) ) {
            $this->addError( 'deposit-amount', __( 'Please choose a valid deposit option.', 'hola-weddings' ) );
            $errors = true;
        } else {
            // Base deposit: fixed min or dynamic (first-night rate × multiplier)
            $base_deposit = hw_get_min_deposit();
            if ( hw_get_deposit_type() === 'dynamic' ) {
                $first_day  = reset( $totals['total_per_day'] );
                $multiplier = min( hw_get_deposit_nights_multiplier(), $nights );
                if ( $first_day > 0 ) {
                    $base_deposit = $first_day * $multiplier;
                }
            }

            // Minimum payable: base + insurance, never exceeds grand total
            $min_deposit_amount = min( $base_deposit + $insurance_total, $running_total );

            switch ( $deposit_type ) {
                case 'full':
                    $data['deposit']['custom_amount'] = $running_total;
                    break;
                case 'custom':
                    if ( floatval( $data['deposit']['custom_amount'] ) < $min_deposit_amount ) {
                        $this->addError( 'deposit-custom-amount', __( 'Insert a valid custom payment amount.', 'hola-weddings' ) );
                        $errors = true;
                    }
                    break;
                default: // 'deposit'
                    $data['deposit']['custom_amount'] = $min_deposit_amount;
                    break;
            }
        }

        if ( empty( $data['deposit']['accept_charge'] ) || $data['deposit']['accept_charge'] != 1 ) {
            $this->addError( 'deposit-acceptance', __( 'Please read and accept the deposit charge disclaimer.', 'hola-weddings' ) );
            $errors = true;
        }

        $data['deposit']['ip_address']  = hw_get_the_user_ip();
        $data['deposit']['accepted_on'] = time();

        return ! $errors;
    }

    // ── Card encryption ───────────────────────────────────────────────────────

    /**
     * Encrypt card
     *
     * @param array $payment
     * @return array { card: string, iv: string }
     */
    private function encryptCard( array $payment ): array {

        $ivlen     = openssl_cipher_iv_length( self::CIPHER );
        $iv        = openssl_random_pseudo_bytes( $ivlen );
        $encrypted = openssl_encrypt( json_encode( $payment ), self::CIPHER, self::KEY, 0, $iv );

        return [
            'card' => $encrypted,
            'iv'   => bin2hex( $iv ),
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function addError( string $key, string $message ): void {
        $this->errors[ $key ] = $message;
    }

    private function fail( string $message ): array {
        return [
            'success'  => false,
            'quote_id' => null,
            'errors'   => $this->errors,
            'message'  => $message,
        ];
    }
}
