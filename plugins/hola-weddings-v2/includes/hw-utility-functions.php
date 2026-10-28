<?php

use FG\ASN1\Universal\Boolean;
use \HW\Helpers\Countries;
use HW\Models\CRM\BillingInformation;
use HW\Models\Quote;

/*
 * Array custom key ordering
 */
function orderArray($arrayToOrder, $keys) {
    
    if(empty($keys) || empty($arrayToOrder)){
        return $arrayToOrder;
    }
    
    $ordered = array();
    
    foreach ($keys as $key) {
        
        if (isset($arrayToOrder[$key])) {
            
             $ordered[$key] = $arrayToOrder[$key];
        }
    }
    
    return $ordered;
}

/*
 * Get dates range
 */
function hw_get_dates_from_range($start, $end, $format = 'Y-m-d') {
      
    // Declare an empty array
    $array = array();
      
    // Variable that store the date interval
    // of period 1 day
    $interval = new DateInterval('P1D');
  
    $realEnd = new DateTime($end);
    $realEnd->add($interval);
  
    $period = new DatePeriod(new DateTime($start), $interval, $realEnd);
  
    // Use loop to store date into array
    foreach($period as $date) {                 
        $array[] = $date->format($format); 
    }
  
    // Return the array elements
    return $array;
}

/*
 * Wrapper function for hw_get_dates_from_range that return the date range without the last date
 *
 * In rentals last date is not used to calculate rates, it is the checkout
 */
function hw_get_dates_from_range_for_rental($start, $end, $format = 'Y-m-d') {
    
    $date_range = hw_get_dates_from_range($start, $end, $format);
    array_pop($date_range);
    return $date_range;
}

/**
 * return a list of months
 *
 * @return array
 */
function hw_get_months_list( $abbrev = true){

    return array_combine(range(01,12), hw_get_month_short_names($abbrev));
}
  
/**
 * Return a list of month names
 *
 * @param boolean $label_short
 * @return array
 */
function hw_get_month_short_names( $label_short = true ){
    
    if($label_short){
        return [
            __('Jan', 'hola-weddings'), 
            __('Feb', 'hola-weddings'),
            __('Mar', 'hola-weddings'),
            __('Apr', 'hola-weddings'),
            __('May', 'hola-weddings'),
            __('Jun', 'hola-weddings'),
            __('Jul', 'hola-weddings'),
            __('Aug', 'hola-weddings'),
            __('Sep', 'hola-weddings'),
            __('Oct', 'hola-weddings'),
            __('Nov', 'hola-weddings'),
            __('Dec', 'hola-weddings')
        ];
    }else{
        return [
            __('January', 'hola-weddings'), 
            __('February', 'hola-weddings'),
            __('March', 'hola-weddings'),
            __('April', 'hola-weddings'),
            __('May', 'hola-weddings'),
            __('June', 'hola-weddings'),
            __('July', 'hola-weddings'),
            __('August', 'hola-weddings'),
            __('September', 'hola-weddings'),
            __('October', 'hola-weddings'),
            __('November', 'hola-weddings'),
            __('December', 'hola-weddings')
        ];
    }
}

/**
 * return user ip address
 *
 * @return string
 */
function hw_get_the_user_ip() {

    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

/**
 * return the URL of the page currently being rendered
 *
 * Scheme and host come from the site being rendered rather than from the
 * request: HTTP_HOST is client controlled, is_ssl() describes the incoming
 * request rather than the blog, and on a network each site can have its own host
 * and scheme. Only the path and query are taken from the request — appending
 * home_url()'s own path as well would double it on a subdirectory install.
 *
 * Returns an empty string when there is no request path to read, so callers on
 * ajax, REST or CLI paths get nothing rather than something misleading. Always
 * provide a fallback when using this as a redirect target.
 *
 * @return string
 */
function hw_get_current_url(){

    if( empty( $_SERVER['REQUEST_URI'] ) ){

        return '';
    }

    $home = wp_parse_url( home_url( '/' ) );

    if( empty( $home['host'] ) ){

        return '';
    }

    $url = ( $home['scheme'] ?? 'https' ) . '://'
        . $home['host']
        . ( isset( $home['port'] ) ? ':' . $home['port'] : '' )
        . $_SERVER['REQUEST_URI'];

    return esc_url_raw( $url );
}

/**
 * create a unique username given a base
 *
 * @param string $username
 * @return string
 */
function hw_get_unique_username($username){

    if(username_exists($username)){
        $i = 1;
        $user_try = $username;
        
        do{
                        
            $user_try .= '-' . $i;
            $i++;
        }while(username_exists($user_try));
        
        $username = $user_try;
    }

    return $username;
}

/**
 * return state name
 *
 * @param int $key
 * @return string
 */
function hw_get_state_name($key){

    return Countries::getStateName($key);
}

/**
 * Utility function to format customer address
 *
 * @param BillingInformation|array $customer
 * @return string
 */
function hw_format_address(BillingInformation|array $customer){

    if($customer instanceof BillingInformation){
        $customer = $customer->toArray();
    }
       
    return sprintf('%s, %s %s, %s, %s', $customer['address'], $customer['city'], $customer['zip'] ?? $customer['postal_code'] ?? '', $customer['state'], $customer['country']);
}

/**
 * add capabilities to role
 *
 * @param WP_Role $role
 * @param string $role_single
 * @param string $role_plural
 * @return void
 */
function add_capabilities_to_role($role, $role_single = '', $role_plural = ''){

    // Edit
    $role->add_cap( 'edit_'.$role_single ); 
    $role->add_cap( 'edit_'.$role_plural ); 
    $role->add_cap( 'edit_other_'.$role_single );
    $role->add_cap( 'edit_other_'.$role_plural ); 
    $role->add_cap( 'edit_others_'.$role_plural );
    $role->add_cap( 'edit_private_'.$role_plural ); 
    $role->add_cap( 'edit_published_'.$role_plural );

    // publish
    $role->add_cap( 'publish_'.$role_single ); 
    $role->add_cap( 'publish_'.$role_plural ); 

    // Read
    $role->add_cap( 'read_'.$role_single ); 
    $role->add_cap( 'read_private_'.$role_single );
    $role->add_cap( 'read_private_'.$role_plural );

    // delete
    $role->add_cap( 'delete_'.$role_single ); 
    $role->add_cap( 'delete_'.$role_plural ); 
    $role->add_cap( 'delete_others_'.$role_plural ); 
    $role->add_cap( 'delete_private_'.$role_plural ); 
    $role->add_cap( 'delete_published_'.$role_plural ); 
}

/**
 * return guest's full name
 *
 * @param array $guest
 * @return string
 */
function hw_guest_full_name($guest){

    if(!isset($guest['name'])){
        return '';
    }

    $name = $guest['name'];

    if(isset($guest['lastname'])){
        $name .= ' ' . $guest['lastname'];
    }

    return $name;
}

/**
 * read a file content
 *
 * @param string $log_file
 * @return string
 */
function hw_read_log( string $log_file ){

    if(file_exists( $log_file )){
        
        return file_get_contents( $log_file );
    }

    return false;
}

/**
 * conert a string to camel case notation
 *
 * @param string $string
 * @return string
 */
function hw_string_to_camel_case(string $string) : string{

    $string = ucwords(
        strtr($string, 
        ['_' => ' ', '-' => ' '])
    );
    
    $string = str_replace(' ', '', $string);
    
    return $string;
}

/**
 * Convert a string into a URL-friendly slug.
 *
 * Transliterates accented characters to ASCII, lowercases the string, and
 * replaces every run of non-alphanumeric characters with the separator.
 *
 * e.g. hw_slugify('Château Déjà Vu!') => 'chateau-deja-vu'
 *
 * @param string $string    The string to slugify.
 * @param string $separator Character used to join words. Default '-'.
 * @return string
 */
function hw_slugify(string $string, string $separator = '-') : string{

    // transliterate accented characters to their ASCII equivalent
    $string = remove_accents($string);

    // lowercase
    $string = strtolower($string);

    // replace every run of non-alphanumeric characters with the separator
    $string = preg_replace('/[^a-z0-9]+/', $separator, $string);

    // trim leading/trailing separators
    return trim($string, $separator);
}

/**
 * evalualte string to boolean
 *
 * Strings like '1', 'true', 'on', 'yes' (case-insensitive) will return true.
 * All other values ('0', 'false', 'off', 'no', empty string, etc.) return false.
 *
 * @param string $value
 * @return bool
 */
function hw_evaluate_string_to_boolean(string|bool $value) : bool{

    if ( is_bool( $value ) ) return $value;

    return filter_var($value, FILTER_VALIDATE_BOOLEAN);
}

/**
 * convert number of columns to bootstrap column number
 *
 * @param integer $columns
 * @return integer
 */
function hw_bootstrap_column_converter(int $columns):int{

    if($columns > 6){
        $columns = 6;
    }

    if($columns < 1){
        $columns = 1;
    }
    
    return 12/$columns;
}

/**
 * Format quote occupancy with labels
 *
 * @param Quote $quote
 * @param string $separator
 * @return string
 */
function hw_format_quote_occupancy( Quote $quote, string $separator = ', ' ) : string{

    $occupancy = [];
    
    $occupancy[] =  sprintf(__('Adults: %d', 'hola-weddings'), $quote->getAdults());

    if($quote->getTeen() > 0){
        $occupancy[] = sprintf(__('Teens: %d', 'hola-weddings'), $quote->getTeen());
    }

    if($quote->getChildren() > 0){
        $occupancy[] = sprintf(__('Children: %d', 'hola-weddings'), $quote->getChildren());
    }

    if($quote->getInfants() > 0){
        $occupancy[] = sprintf(__('Infants: %d', 'hola-weddings'), $quote->getInfants());
    }

    return implode( $separator, $occupancy );
}

/**
 * helper function to render attributes
 *
 * @param array $attributes
 * @return void
 */
function renderAttributes(array $attributes = []): void
{

    foreach ($attributes as $key => $value) {
        if ($value === null || $value === false) {
            continue; // omit
        }

        $key = esc_attr((string) $key);

        // boolean attributes
        if ($value === true) {
            echo ' ' . $key;
            continue;
        }

        // allow arrays for data-* like ['a' => 1] => "a:1"? Better to JSON.
        if (is_array($value)) {
            $value = wp_json_encode($value);
        }

        echo ' ' . $key . '="' . esc_attr((string) $value) . '"';
    }
}
/**
 * normalize WYSIWYG/editor HTML so Html2Pdf can parse it.
 *
 * Html2Pdf validates closing tags against a plain LIFO stack, with no notion
 * of optional end tags or auto-closing blocks. Markup a browser renders
 * happily -- `<ul><li><strong>a</li></ul>`, `<ul><li>a<li>b</ul>`, a list
 * wrapped in a `<p>` -- makes it throw "Tags are closed in a wrong order".
 * Running the fragment through DOMDocument applies the normal HTML parsing
 * rules and hands back strictly balanced markup.
 *
 * @param string|null $html
 * @return string
 */
function hw_pdf_safe_html( ?string $html ) : string {

    $html = (string) $html;

    if ( trim( $html ) === '' ) {
        return '';
    }

    // Html2Pdf only implements a subset of HTML; anything outside it is either
    // ignored or fatal, so drop it before we normalize.
    $html = wp_kses( $html, hw_pdf_allowed_html() );

    if ( ! class_exists( 'DOMDocument' ) ) {
        return force_balance_tags( $html );
    }

    $doc      = new DOMDocument();
    $internal = libxml_use_internal_errors( true );

    // The XML declaration keeps DOMDocument from reading the fragment as
    // ISO-8859-1; the wrapper div gives us a known node to read back out of.
    $doc->loadHTML(
        '<?xml encoding="UTF-8"?><html><body><div id="hw-pdf-root">' . $html . '</div></body></html>',
        LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR
    );

    libxml_clear_errors();
    libxml_use_internal_errors( $internal );

    $root = ( new DOMXPath( $doc ) )->query( '//div[@id="hw-pdf-root"]' )->item( 0 );

    if ( ! $root ) {
        return force_balance_tags( $html );
    }

    $clean = '';

    foreach ( $root->childNodes as $child ) {
        $clean .= $doc->saveHTML( $child );
    }

    return $clean;
}

/**
 * tags Html2Pdf knows how to render, for use with wp_kses().
 *
 * @return array
 */
function hw_pdf_allowed_html() : array {

    $inline = [ 'style' => true, 'class' => true ];

    $tags = [
        'p'          => $inline,
        'br'         => [],
        'hr'         => $inline,
        'h1'         => $inline,
        'h2'         => $inline,
        'h3'         => $inline,
        'h4'         => $inline,
        'h5'         => $inline,
        'h6'         => $inline,
        'div'        => $inline,
        'span'       => $inline,
        'strong'     => $inline,
        'b'          => $inline,
        'em'         => $inline,
        'i'          => $inline,
        'u'          => $inline,
        's'          => $inline,
        'del'        => $inline,
        'ins'        => $inline,
        'sub'        => $inline,
        'sup'        => $inline,
        'small'      => $inline,
        'big'        => $inline,
        'cite'       => $inline,
        'address'    => $inline,
        'ul'         => $inline,
        'ol'         => $inline + [ 'start' => true ],
        'li'         => $inline,
        'blockquote' => $inline,
        'table'      => $inline,
        'thead'      => $inline,
        'tbody'      => $inline,
        'tfoot'      => $inline,
        'tr'         => $inline,
        'th'         => $inline + [ 'colspan' => true, 'rowspan' => true ],
        'td'         => $inline + [ 'colspan' => true, 'rowspan' => true ],
        'a'          => $inline + [ 'href' => true, 'title' => true ],
        'img'        => $inline + [ 'src' => true, 'alt' => true, 'width' => true, 'height' => true ],
    ];

    return apply_filters( 'hw_pdf_allowed_html', $tags );
}
