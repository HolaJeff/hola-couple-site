<?php

namespace HW\Payment\Gateway;

use HW\Models\Transaction;
use HW\Payment\Utilities\Charge;

Interface PaymentGatewayInterface{

    /**
     * process payment
     *
     * @param Charge $charge
     * @param Transaction $transaction
     * @return boolean
     */
    public function process( Charge $charge, Transaction $transaction );

    /**
     * get gateway human name
     *
     * @return string
     */
    public function getName();

    /**
     * get gateway id
     *
     * @return string
     */
    public function getId();
}