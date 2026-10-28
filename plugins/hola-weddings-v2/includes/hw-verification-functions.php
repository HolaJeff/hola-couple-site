<?php

use HW\Auth\EmailVerificationController;
use HW\Auth\EmailVerificationService;

/**
 * Shared entry point to the email verification service.
 *
 * Templates and controllers call these rather than reading the user meta
 * directly, so the administrator bypass and the legacy grandfathering rule stay
 * in exactly one place.
 */

/**
 * return the verification service
 *
 * @return EmailVerificationService
 */
function hw_email_verification(){

    return EmailVerificationController::getInstance()->getService();
}

/**
 * return whether the user's email address has been proven
 *
 * Defaults to the current user. Administrators and accounts that predate the
 * feature always answer true.
 *
 * @param int|null $user_id
 * @return bool
 */
function hw_user_email_is_verified( $user_id = null ){

    $user_id = is_null( $user_id ) ? get_current_user_id() : (int) $user_id;

    return hw_email_verification()->isVerified( $user_id );
}

/**
 * return whether the user still has to confirm their address
 *
 * @param int|null $user_id
 * @return bool
 */
function hw_user_requires_email_verification( $user_id = null ){

    $user_id = is_null( $user_id ) ? get_current_user_id() : (int) $user_id;

    return hw_email_verification()->requiresVerification( $user_id );
}

/**
 * return whether the current user may use the customer-only parts of the account
 *
 * The one check the account area gates on.
 *
 * @param int|null $user_id
 * @return bool
 */
function hw_can_access_verified_account_features( $user_id = null ){

    $user_id = is_null( $user_id ) ? get_current_user_id() : (int) $user_id;

    return $user_id > 0 && hw_email_verification()->isVerified( $user_id );
}

/**
 * return the verification screen url
 *
 * @return string
 */
function hw_get_verify_email_url(){

    return home_url( '/account/verify-email/' );
}

/**
 * return what the frontend needs to show the verification step itself
 *
 * Returned by the sign-in and sign-up endpoints so the auth modal can swap to
 * its verification pane instead of sending the visitor to a whole other page.
 * The nonce is included because the modal is rendered while logged out, long
 * before the account it belongs to is known.
 *
 * @param int|null $user_id
 * @return array{required:bool,email?:string,nonce?:string}
 */
function hw_get_verification_payload( $user_id = null ){

    $user_id = is_null( $user_id ) ? get_current_user_id() : (int) $user_id;

    if ( $user_id <= 0 || hw_user_email_is_verified( $user_id ) ) {

        return [ 'required' => false ];
    }

    $user = get_userdata( $user_id );

    return [
        'required' => true,
        'email'    => $user ? $user->user_email : '',
        'nonce'    => wp_create_nonce( \HW\Auth\EmailVerificationController::RESEND_ACTION . '_' . $user_id ),
    ];
}
