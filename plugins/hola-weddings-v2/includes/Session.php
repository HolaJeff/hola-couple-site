<?php

namespace HW;

class Session{
    
    use Traits\Singleton;
    
    protected $_data;

	protected $_session_id;

	protected $_session_timeout = '1800';
    
    /**
	 * Dirty when the session needs saving.
	 *
	 * @var bool $_dirty When something changes
	 */
	protected $_dirty = false;
    
    public function __construct(){
        
		$this->session_init();

        $this->_data = $this->get_session_data();
        register_shutdown_function([ $this, 'save_data' ]);
        add_action( 'shutdown', [ $this, 'save_data' ], 20 );
		add_action( 'wp_logout', [ $this, 'destroy_session' ] );
    }
    
    /**
	 * Magic get method.
	 *
	 * @param mixed $key Key to get.
	 * @return mixed
	 */
	public function __get( $key ) {
		return $this->get( $key );
	}

	/**
	 * Magic set method.
	 *
	 * @param mixed $key Key to set.
	 * @param mixed $value Value to set.
	 */
	public function __set( $key, $value ) {
		$this->set( $key, $value );
	}
    
    /**
	 * Magic isset method.
	 *
	 * @param mixed $key Key to check.
	 * @return bool
	 */
	public function __isset( $key ) {
        
		return isset( $this->_data[ sanitize_title( $key ) ] );
	}

	/**
	 * Magic unset method.
	 *
	 * @param mixed $key Key to unset.
	 */
	public function __unset( $key ) {
        
		if ( isset( $this->_data[ $key ] ) ) {
			unset( $this->_data[ $key ] );
			$this->_dirty = true;
		}
	}
    
    /**
	 * Get session data.
	 *
	 * @return array
	 */
	public function get_session_data() {
        
		return $this->get_session( );
	}
    
	/**
	 * Get a session variable.
	 *
	 * @param string $key Key to get.
	 * @param mixed  $default used if the session variable isn't set.
	 * @return array|string value of session variable
	 */
	public function get( $key, $default = null ) {
		$key = sanitize_key( $key );
		return isset( $this->_data[ $key ] ) ? maybe_unserialize( $this->_data[ $key ] ) : $default;
	}
    
	/**
	 * init section
	 *
	 * @return string
	 */
    public function session_init() {

		if (!session_id()) {
			session_start();
		}

		$this->_session_id = session_id();
	}
    
	/**
	 * store the data into transient before shutdown
	 *
	 * @return void
	 */
    public function save_data(){
        
        if ( $this->_dirty ) {
           	
			set_transient( 'hw_session_' . $this->_session_id , $this->_data, $this->_session_timeout );
        }
    }
    
    /**
	 * Destroy all session data.
	 */
    public function destroy_session() {
		$this->delete_session();
		$this->forget_session();
	}
    
    /**
	 * Forget all session data without destroying it.
	 */
	public function forget_session() {

		$this->_data        = array();
		$this->_dirty       = false;
	}
    
    /**
	 * Delete the session from the cache and database.
	 *
	 * @param int $customer_id Customer ID.
	 */
	public function delete_session() {

		delete_transient( 'hw_session_' . $this->_session_id );
	}

    
    /**
	 * Set a session variable.
	 *
	 * @param string $key Key to set.
	 * @param mixed  $value Value to set.
	 */
	public function set( $key, $value ) {
		if ( $value !== $this->get( $key ) ) {
			$this->_data[ sanitize_key( $key ) ] = maybe_serialize( $value );
			$this->_dirty                        = true;
		}
	}
    
    /**
	 * Returns the session.
	 *
	 * @param string $customer_id Custo ID.
	 * @param mixed  $default Default session value.
	 * @return string|array
	 */
	public function get_session( ) {

		// Try to get it from the cache, it will return false if not present or if object cache not in use.
		$value = get_transient( 'hw_session_' . $this->_session_id );

        if ( is_null( $value ) ) {
			$value = [];
		}

		return maybe_unserialize( $value );
	}
}