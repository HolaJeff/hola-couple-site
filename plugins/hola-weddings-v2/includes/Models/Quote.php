<?php

namespace HW\Models;

use DateTime;
use HW\API\Operations\Insurance;
use HW\Models\CRM\BillingInformation;
use HW\Quotes;
use HW\Utils\CardBuilder;
use HW\Utils\RoomOccupancy;

class Quote extends DecoratorModel{

    protected $checkin;

    protected $checkout;

    protected $guests;

    public static $post_type = 'quote';

    /**
     * billing information object
     *
     * @var HW\Models\CRM\BillingInformation
     */
    protected $_billing_information;

    /**
     * constructor
     *
     * @param WP_Post|integer $post
     */
    public function __construct( $post )
    {
        parent::__construct($post);

        $billing_information_data = $this->get('hw_customer_data');
        $this->_billing_information = new BillingInformation($billing_information_data);

        $checkin = $this->get('hw_checkin') ?? '';
        $checkout = $this->get('hw_checkout') ?? '';

        $this->checkin = new DateTime($checkin);
        $this->checkout = new DateTime($checkout);        
    }

    /**
	 * Set the object properties based on a named array/hash.
	 *
	 * @param   mixed  $properties  Either an associative array or another object.
	 *
	 * @return  boolean
	 */
    public function setProperties($data)
    {
        // check if dates has changed update the number of nights
        if($this->getCheckin()->format('Y-m-d') != $data['hw_checkin'] || $this->getCheckout()->format('Y-m-d') != $data['hw_checkout']){

            // update nights
            $this->set('hw_nights', getNights($data['hw_checkin'], $data['hw_checkout'])); 
            $recalculate_room_total = true;
        }

        // check if room block has changed
        if($this->getRoomBlockID() != $data['hw_room_block_id'] ){

            // update nights
            $recalculate_room_total = true;
        }

        // check if
        if($this->getAdults() != intval($data['hw_adults']) || $this->getChildren() != intval($data['hw_children'])
            || $this->getTeen() != intval($data['hw_teen'])){
                
            $recalculate_room_total = true;
        }

        // billing
        if(isset($data['billing']) && !empty($data['billing'])){

            $this->set('hw_customer_data', $data['billing']);
            unset($data['billing']);
        }

        // guests
        if(isset($data['guests']) && $data['guests'] != null){

            $this->set('hw_guests', array_values($data['guests']));
            unset($data['guests']);
        }

        // guests
        if(isset($data['deposit']) && $data['deposit'] != null){

            $deposit = $this->getDeposit();
            $this->set('hw_deposit', array_merge($deposit, $data['deposit']));
            unset($data['deposit']);
        }

        // guests
        if(isset($data['card']) && !empty($data['card'])){

            $this->setCardData($data['card']);
            unset($data['card']);
        }

        parent::setProperties($data);

        // recalculate 
        if($recalculate_room_total){

            $this->updateRoomTotal();
        }
    }

    /**
	 * property was updated
	 *
	 * @param string $property
	 * @param mixed $value
	 * @return void
	 */
	public function propertyUpdated($property, $value, $previous){

        switch($property){
            case 'hw_checkin':
                $this->checkin = new DateTime($this->get('hw_checkin'));
                break;
            case 'hw_checkout':
                $this->checkout = new DateTime($this->get('hw_checkout'));
                break;
            case 'hw_insurance_total':
            case 'hw_room_total':
                $this->updateTotal();
                break;
            case 'hw_insurance_plan_id':
                if($previous != $value){
                    $this->updateInsuranceTotal($value);
                }
                break;
            case 'hw_service_fee':
                if(intval($value) != $previous){
                    $this->updateTotal();
                }
                break;
        }
    }

    /**
     * return the full name
     *
     * @return string
     */
    public function getFullName(){

        return sprintf('%s %s', $this->getFirstName(), $this->getLastName());
    }

    /**
     * return first namr
     *
     * @return string
     */
    public function getFirstName(){

        return $this->get('hw_first_name');
    }

    /**
     * return last name
     *
     * @return string
     */
    public function getLastName(){

        return $this->get('hw_last_name');
    }

    /**
     * return guests
     *
     * @return array
     */
    public function getGuests(){

        return $this->get('hw_guests') ?: [];
    }

    /**
     * return the main guest
     *
     * @return mixed
     */
    public function getMainGuest(){

        return reset($this->getGuests());
    }

    /**
     * return the quote status
     *
     * @return string
     */
    public function getStatus(){

        return $this->get('hw_quote_status');
    }

    /**
     * return additional notes
     *
     * @return string
     */
    public function getAdditionalNotes(){

        return $this->get('hw_additional_notes');
    }

    /**
     * return the permalink
     *
     * @return string
     */
    public function getPermalink(){

        return hw_get_reservation_permalink($this->getID());
    }

    /**
     * Encrypt and update card data
     *
     * @param \HW\Utils\Card $card
     * @return void
     */
    public function setCardData(\HW\Utils\Card $card){

        // map to legacy encrypted fields
        $map = [
            'card-owner' => $card->getCardHolder(),
            'card-month' => $card->getExpMonth(),
            'card-year' => $card->getExpYear(),
            'card-type' => $card->getType(),
            'card-ccv' => $card->getCCV(),
            'card-number' => $card->getNumber()
        ];

        // Encrypt the data
        $ivlen = openssl_cipher_iv_length(Quotes::$cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);

        $encrypted_card = openssl_encrypt( json_encode($map), Quotes::$cipher, Quotes::$key, $options=0, $iv);
        
        update_post_meta($this->getID(), 'hw_iv',  bin2hex($iv));
        $this->set('hw_card', $encrypted_card);
    }

    /**
     * return if payment data exists
     *
     * @return boolean
     */
    public function hasCardData(){

        $card_data = get_post_meta($this->getID(), 'hw_card', true);
        return !empty($card_data);
    }

    /**
     * return the card
     *
     * @return \HW\Utils\Card
     */
    public function getCardData(){

        if(!$this->hasCardData()){
            
            return false;
        }

        $iv = get_post_meta($this->getID(), 'hw_iv', true);
        $encrypted_card = get_post_meta($this->getID(), 'hw_card', true);        

        $card = json_decode(openssl_decrypt ( $encrypted_card, Quotes::$cipher, Quotes::$key, $options=0, hex2bin($iv)), true);

        switch($card['card-type']){
            case 'visa':
                $card['card-type'] = '001';
                break;
            case 'mc':
                $card['card-type'] = '002';
                break;
            case 'amex':
                $card['card-type'] = '003';
                break;
        }

        return CardBuilder::build($card['card-number'], $card['card-month'], $card['card-year'], $card['card-ccv'], $card['card-type'], $card['card-owner']);
    }

    /**
     * return billing information
     *
     * @return HW\Models\CRM\BillingInformation;
     */
    public function getBillingInformation(){
        
        return $this->_billing_information;
    }

    /**
     * return checkin date
     *
     * @return \DateTime
     */
    public function getCheckin(){

        return $this->get('checkin');
    }

    /**
     * return checkin date
     *
     * @return \DateTime
     */
    public function getCheckout(){
        
        return $this->get('checkout');
    }

    /**
     * return nights
     *
     * @return int
     */
    public function getNights(){
        
        return intval($this->get('hw_nights'));
    }

    /**
     * return adults number
     *
     * @return int
     */
    public function getAdults(){

        return $this->get('hw_adults');
    }

    /**
     * return teen number
     *
     * @return int
     */
    public function getTeen(){
        
        return $this->get('hw_teen');
    }

    /**
     * return children number
     *
     * @return int
     */
    public function getChildren(){
        
        return $this->get('hw_children');
    }

    /**
     * return infants number
     *
     * @return int
     */
    public function getInfants(){
        
        return $this->get('hw_infants');
    }

    /**
     * return room occupancy
     *
     * @return RoomOccupancy
     */
    public function getOccupancy(){

        return (new RoomOccupancy($this->getAdults(), $this->getChildren(), $this->getTeen(), $this->getInfants()));
    }

    /**
     * return room block id
     *
     * @return int
     */
    public function getRoomBlockID(){

        return $this->get('hw_room_block_id');
    }

    /**
     * return room block
     *
     * @return RoomBlock
     */
    public function getRoomBlock(){

        return hw_get_room_block($this->getRoomBlockID());
    }

    /**
     * return room
     *
     * @return array
     */
    public function getRoom(){

        return hw_get_room($this->get('hw_room_id'));
    }

    /**
     * return room total
     *
     * @return int
     */
    public function getRoomTotal(){

        return intval($this->get('hw_room_total', 0));
    }

    /**
     * return rates per day
     *
     * @return array
     */
    public function getRates(){

        return $this->get('hw_rates_per_day');
    }

    /**
     * return service fee
     *
     * @return int
     */
    public function getServiceFee(){

        return intval($this->get('hw_service_fee', 0));
    }

    /**
     * return deposit details
     *
     * @return array
     */
    public function getDeposit(){

        $deposit = $this->get('hw_deposit');

        if(empty($deposit)){
            return [];
        }

        return $deposit;
    }

    /**
     * return the total
     *
     * @return int
     */
    public function getTotal(){

        return $this->get('hw_total');
    }

    /**
     * return the quote owner
     *
     * @return int|null
     */
    public function getOwner(){

        return (int)$this->post->post_author;
    }

    /**
     * return the insurance plan id
     *
     * @return int
     */
    public function getInsurancePlanID(){

        return $this->get('hw_insurance_plan_id');
    }

    /**
     * return the insurance plan
     *
     * @return Insurance|false
     */
    public function getInsurancePlan(){

        $insurance_plan_id = $this->getInsurancePlanID();
        return $insurance_plan_id ? Insurance::getPlan($insurance_plan_id) : false;
    }

    /**
     * return insurance total
     *
     * @return int
     */
    public function getInsuranceTotal(){

        return intval($this->get('hw_insurance_total', 0));
    }

    /**
     * return insurance rates
     *
     * @return array
     */
    public function getInsuranceRates(){

        return $this->get('hw_insurance_rates');
    }

    /**
     * return deposit details
     *
     * @return void
     */
    public function getDepositDetails(){

        return $this->get( 'hw_deposit');
    }

    /**
     * return a list of invoices associated to this quote
     *
     * @return Invoice[]
     */
    public function getInvoices(){

        $query = new \WP_Query([
            'post_type' => 'invoices',
            'posts_per_page' => -1,
            'orderby' => [ 'meta_value' => 'ASC', 'ID' => 'ASC' ],
            'meta_key' => 'hw_due_date',
            'meta_type' => 'DATE',
            'post_status' => [ 'publish' ],
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => 'hw_reservation_id',
                    'value' => $this->getID()
                ]
            ]
        ]);
    
        $invoices = [];
    
        if($query->have_posts()){
            foreach($query->posts as $post){
                $invoices[$post->ID] = new Invoice($post);
            }
        }
    
        return $invoices;
    }

    /**
     * return the sum of paid invocies
     *
     * @return int
     */
    public function getPaidAmount(){

        $paid_amount = 0;
        $invoices = $this->getInvoices();
        
        foreach($invoices as $invoice){
            $paid_amount += $invoice->getPaidAmount();
        }

        return $paid_amount;
    }

    /**
     * update room total and rates
     *
     * @return void
     */
    public function updateRoomTotal(){

        // get the room rate
        $room_block = $this->getRoomBlock();
        $room_total = $room_block->calculateTotal($this->getCheckin()->format('Y-m-d'), $this->getCheckout()->format('Y-m-d'), $this->getOccupancy());
        
        $this->set('hw_room_total', $room_total['total']);
        $this->set('hw_rates_per_day', $room_total['rates']);

        $this->updateTotal();
    }

    /**
     * update the quote total
     *
     * @return void
     */
    public function updateTotal(){

        $this->set('hw_total', $this->getRoomTotal() + $this->getInsuranceTotal() + $this->getServiceFee());
    }

    /**
     * update the insurance
     *
     * @param int $id
     * @return void
     */
    protected function updateInsuranceTotal($planID = null){

        if($planID == 0){

            $this->set('hw_insurance_id', null);
            $this->set('hw_insurance_total', 0);
            $this->set('hw_insurance_rates', null);
            return;
        }

        if(empty($planID)){
            $planID = $this->getInsurancePlanID();
        }

        $plan = Insurance::getPlan($planID);
        $insurance = hw_get_insurance($plan['insurance_id']);
        $nights = $this->getNights();
        
        $insurance_total = 0;
        $insurance_rates = [];
        
        if($plan){

            switch($insurance['mode']){
                case 'night':
                    $nights_multiplier = $nights;
                    $rates = hw_get_insurance_range($plan, $nights_multiplier);
                    $insurance_total += ( $rates['adult'] * ( $this->getAdults() + $this->getTeen() ) );
                    $insurance_total += ( $rates['child'] * $this->getChildren() );
                    $insurance_rates = $rates;
                    break;
                case 'price':
                    $rates = hw_get_insurance_range($plan, $this->getRoomTotal());
                    $insurance_total += ( $rates['adult'] * ( $this->getAdults() + $this->getChildren() + $this->getTeen()) );
                    $insurance_rates = $rates;
                    break;
                case 'person_total':
                    // this insurance is calculated with adult price, even if there are children or teens
                    $room_block = $this->getRoomBlock();
                    $rate_type = $room_block->getRoomRateType($this->getAdults(), $this->getChildren(), $this->getTeen());

                    $ci = $this->getCheckin()->format('Y-m-d');
                    $co = $this->getCheckout()->format('Y-m-d');

                     // adult
                    $adult_total = hw_calc_person_type_total($room_block, $rate_type, 'adult', $ci, $co);
                    $rates = hw_get_insurance_range($plan, $adult_total);
                    $insurance_total += $rates['adult'] * $this->getAdults();
                    $insurance_rates['adult'] = $rates['adult'];

                    // teen
                    if($this->getTeen() > 0){
                        $teen_total = hw_calc_person_type_total($room_block, $rate_type, 'teen', $ci, $co);
                        $rates = hw_get_insurance_range($plan, $teen_total);
                        $insurance_total += $rates['teen'] * $this->getTeen();
                        $insurance_rates['teen'] = $rates['teen'];
                    }

                    // child
                    if($this->getChildren() > 0){
                        $child_total = hw_calc_person_type_total($room_block, $rate_type, 'child', $ci, $co);
                        $rates = hw_get_insurance_range($plan, $child_total);
                        $insurance_total += $rates['child'] * $this->getChildren();
                        $insurance_rates['child'] = $rates['child'];
                    }

                    // infant
                    if($this->getInfants() > 0){
                        $infant_total = 0;
                        $rates = hw_get_insurance_range($plan, $infant_total);
                        $insurance_total += $rates['infant'] * $this->getInfants();
                        $insurance_rates['infant'] = $rates['infant'];
                    }
                    break;
                case 'total_cost_per_person':
                    // CV insurance calculation, get total ( room + transfer ) / amount of people (including infants), infants always don't pay so they are in the lower tier
                    $total = $this->getRoomTotal() + $this->getServiceFee();
                    $persons_count = $this->getAdults() + $this->getChildren() + $this->getTeen() + $this->getInfants();
                    $price_per_person = $total / $persons_count;

                    $prices = hw_get_insurance_range($plan, $price_per_person);

                    $insurance_total = ( $this->getAdults() * $prices['adult'] ) 
                        + ( $this->getTeen() * $prices['teen'] ) 
                        + ( $this->getChildren() * $prices['child'] ) 
                        + ( $this->getInfants() * $prices['infant'] );

                    $insurance_rates = $prices;
                    break;
            }

            $this->set('hw_insurance_id', $insurance['id']);
            $this->set('hw_insurance_total', intval($insurance_total));
            $this->set('hw_insurance_rates', $insurance_rates);
        }
    }
}