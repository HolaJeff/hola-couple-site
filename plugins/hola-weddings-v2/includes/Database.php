<?php

namespace HW;

class Database{
    
    use Traits\Singleton;
    
    protected $_current_version;
    
    public function __construct(){
        
        $this->do_updates();
    }
    
    /**
     * check for updates
     *
     * @return void
     */
    public function do_updates(){
        
        global $wpdb;

        $current_version = $this->get_db_version();
        
        if(version_compare('1.0.2', $current_version, '>')){
            
            $table_name = $wpdb->prefix . 'rooms_inventory';
            
            if( $wpdb->get_var( "show tables like '{$table_name}'" ) != $table_name ) {

                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                
                // id mediumint(9) NOT NULL AUTO_INCREMENT,
                
                $sql = "CREATE TABLE IF NOT EXISTS " . $table_name . " (
                    room_id int(11) UNSIGNED NOT NULL,
                    quote_id int(11) UNSIGNED NOT NULL,
                    date DATE NOT NULL,
                    INDEX (room_id, date),
                    INDEX (quote_id)
                );";
        
                dbDelta( $sql );
        
                $this->update_db_version('1.0.2');
            }
        }

        if(version_compare('1.0.3', $current_version, '>')){
            
            $table_name = $wpdb->prefix . 'rooms_inventory';
            
            $wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN room_block_id int(11) NOT NULL FIRST;" );            
            $wpdb->query( "DROP INDEX `room_id` ON {$table_name};" );
            //$wpdb->query( "ALTER TABLE {$table_name} ADD PRIMARY KEY(`room_block_id`, `quote_id`, `date`);" );
            $wpdb->query( "ALTER TABLE {$table_name} ADD INDEX(`room_block_id`, `date`);" );
    
            $this->update_db_version('1.0.3');
        }
    }

    /**
     * return the db version
     *
     * @return string
     */
    public function get_db_version(){
        return get_option('hw_db_version', '1.0.0');
    }
    
    /**
     * update database version
     *
     * @param string $version
     * @return void
     */
    protected function update_db_version($version){
        
        update_option('hw_db_version', $version);
        $this->_current_version = $version;
    }
}