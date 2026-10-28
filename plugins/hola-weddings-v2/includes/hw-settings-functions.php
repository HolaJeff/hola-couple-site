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
 * return additional notes description
 *
 * @return string
 */
function hw_get_additional_notes_description(){

    return hw_get_option('hw_additional_notes_description', '' );
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

    return  hw_evaluate_string_to_boolean( hw_get_option( 'hw_default_quote_availability', true ) );
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
 * returns if the booking form create account box is hidden
 *
 * @return boolean
 */
function hw_booking_form_hide_create_account(){

    return hw_get_option( 'hw_booking_form_hide_create_account', false );
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
/**
 * Configurable extra booking-form checkboxes (rendered after the T&C check).
 * Option hw_extra_checkboxes: one checkbox per line; a leading * makes it
 * required. e.g.
 *   *I accept the resort's group booking conditions
 *   Send me travel updates by email
 *
 * @return array [{index, label, required}]
 */
function hw_get_extra_checkboxes(){

    $raw = hw_get_option('hw_extra_checkboxes', '');
    $out = [];

    foreach(preg_split('/\r\n|\r|\n/', (string) $raw) as $line){

        $line = trim($line);
        if($line === ''){
            continue;
        }
        $required = strpos($line, '*') === 0;
        $out[] = [
            'index' => count($out),
            'label' => trim(ltrim($line, '*')),
            'required' => $required
        ];
    }

    return $out;
}

/**
 * The guest's answers to the extra checkboxes, labelled + timestamped for the
 * quote record (audit trail like hw_toc_accepted).
 *
 * @param array $answers posted extra_checkboxes[<index>] values
 * @return array
 */
function hw_extra_checkbox_answers($answers){

    $answers = is_array($answers) ? $answers : [];

    return array_map(function($cb) use ($answers){
        return [
            'label' => $cb['label'],
            'required' => $cb['required'],
            'accepted' => !empty($answers[$cb['index']]),
            'at' => (new \DateTime('NOW'))->format('Y-m-d H:i:s')
        ];
    }, hw_get_extra_checkboxes());
}

/**
 * Option hw_insurance_decline_text: the sentence a guest must tick when they book
 * without travel protection. Empty = the acknowledgement is off.
 *
 * @return string
 */
function hw_get_insurance_decline_text(){

    return trim((string) hw_get_option('hw_insurance_decline_text', ''));
}

/**
 * Option hw_bed_preferences: one bed arrangement per line, offered to the guest as
 * a request (never a guarantee). Empty = the question is not asked.
 *
 * @return array of strings
 */
function hw_get_bed_preferences(){

    $out = [];
    foreach(preg_split('/\r\n|\r|\n/', (string) hw_get_option('hw_bed_preferences', '')) as $line){

        $line = trim($line);
        if($line !== ''){
            $out[] = $line;
        }
    }
    return $out;
}

/**
 * Option hw_transfer_optional: when on (and the site sells transfers at a price),
 * the guest chooses whether to add airport transfers instead of always paying them.
 *
 * @return bool
 */
function hw_is_transfer_optional(){

    return hw_is_transfer_enabled() && hw_get_service_fee() > 0 && intval(hw_get_option('hw_transfer_optional', 0)) == 1;
}

/**
 * Does the guest submitting this booking want the transfers? Always true unless the
 * site made them optional and the guest unticked the box.
 *
 * @return bool
 */
function hw_guest_wants_transfer(){

    if(!hw_is_transfer_optional()){
        return true;
    }
    return !isset($_POST['transfer_opt_in']) || $_POST['transfer_opt_in'] === '1';
}

/*
 * Login / Sign Up — Google
 *
 * Site-level options live in Wedding Settings → Login/Sign Up.
 * The OAuth credentials are network-level (Network Settings → Google) so one
 * Google client covers every wedding site, and can be overridden by the
 * HW_GOOGLE_CLIENT_ID / HW_GOOGLE_CLIENT_SECRET constants in wp-config.php.
 */

/**
 * return whether the Google button is toggled on for this site
 *
 * On by default: sites that never touched the setting get the button, while an
 * explicit opt-out is still respected ( the saved option wins over the default ).
 * Credentials are checked separately by hw_is_google_login_configured().
 *
 * @return boolean
 */
function hw_is_google_login_enabled(){

    return (bool) hw_get_option( 'hw_enable_google_login', 1 );
}

/**
 * return the Google button label
 *
 * @return string
 */
function hw_get_google_login_button_label(){

    $value = hw_get_option( 'hw_google_login_button_label', '' );

    if( empty( trim( (string) $value ) ) ){

        $value = __( 'Continue with Google', 'hola-weddings' );
    }

    return $value;
}

/**
 * return whether Google may create brand new accounts, as opposed to only
 * signing in people who already have one
 *
 * @return boolean
 */
function hw_google_signup_allowed(){

    return (bool) hw_get_option( 'hw_google_allow_signup', 1 );
}
