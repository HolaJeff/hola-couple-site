<?php

/**
 * add minute schedule
 */
add_filter( 'cron_schedules', function( $schedules ) { 
    $schedules['minute'] = array(
        'interval' => 60,
        'display'  => esc_html__( 'Every Minute' ), );
    return $schedules;
});

/**
 * hook the cron job function
 */
add_filter( 'hw_cron_job_classic-vacations_update_pending_transactions',  'hw_cv_review_pending_transactions', 10, 3 );