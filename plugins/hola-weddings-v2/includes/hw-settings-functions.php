<?php

use HW\Quotes;

/**
 * return whether additional notes field is enabled
 *
 * @return boolean
 */
function hw_is_additional_notes_enabled(){

    return hw_get_option('hw_enable_additional_notes', false );
}

/**
 * return additional notes label
 *
 * @return string
 */
function hw_get_additional_notes_label(){

    $value = hw_get_option('hw_additional_notes_label', __('Additional Notes', 'hola-weddings') );

    if(empty($value)){
        $value = __('Additional Notes', 'hola-weddings');
    }

    return $value;
}

/**
 * return whether additional notes field is required
 *
 * @return boolean
 */
function hw_is_additional_notes_is_required(){

    return hw_get_option('hw_additional_notes_required', false );
}

/**
 * return the deposit increase date if set
 *
 * @return string|null
 */
function hw_get_deposit_increase_date(){

    return hw_get_option('hw_deposit_increase_date', null );
}

/**
 * return the deposit increase date if set
 *
 * @return int
 */
function hw_get_deposit_increase_amount(){

    return intval(hw_get_option('hw_deposit_increase_amount', 0 ));
}

/**
 * return default quote status
 *
 * @return bool
 */
function hw_force_quote_status(){

    return (bool) hw_get_option( 'hw_force_quote_status', 0 );
}

/**
 * return forced quote status
 *
 * @return string
 */
function hw_forced_quote_status(){

    return hw_get_option( 'hw_forced_quote_status', '' );
}

/**
 * return default quote status
 *
 * @return boolean
 */
function hw_get_default_availability(){

    return hw_get_option( 'hw_default_quote_availability', true );
}

/**
 * return the dresscode
 *
 * @return boolean
 */
function hw_get_dress_code(){

    return hw_get_option( 'hw_dress_code', '' );
}

/**
 * return hide login cta option
 *
 * @return boolean
 */
function hw_hide_form_login_cta(){

    return hw_get_option( 'hw_booking_form_hide_login_cta', false );
}

/**
 * return PoS debug level
 *
 * @return string
 */
function hw_get_pos_debug_level(){

    return hw_get_option( 'hw_pos_debug_level', 'info' );
}

/**
 * return invoice payment button action
 *
 * @return string
 */
function hw_get_invoice_payment_btn_action(){

    return hw_get_option( 'hw_invoice_payment_btn_action', 'payment-form' );
}

/**
 * return invoice payment custom message
 *
 * @return string
 */
function hw_invoice_payment_custom_message(){

    return hw_get_option( 'hw_invoice_payment_custom_message', '' );
}

/**
 * return the alternative email from the invoice payment message option
 *
 * @return bool
 */
function hw_invoice_payment_message_alternative_email(){

    return hw_get_option( 'hw_invoice_payment_message_alternative_email', '' );
}

/**
 * return additional events array
 *
 * @return array
 */
function hw_get_additional_events(){

    return hw_get_option( 'hw_additional_events', [] );
}