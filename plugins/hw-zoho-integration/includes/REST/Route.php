<?php

namespace HWZoho\REST;

class Route{

    /**
     * constructor
     */
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Register routes
     *
     * @return void
     */
    public function register_routes(){}
}