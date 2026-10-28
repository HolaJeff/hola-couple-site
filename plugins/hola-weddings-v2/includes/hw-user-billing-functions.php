<?php

use HW\Models\CRM\BillingInformation;

/**
 * Billing fields stored as user meta.
 *
 * The billing name (first/last) is a saved "payer" name that may legitimately
 * differ from the account identity (e.g. paying for someone else). The account
 * email + name on the WP user record remain the login identity and are never
 * written here.
 */
const HW_USER_BILLING_META = [
    'first_name' => 'hw_billing_first_name',
    'last_name'  => 'hw_billing_last_name',
    'phone'      => 'hw_billing_phone',
    'address'    => 'hw_billing_address',
    'city'       => 'hw_billing_city',
    'state'      => 'hw_billing_state',
    'country'    => 'hw_billing_country',
    'zip'        => 'hw_billing_zip',
];

/**
 * Build a BillingInformation object for a WP user.
 *
 * The billing (payer) name comes from user meta, falling back to the account
 * name when unset. Email is always the account email. Address and phone are
 * read from user meta.
 *
 * @param int $user_id
 * @return BillingInformation|false
 */
function hw_get_user_billing_information( int $user_id ) {

    $user = get_userdata( $user_id );

    if ( ! $user ) {
        return false;
    }

    $first_name = get_user_meta( $user_id, HW_USER_BILLING_META['first_name'], true );
    $last_name  = get_user_meta( $user_id, HW_USER_BILLING_META['last_name'],  true );

    return new BillingInformation( [
        'first_name' => $first_name !== '' ? $first_name : $user->first_name,
        'last_name'  => $last_name  !== '' ? $last_name  : $user->last_name,
        'email'      => $user->user_email,
        'phone'      => get_user_meta( $user_id, HW_USER_BILLING_META['phone'],   true ),
        'address'    => get_user_meta( $user_id, HW_USER_BILLING_META['address'], true ),
        'city'       => get_user_meta( $user_id, HW_USER_BILLING_META['city'],    true ),
        'state'      => get_user_meta( $user_id, HW_USER_BILLING_META['state'],   true ),
        'country'    => get_user_meta( $user_id, HW_USER_BILLING_META['country'], true ),
        'zip'        => get_user_meta( $user_id, HW_USER_BILLING_META['zip'],     true ),
    ] );
}

/**
 * Persist a user's saved billing default from a BillingInformation object.
 *
 * Writes the billing (payer) name and the address/phone. The account email and
 * account name on the WP user record are never touched — they are the identity.
 *
 * @param int                $user_id
 * @param BillingInformation $billing
 * @return void
 */
function hw_update_user_billing_information( int $user_id, BillingInformation $billing ): void {

    if ( ! $user_id ) {
        return;
    }

    update_user_meta( $user_id, HW_USER_BILLING_META['first_name'], sanitize_text_field( $billing->getFirstName() ) );
    update_user_meta( $user_id, HW_USER_BILLING_META['last_name'],  sanitize_text_field( $billing->getLastName() ) );
    update_user_meta( $user_id, HW_USER_BILLING_META['phone'],      sanitize_text_field( $billing->getPhone() ) );
    update_user_meta( $user_id, HW_USER_BILLING_META['address'],    sanitize_text_field( $billing->getAddress() ) );
    update_user_meta( $user_id, HW_USER_BILLING_META['city'],       sanitize_text_field( $billing->getCity() ) );
    update_user_meta( $user_id, HW_USER_BILLING_META['state'],      sanitize_text_field( $billing->getState() ) );
    update_user_meta( $user_id, HW_USER_BILLING_META['country'],    sanitize_text_field( $billing->getCountry() ) );
    update_user_meta( $user_id, HW_USER_BILLING_META['zip'],        sanitize_text_field( $billing->getPostalCode() ) );
}
