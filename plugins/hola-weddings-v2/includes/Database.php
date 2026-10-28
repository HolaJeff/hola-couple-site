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

        if(version_compare('1.0.4', $current_version, '>')){

            $this->grandfather_existing_users();

            $this->update_db_version('1.0.4');
        }

        if(version_compare('1.0.5', $current_version, '>')){

            $this->resync_quote_customer_meta();

            $this->update_db_version('1.0.5');
        }
    }

    /**
     * Seed every quote's billing meta fields from its original snapshot.
     *
     * Billing details used to live in hw_customer_data, with only some of them
     * copied out to their own meta keys — and only when the quote was created.
     * Editing a quote afterwards updated the snapshot and left those copies on
     * the old values, so hw_email could disagree with the billing email. That is
     * what decides whether a guest booking is handed to a newly verified
     * account, so the booking silently stopped matching.
     *
     * The fields are now the billing details outright and the snapshot is frozen
     * as a record of the original submission. This fills the fields in from that
     * snapshot once, including address, city and zip, which never had their own
     * keys before.
     *
     * The snapshot wins because it is what the editor actually wrote, so it holds
     * the current values. Quotes nobody edited already agree and are skipped.
     *
     * Quotes are per-site, so this runs once on each site in the network.
     *
     * @return int Number of quotes seeded.
     */
    protected function resync_quote_customer_meta(){

        $quotes = get_posts( [
            'post_type'        => 'quote',
            'post_status'      => 'any',
            'posts_per_page'   => -1,
            'fields'           => 'ids',
            'suppress_filters' => true,
        ] );

        $seeded = 0;

        foreach ( $quotes as $quote_id ) {

            $snapshot = get_post_meta( $quote_id, 'hw_customer_data', true );

            // Nothing authoritative to copy from.
            if ( ! is_array( $snapshot ) || empty( $snapshot ) ) {
                continue;
            }

            $billing = new \HW\Models\CRM\BillingInformation( $snapshot );

            $expected = [
                'first_name' => (string) $billing->getFirstName(),
                'last_name'  => (string) $billing->getLastName(),
                'email'      => (string) $billing->getEmail(),
                'phone'      => (string) $billing->getPhone(),
                'address'    => (string) $billing->getAddress(),
                'city'       => (string) $billing->getCity(),
                'state'      => (string) $billing->getState(),
                'country'    => (string) $billing->getCountry(),
                'zip'        => (string) $billing->getPostalCode(),
            ];

            $needs_seeding = false;

            foreach ( $expected as $property => $value ) {

                if ( (string) get_post_meta( $quote_id, \HW\Models\Quote::BILLING_META[ $property ], true ) !== $value ) {

                    $needs_seeding = true;
                    break;
                }
            }

            if ( ! $needs_seeding ) {
                continue;
            }

            hw_get_quote( $quote_id )->setBillingInformation( $billing );
            $seeded++;
        }

        return $seeded;
    }

    /**
     * Mark every account that predates email verification as already verified.
     *
     * Customers who booked before the feature shipped must not be locked out of
     * their reservations, and must never be mailed a verification link.
     *
     * The rollout timestamp is stamped network wide at boot, before any account
     * can be created under the new rules, so it cleanly separates "existed
     * already" from "registered since". That is what keeps this safe to re-run:
     * a user who signs up after rollout can never match the cut-off, and the
     * LEFT JOIN skips anyone who already carries the meta, so a second pass
     * cannot overwrite a genuine unverified state.
     *
     * Written as one statement rather than a user loop because wp_users is a
     * network-wide table and can be large.
     *
     * @return int Number of users grandfathered.
     */
    protected function grandfather_existing_users(){

        global $wpdb;

        // Database boots before the verification controller, so make sure the
        // cut-off is persisted before it is used to decide who is legacy.
        \HW\Auth\EmailVerificationService::bootstrapRollout();

        $meta_key = \HW\Auth\EmailVerificationService::META_VERIFIED_AT;
        $rollout  = \HW\Auth\EmailVerificationService::rolloutDate();

        $sql = $wpdb->prepare(
            "INSERT INTO {$wpdb->usermeta} ( user_id, meta_key, meta_value )
             SELECT u.ID, %s, %s
             FROM {$wpdb->users} u
             LEFT JOIN {$wpdb->usermeta} m
                    ON m.user_id = u.ID AND m.meta_key = %s
             WHERE m.umeta_id IS NULL
               AND u.user_registered < %s",
            $meta_key,
            $rollout,
            $meta_key,
            $rollout
        );

        $rows = $wpdb->query( $sql );

        // The rows went in behind WordPress's back. User meta is cached per user
        // id in the 'user_meta' group, so without an explicit flush this request
        // — and any persistent object cache — would keep answering "unverified"
        // for accounts that were just grandfathered.
        if ( $rows ) {

            if ( function_exists( 'wp_cache_supports' ) && wp_cache_supports( 'flush_group' ) ) {

                wp_cache_flush_group( 'user_meta' );

            } else {

                // Backends without group flushing: fall back to clearing the
                // affected users one by one.
                $ids = $wpdb->get_col( $wpdb->prepare(
                    "SELECT u.ID FROM {$wpdb->users} u WHERE u.user_registered < %s",
                    $rollout
                ) );

                foreach ( $ids as $id ) {
                    wp_cache_delete( (int) $id, 'user_meta' );
                }
            }
        }

        return is_numeric( $rows ) ? (int) $rows : 0;
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