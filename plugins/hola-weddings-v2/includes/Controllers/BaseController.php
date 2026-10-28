<?php

namespace HW\Controllers;

abstract class BaseController{

    /**
     * constructor
     */
    public function __construct(){
        
        $this->add_hooks();
    }

    /**
     * register hooks
     *
     * @return void
     */
    public function add_hooks(){

        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('template_redirect', [$this, 'template_redirect']);
    }

    /**
     * wp init
     *
     * @return void
     */
    public function init(){}

    /**
     * enqueue scripts
     *
     * @return void
     */
    public function enqueue_scripts(){}

    /**
     * perform middleware checks on template_redirect hook
     *
     * @return void
     */
    public function template_redirect(){}
}