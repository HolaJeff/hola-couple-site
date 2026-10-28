<?php

namespace HW\Integrations;
use HW\Integrations\Integration;

class TemplateHelper{

    protected $templates_folder = 'templates';

    protected $dirname;

    /**
     * Constructor
     *
     * @param Integration $integration
     * @param string $templates_folder
     */
    public function __construct( Integration $integration, $templates_folder = '' ){
        
        $this->dirname = $this->get_dir($integration);

        if(!empty($templates_folder)){
            $this->templates_folder = $templates_folder;
        }
    }

    /**
     * display a template
     *
     * @param string $template_name
     * @param array $args
     * @return void
     */
    public function get_template($template_name, $args = []){

        if ( is_array( $args ) && !empty( $args ) ) :
            extract( $args );
        endif;
        
        $template_file = $this->dirname .'/'. $this->templates_folder . '/' . $template_name . '.php';
        
        if ( ! file_exists( $template_file ) ) :
            _doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> does not exist.', $template_file ), '1.0.0' );
            return;
        endif;
    
        include $template_file;
    }
    
    /**
     * return the directory name of the called class
     *
     * @return string
     */
    private function get_dir( $integration ) {
        
        $reflector = new \ReflectionClass(get_class($integration));
        return dirname($reflector->getFileName());
    }
}