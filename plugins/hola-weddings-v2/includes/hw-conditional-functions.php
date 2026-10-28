<?php

/**
 * determinate if on account page
 *
 * @return bool
 */
function hw_is_account(){

    $template = get_query_var('hw_template', false);

    return $template == 'account' ? true : false;
}

/**
 * return true ir on account reservation page
 *
 * @return bool
 */
function hw_is_reservation(){

    $template = get_query_var('hw_template', false);
    $view = get_query_var('hw_view', false);

    return $template == 'account' && $view == 'reservation' ? true : false;
}

/**
 * return true ir on account invoice page
 *
 * @return bool
 */
function hw_is_invoice(){

    $template = get_query_var('hw_template', false);
    $view = get_query_var('hw_view', false);

    return $template == 'account' && $view == 'reservation' ? true : false;
}

/**
 * return true if is point of sail
 *
 * @return bool
 */
function hw_is_pos(){

    $template = get_query_var('hw_template', false);

    return $template == 'pos' ? true : false;
}

/**
 * return true if is invoice payment confirmation
 *
 * @return bool
 */
function hw_is_invoice_payment_confirmation(){

    $template = get_query_var('hw_template', false);

    return $template == 'invoice-payment-confirmation' ? true : false;
}

/**
 * return tru if plugin home endpoint is displayed
 *
 * @return boolean
 */
function hw_is_home_endpoint(){

    return is_page_template( 'endpoints/home.php' );
}

/**
 * turn true if page template is set to Legacy booking form
 *
 * @return boolean
 */
function hw_is_legacy_booking_form(){

    return is_page_template( 'endpoints/book.php' );
}

/**
 * return true if page template is set to React booking form
 *
 * @return boolean
 */
function hw_is_react_booking_form(){

    return is_page_template( 'templates/booking-form.php' );
}