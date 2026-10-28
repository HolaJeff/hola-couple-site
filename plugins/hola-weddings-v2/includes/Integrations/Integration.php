<?php

namespace HW\Integrations;

use HW\Traits\Singleton;

abstract class Integration{

    use Singleton;

    protected $templates_folder = 'templates';

    protected $template_helper;

    /**
     * constructor
     */
    public function __construct(){
        
        $this->template_helper = new TemplateHelper($this, $this->templates_folder);
    }

    /**
     * get the template
     *
     * @param string $template_name
     * @param array $args
     * @return HW\Integrations\TemplateHelper;
     */
    public function get_template_helper(){
        
        return $this->template_helper;
    }
}