<?php

namespace HW\Integrations\ClassicVacations;

use HW\Factories\LoggerFactory;
use HW\Integrations\ClassicVacations\API\Auth\TokenStore;
use HW\Integrations\ClassicVacations\CLI\Auth;
use HW\Integrations\Integration;
use HW\Utils\Logger;

define( 'CLASSICV_PATH', __DIR__ );
define( 'CLASSICV_URL' , plugin_dir_url( __FILE__ ) );

// instantiate the loader
$loader = \Psr4AutoloaderClass::getInstance();
  
// register the base directories for the namespace prefix
$loader->addNamespace( 'HW\Integrations\ClassicVacations', CLASSICV_PATH . '/src' );

require_once( 'functions.php' );
require_once( 'hooks.php' );
require_once( 'cron-functions.php' );
require_once( 'cron-hooks.php' );

class App extends Integration{

    public $logger;

    public function __construct(){

        parent::__construct();

        $this->logger = ( LoggerFactory::make( CLASSICV_PATH . '/logs' ) )->setLevel(hw_get_cv_debug_level());

        add_action( 'init', [ $this, 'add_routes' ], 10 );
        add_action( 'wp', [ $this, 'process_authorization' ], 11);

        add_filter( 'query_vars', [ $this, 'vars' ] );

        if(is_admin()){

            Admin::getInstance();
        }

        if(class_exists( 'WP_CLI' )){
            
            \WP_CLI::add_command( 'classic-vacations auth-check', [ Auth::class, 'checkTokenHealth' ] );
        }
    }

    /**
     * process authorization callback
     *
     * @return void
     */
    public function process_authorization(){
       
        if(get_query_var('hw_cv_action') == 'process-cv-oauth2'){

            if(!is_user_logged_in() || !current_user_can('manage_network')){
                http_response_code( 401 );
                die('Unauthorized');
            }

            if(isset($_GET['code']) && !empty($_GET['code']) ){
                
                $client_id = get_network_option(null, 'hw_classic_vacation_client_id', '');
                $client_secret = get_network_option(null, 'hw_classic_vacation_client_secret', '');
                $redirect = get_network_option(null, 'hw_classic_vacation_client_redirect_uri', '');
                $grant_token = $_GET['code'];
                
                // TODO Remove once we are secure the new process is working

                /*$response = wp_remote_request('https://connect.classicvacations.com/AuthorizationServer/OAuth/Token',[
                    'method' => 'POST',
                    'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded'
                    ],
                    'body' => [
                        'code' => $grant_token,
                        'grant_type' => 'authorization_code',
                        'redirect_uri' => $redirect,
                        'client_id' => $client_id,
                        'client_secret' => $client_secret
                    ]
                ]);

                if(is_wp_error($response)){
                    hw_add_notice('Ops there was an error with the token request.', 'error');
                }

                $response_data = json_decode(wp_remote_retrieve_body($response), true);
                $response_data['grant_token'] = $grant_token;*/

                // use the token store to create a new token without constructor
                $tokenStore = TokenStore::getInstance();
                $token = $tokenStore->createToken();

                try{
                    // set the variables and generate the token
                    $token->setGrantToken($grant_token)
                        ->setRedirectURL($redirect)
                        ->setClientId($client_id)
                        ->setClientSecret($client_secret)
                        ->generateToken();

                    hw_add_notice('Authorized correctly!');

                }catch(\Exception $e){

                    hw_add_notice('Error: ' . $e->getMessage());
                }

                wp_safe_redirect(network_admin_url( 'admin.php?page=classic-vacations-connection' ));
            }
        }
    }

    /**
     * Add query vars
     *
     * @param array $query_vars
     * @return array
     */
    public function vars( $query_vars ){
        
        $query_vars[] = 'hw_cv_action';
        
        return $query_vars;
    }

    /**
     * add the endpoint to process the grant token
     *
     * @return void
     */
    public function add_routes(){

        // OAuth
        add_rewrite_rule( 'integrations/classicvacations/oauth/process[/]?', 'index.php?hw_cv_action=process-cv-oauth2', 'top' );
    }

    /**
     * Return the logger
     *
     * @return HW\Utils\Logger;
     */
    public function getLogger(){
        
        return $this->logger;
    }
}