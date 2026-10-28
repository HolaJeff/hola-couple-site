<?php

use HW\Email\Mailer;

/**
 * helper function to get the Mailer class instance
 *
 * @return Mailer
 */
function hw_get_mailer(){

    return Mailer::getInstance();
}