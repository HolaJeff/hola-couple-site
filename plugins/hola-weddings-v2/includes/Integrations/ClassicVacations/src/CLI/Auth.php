<?php

namespace HW\Integrations\ClassicVacations\CLI;

use HW\Email\Email;
use HW\Email\Mailer;
use HW\Integrations\ClassicVacations\API\Auth\TokenStore;
use HW\Integrations\ClassicVacations\API\Initializer;
use HW\Integrations\ClassicVacations\API\Logger;
use HW\Integrations\ClassicVacations\App;

class Auth{

    /**
     * check token health
     *
     * @return void
     */
    public function checkTokenHealth(){

        //Initializer::initialize();
        $logger = App::getInstance()->getLogger();

        // log beginning of task
        \WP_CLI::line('Classic Vacation Token Check begins');
        $logger->info('Auth Token check start', self::class . '::checkTokenHealth' );

        // get the token
        $store = TokenStore::getInstance();
        $token = $store->getToken();

        // try to obtain the token
        try{

            $token->generateToken();

        }catch(\Exception $e){

            $this->sendAdminEmail($e->getMessage());
            \WP_CLI::error($e->getMessage());
            $logger->error( 'WP-Cli Auth Token Check failed: ' . $e-> getMessage(), self::class . '::checkTokenHealth' );
            return;
        }

        // success
        $logger->info('WP-Cli Auth Token Check succeeded', self::class . '::checkTokenHealth' );
        \WP_CLI::success('success');
    }

    /**
     * send an email to admins about the error
     *
     * @param string $message
     * @return void
     */
    protected function sendAdminEmail($message){

        $logger = App::getInstance()->getLogger();
        $template_helper = App::getInstance()->get_template_helper();
        $mailer = new Mailer();

        $email = new Email();
        $email->setHeaders([
            'Content-Type: text/html; charset=UTF-8',
            'Reply-To: No-Reply <no-reply@holaweddings.com>',
            'From: Hola Couples Site <no-reply@holaweddings.com>'
        ])
        ->setRecipients([
            'jeff@holaweddings.com',
            'marcocariolato@live.com'
        ])
        ->setSubject('Classic Vacation token has expired');

        ob_start();
        $template_helper->get_template('emails/token-expired', ['message' => $message]);
        $body = ob_get_clean();

        $email->setBody($body);

        if( !$mailer->send($email)){
            
            $logger->error('Error sending admin email', self::class . '::sendAdminEmail' );
            \WP_CLI::error('Error sending admin email');
        }

        $logger->info('Admin email notification sent', self::class . '::sendAdminEmail' );
        \WP_CLI::line('Admin email notification sent');
    }
}