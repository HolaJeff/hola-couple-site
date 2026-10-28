<?php

namespace HWZoho\REST\Routes;

use HWZoho\REST\Operations\Import\Factory;
use HWZoho\REST\Operations\Import\Importer;
use HWZoho\REST\Route;

class Records extends Route{

    protected $allowed_post_types = [
        'invoices',
        'quote'
    ];

    /**
     * register routes
     *
     * @return void
     */
    public function register_routes(){

        $base = 'v1/zoho-plugin';

        register_rest_route( $base, '/records/(?P<post_type>[a-zA-Z0-9-]+)/', array(
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => [$this, 'process_create_update'],
            'permission_callback' => '__return_true',
            'args'                => [
                'post_type' => [
                    'required' => true,
                    'type' => 'string'
                ]
            ]
        ));

        register_rest_route( $base, '/records/(?P<post_type>[a-zA-Z0-9-]+)/(?P<id>[0-9]+)', array(
            'methods' => 'PUT',
            'callback' => [$this, 'process_create_update'],
            'permission_callback' => '__return_true',
            'args'                => [
                'post_type' => [
                    'required' => true,
                    'type' => 'string'
                ],
                'id' => [
                    'required' => true,
                    'type' => 'string'
                ],
            ]
        ));

        register_rest_route( $base, '/records/(?P<post_type>[a-zA-Z0-9-]+)/(?P<id>[0-9]+)/', array(
            'methods' => \WP_REST_Server::DELETABLE,
            'callback' => [$this, 'delete_record'],
            'args'                => [
                'post_type' => [
                    'required' => true,
                    'type' => 'string'
                ],
                'id' => [
                    'required' => true,
                    'type' => 'string'
                ],
            ]
        ));
    }

    /**
     * verify if the user was logged in with basic auth
     *
     * @return bool
     */
    public function permission_check(){
        
        //return is_user_logged_in();
        return true;
    }

    /**
     * process the POST/PUT request
     *
     * @param WP_REST_Request $request
     * @return json
     */
    public function process_create_update($request){

        $params = $request->get_params();
        $method = $request->get_method();

        $this->validate_post_type($params['post_type']);
        
        $importer = Factory::create($params['post_type']);
        $response = $method == 'POST' ? $importer->create($params) : $importer->update($params);

        if(is_wp_error($response)){

            return new \WP_REST_Response(
                [
                    'status' => 'ERROR',
                    'code' => $response->get_error_data()['code'], 
                    'message' => $response->get_error_message()
                ], 
                $response->get_error_code() 
            );
        }

        return new \WP_REST_Response(
            [
                'status' => 'SUCCESS', 
                'code' => $method == 'POST' ? 'RECORD_CREATED' : 'RECORD_UPDATED', 
                'message' => $method == 'POST' ? 'Record Created' : 'Record Updated',
                'data' => is_array($response) ? $response : []
            ],
            200
        );
    }
    
    /**
     * handle record deletion
     *
     * @param WP_Request $request
     * @return json
     */
    public function delete_record($request){

        $params = $request->get_params();

        $this->validate_post_type($params['post_type']);

        $importer = Factory::create($params['post_type']);
        $response = $importer->delete($params);

        if(is_wp_error($response)){

            return new \WP_REST_Response(
                [
                    'status' => 'ERROR',
                    'code' => $response->get_error_data()['code'], 
                    'message' => $response->get_error_message()
                ], 
                $response->get_error_code() 
            );
        }

 
        return new \WP_REST_Response(
            [
                'status' => 'SUCCESS', 
                'code' => 'RECORD_DELETED', 
                'message' => 'Record Deleted'
            ],
            200
        );
    }

    /**
     * validate post type
     *
     * @param string $post_type
     * @return WP_REST_Response
     */
    private function validate_post_type($post_type){

        // review allowed post types
        if(!in_array($post_type, $this->allowed_post_types)){
            return new \WP_REST_Response(
                [
                    'status' => 'ERROR',
                    'code' => 'INVALID_POST_TYPE', 
                    'message' => 'Invalid post type'
                ], 
                400
            );
        }
    }
}