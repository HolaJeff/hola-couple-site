<?php

namespace HW\Email\Templates;

use HW\Email\AbstractTemplate;
use HW\Helpers\Countries;
use HW\Models\Quote;

/**
 * Internal notification sent to the Hola Weddings inbox when a new quote lands.
 *
 * Everything on it is read from the Quote model rather than from the meta array
 * the hw_after_create_quote hook happens to carry: the model is the only view
 * that reconciles the two billing key shapes, and it is what a resend hours
 * later (see `wp hw test-quote-email`) has to read from anyway.
 *
 * Deliberately layout-less. This one goes to staff, not customers, so it wants
 * a plain table it can be skimmed and forwarded from - not the branded
 * card-and-logo chrome the customer-facing templates wrap themselves in.
 */
class NewQuoteAdminNotification extends AbstractTemplate {

    /**
     * No layout: the template below renders the complete message.
     *
     * @var string
     */
    protected $layout = '';

    protected Quote $quote;

    /**
     * constructor
     *
     * @param Quote $quote
     */
    public function __construct( Quote $quote ) {

        $this->quote = $quote;
    }

    /**
     * subject line
     *
     * @return string
     */
    public function getSubject(): string {

        $billing = $this->quote->getBillingInformation();

        return sprintf(
            'New quote for wedding %s, from %s %s - <%s>',
            $this->getWeddingName(),
            $billing->getFirstName(),
            $billing->getLastName(),
            $billing->getEmail()
        );
    }

    /**
     * template file
     *
     * @return string
     */
    public function getTemplate(): string {

        return 'templates/emails/quotes/new-quote-admin.php';
    }

    /**
     * recipients
     *
     * @return array
     */
    public function getRecipients(): array {

        // phase4-ops-email: route to the tenant's configured ops address
        // (hw_agency_ops_email), keeping staging's default when it is empty.
        $ops = function_exists( 'hw_agency_ops_email' ) ? (string) hw_agency_ops_email() : '';

        $recipients = [ is_email( $ops ) ? $ops : 'hola@holaweddings.com' ];

        // Stored as a comma separated list in the settings screen.
        $alternatives = (string) hw_get_option( 'hw_alternative_email', '' );

        foreach ( explode( ',', $alternatives ) as $email ) {

            $email = trim( $email );

            if ( is_email( $email ) ) {
                $recipients[] = $email;
            }
        }

        $recipients = array_values( array_unique( $recipients ) );

        /**
         * Filter the recipients of the new-quote admin notification.
         *
         * @param array $recipients
         * @param Quote $quote
         */
        return apply_filters( 'hw_new_quote_admin_recipients', $recipients, $this->quote );
    }

    /**
     * headers - replies go straight back to the customer
     *
     * @return array
     */
    public function getHeaders(): array {

        $billing = $this->quote->getBillingInformation();

        return [
            'Content-Type: text/html; charset=UTF-8',
            sprintf( 'Reply-To: %s %s <%s>', $billing->getFirstName(), $billing->getLastName(), $billing->getEmail() ),
            $this->getFromHeader(),
        ];
    }

    /**
     * From header: branded sender (hw_email_from_header), then the tenant ops
     * sender (hw_agency_ops_from), then staging's default.
     *
     * @return string
     */
    protected function getFromHeader(): string {

        $from = function_exists( 'hw_email_from_header' ) ? (string) hw_email_from_header() : '';

        if ( $from === '' && function_exists( 'hw_agency_ops_from' ) ) {
            $from = (string) hw_agency_ops_from();
        }

        return $from !== '' ? $from : 'From: Hola Weddings <hola@holaweddings.com>';
    }

    /**
     * template data
     *
     * @return array
     */
    public function getData(): array {

        $quote   = $this->quote;
        $billing = $quote->getBillingInformation();
        $hotel   = (object) hw_get_hotel();
        $mode    = $quote->getMode();

        // Package bookings span a fixed run of nights defined on the package,
        // not the dates carried on the quote.
        $nights = $mode == 'package' && $quote->getPackageID()
            ? hw_calc_package_nights( $quote->getPackageID() )
            : $quote->getNights();

        $room = $this->resolveRoom();

        $statuses = hw_get_quote_statuses();

        return [
            'quote_id'        => $quote->getID(),
            'edit_link'       => $quote->getEditLink(),
            'wedding_name'    => $this->getWeddingName(),
            'mode'            => $mode,
            'mode_label'      => $mode == 'room' ? 'Room' : 'Package',
            'package_title'   => $quote->getPackageID() ? get_the_title( $quote->getPackageID() ) : '',
            'status_label'    => $statuses[ $quote->getStatus() ] ?? $quote->getStatus(),
            'full_name'       => trim( $billing->getFirstName() . ' ' . $billing->getLastName() ),
            'email'           => $billing->getEmail(),
            'phone'           => $billing->getPhone(),
            'address'         => $billing->getAddress(),
            'city'            => $billing->getCity(),
            'zip'             => $billing->getPostalCode(),
            'state'           => $billing->getState(),
            'country'         => $this->formatCountry( $billing->getCountry() ),
            'checkin'         => $quote->getCheckin()->format( 'l dS, F Y' ),
            'checkout'        => $quote->getCheckout()->format( 'l dS, F Y' ),
            'nights'          => $nights,
            'adults'          => $quote->getAdults(),
            'teen'            => $quote->getTeen(),
            'teen_enabled'    => (bool) hw_is_teen_enabled(),
            'children'        => $quote->getChildren(),
            'infants'         => $quote->getInfants(),
            'hotel_name'      => $hotel->name ?? '--',
            'room_name'       => $room['name'] ?? '--',
            'rate_type'       => $quote->getRateID() ? get_the_title( $quote->getRateID() ) : '--',
            'room_total'      => hw_format_price( $quote->getRoomTotal() ),
            'guests'          => $quote->getGuests(),
            'has_insurance'   => $quote->hasInsurance(),
            'insurance_name'  => $this->getInsuranceName(),
            'insurance_total' => hw_format_price( $quote->getInsuranceTotal() ),
            'service_fee'     => $quote->getServiceFee() ? hw_format_price( $quote->getServiceFee() ) : '--',
            'total'           => hw_format_price( $quote->getTotal() ),
            'notes'           => $quote->getAdditionalNotes(),
            'deposit'         => $quote->getDeposit(),
        ];
    }

    /**
     * The booked room, as the API describes it.
     *
     * Resolved through the room block, the only source of truth for which room
     * a quote is on. hw_get_room() behind it is cached and already answers
     * false rather than a WP_Error when the API is unreachable - a notification
     * that cannot name the room is still worth sending.
     *
     * @return array
     */
    protected function resolveRoom(): array {

        $room = $this->quote->getRoom();

        return is_array( $room ) ? $room : [];
    }

    /**
     * The couple this site belongs to.
     *
     * @return string
     */
    protected function getWeddingName(): string {

        return trim( sprintf(
            '%s & %s',
            hw_get_option( 'hw_his_name', '' ),
            hw_get_option( 'hw_her_name', '' )
        ), ' &' );
    }

    /**
     * Name of the purchased plan, or a note that it could not be resolved.
     *
     * The plan lives behind the API, so a lookup failure must not be reported
     * as "No" - the customer was still charged for it.
     *
     * @return string
     */
    protected function getInsuranceName(): string {

        if ( ! $this->quote->hasInsurance() ) {
            return 'No';
        }

        $plan = $this->quote->getInsurancePlan();

        if ( ! empty( $plan['name'] ) ) {
            return $plan['name'];
        }

        return sprintf( 'Yes (plan #%s - name unavailable)', $this->quote->getInsurancePlanID() );
    }

    /**
     * "United States (US)", or a dash when no country was captured.
     *
     * @param string $code
     * @return string
     */
    protected function formatCountry( $code ): string {

        if ( empty( $code ) ) {
            return '--';
        }

        // getList(true) hands back the whole country row, so indexing straight
        // into it printed a bare "Array" next to the code.
        $name = Countries::getCountryName( $code );

        return $name ? sprintf( '%s (%s)', $name, $code ) : $code;
    }
}
