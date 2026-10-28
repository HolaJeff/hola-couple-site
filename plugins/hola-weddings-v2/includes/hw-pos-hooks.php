<?php

use HW\Controllers\POS;
use HW\Enums\TransactionStatus;
use HW\Integrations\ClassicVacations\Payment\ClassicVacationGateway;
use HW\Payment\Gateway\PaymentGatewaySimulator;

/**
 * Edit page title according to account templates
 */
add_filter( 'document_title_parts', function($title){

    if(hw_is_pos()){

        $title['title'] = __('Invoice Payment', 'hola-weddings');
    }

    return $title;
}, 10, 1);


/**
 * Print notices
 */
add_action('hw_pos_before', function(){

    hw_print_notices();
});

/**
 * review classic vacations pending transactions
 *
 * @param \HW\Models\Transaction $transaction
 * @param \HW\Utils\Logger $logger
 * @return bool
 */
function hw_simulator_payment_gateway_review_pending_transactions($keep_status, $transactionModel, $logger){

    // simply set this to confirmed
    $transactionModel->setStatus(TransactionStatus::CONFIRMED);

    $logger->debug(sprintf('Simulator Payment Gateway: Status updated to %s', TransactionStatus::CONFIRMED->value), 'Cron Job hw_simulator_payment_gateway_review_pending_transactions' );
    $transactionModel->addNote(sprintf('Status updated to %s', TransactionStatus::CONFIRMED->value) , 'hw_cv_review_pending_transactions');

    return $keep_status;
}
add_filter( 'hw_cron_job_payment-gateway-simulator_update_pending_transactions', 'hw_simulator_payment_gateway_review_pending_transactions', 10, 3 );

/**
 * register default payment gateways
 *
 * @param array $gateways
 * @return void
 */
function hw_register_default_payment_gateways(array $gateways) {

    $gateways[] = new PaymentGatewaySimulator();
    $gateways[] = new ClassicVacationGateway();

    return $gateways;
}
add_filter('hw_payment_gateways', 'hw_register_default_payment_gateways', 10, 1 );