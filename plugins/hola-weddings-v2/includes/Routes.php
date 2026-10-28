<?php

namespace HW;

/**
 * Defined fixed routes
 */
class Routes{
    
    use \HW\Traits\Singleton;
    
    /**
     * Allowed routes
     *
     * @var array
     */
    private $allowed_views = [
        'booking',
        'account',
        'booking-confirmation',
        'pos',
        'invoice-payment-confirmation'
    ];
    
    /**
     * Add hooks
     *
     * @return void
     */
    public function __construct(){
        
        add_action( 'init', [ $this, 'add_routes' ] );
        
        add_filter( 'template_include', [ $this, 'route' ] );
        add_filter( 'query_vars', [ $this, 'vars' ] );
        add_filter( 'body_class', [ $this, 'body_classes'] );
    }
    
    /**
     * Register routes
     *
     * @return void
     */
    public function add_routes(){

        // invoice
        add_rewrite_rule( 'invoice-payment/confirmation[/]?', 'index.php?hw_template=invoice-payment-confirmation', 'top' );
        add_rewrite_rule( 'invoice-payment[/]?', 'index.php?hw_template=pos', 'top' );

        // reservation
        add_rewrite_rule( 'account/reservation/([0-9]+)/pdf[/]?', 'index.php?hw_template=account&hw_view=reservation&post_type=quote&p=$matches[1]&hw_format=pdf', 'top' );
        add_rewrite_rule( 'account/reservation/([0-9]+)[/]?', 'index.php?hw_template=account&hw_view=reservation&post_type=quote&p=$matches[1]', 'top' );
        add_rewrite_rule( 'account/reservations[/]?', 'index.php?hw_template=account&hw_view=reservations', 'top' );
        
        // account
        add_rewrite_rule( 'account/profile[/]?', 'index.php?hw_template=account&hw_view=profile', 'top' );
        add_rewrite_rule( 'account[/]?', 'index.php?hw_template=account&hw_view=dashboard', 'top' );

        // booking
        add_rewrite_rule( 'booking-confirmation[/]?', 'index.php?hw_template=booking-confirmation', 'top' );
    }
    
    /**
     * Add query vars
     *
     * @param array $query_vars
     * @return array
     */
    public function vars( $query_vars ){
        
        $query_vars[] = 'hw_format';
        $query_vars[] = 'hw_template';
        $query_vars[] = 'hw_view';
        $query_vars[] = 'id';
        $query_vars[] = 'hw_action';
        
        return $query_vars;
    }
    
    /**
     * return view from query
     *
     * @return string|bool
     */
    public static function get_view(){
        
        return get_query_var('hw_view', false);
    }

    /**
     * return template from query
     *
     * @return void
     */
    public static function get_template(){
        
        return get_query_var('hw_template', false);
    }
    
    /**
     * return the template
     *
     * @param string $template
     * @return string
     */
    public function route( $template ){
        
        $route = self::get_template();
        
        if ( $route && in_array($route, $this->allowed_views ) ) {
            
            switch($route){
                case 'account':
                    $template = hw_locate_template('templates/account.php');
                    break;
                case 'booking-confirmation':
                    $template = hw_locate_template('templates/booking-confirmation.php');
                    break;
                case 'pos':
                    $template = hw_locate_template('templates/pos.php');
                    break;
                case 'invoice-payment-confirmation':
                    $template = hw_locate_template('templates/invoice-payment-confirmation.php');
                    break;
            }
        }
        
        return $template;
    }
    
    /**
     * add body classes
     *
     * @param array $classes
     * @return array
     */
    public function body_classes($classes){
        
        $route = self::get_template();
        $view = self::get_view();
        
        if($route){
            $classes[] = 'hw-template-' . $route;
            $classes[] = 'hw-view-' . $view;
        }
        
        return $classes;
    }
}