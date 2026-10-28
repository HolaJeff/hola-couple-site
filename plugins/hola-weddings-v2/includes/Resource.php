<?php

namespace HW;

abstract class Resource{
    
    protected static $instance;
    
    public static function getInstance(){
        
        if(!isset(self::$instance)){
            
            self::$instance = new static(); 
        }
        
        return self::$instance;
    }
    
    public function __construct(){
        
        add_action( 'admin_notices', [ $this, 'print_notices' ] );
        
        $this->init();
    }
    
    abstract public function init();
    
    public function print_notices(){
		
		if(empty($this->notices)){
			
			return;
		}
		
		foreach( $this->notices as $notice ){
			
			?>
			<div class="notice notice-<?php echo $notice['type'] ?> is-dismissible">
				<p><?php echo $notice['message'] ?></p>
			</div>
			<?php
		}
		
		$this->notices = [];
	}
	
	protected function add_notice( $type = 'info', $message = ''){
		
		$this->notices[] = [
			'type' => $type,
			'message' => $message
		];
	}
}