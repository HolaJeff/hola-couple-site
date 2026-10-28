<?php

/**
 * Get the count of notices added, either for all notices (default) or for one.
 * particular notice type specified by $notice_type.
 *
 * @since  1.0
 * @param  string $notice_type Optional. The name of the notice type - either error, success or notice.
 * @return int
 */
function hw_notice_count( $notice_type = '' ) {

	$notice_count = 0;
	$all_notices  = hw()->session->get( 'hw_notices', array() );

	if ( isset( $all_notices[ $notice_type ] ) ) {

		$notice_count = count( $all_notices[ $notice_type ] );

	} elseif ( empty( $notice_type ) ) {

		foreach ( $all_notices as $notices ) {
			$notice_count += count( $notices );
		}
	}

	return $notice_count;
}

/**
 * Check if a notice has already been added.
 *
 * @since  1.0
 * @param  string $message The text to display in the notice.
 * @param  string $notice_type Optional. The name of the notice type - either error, success or notice.
 * @return bool
 */
function hw_has_notice( $message, $notice_type = 'success' ) {

	$notices = hw()->session->get( 'hw_notices', array() );
	$notices = isset( $notices[ $notice_type ] ) ? $notices[ $notice_type ] : array();
	return array_search( $message, wp_list_pluck( $notices, 'notice' ), true ) !== false;
}

/**
 * Add and store a notice.
 *
 * @since 1.0
 * @param string $message     The text to display in the notice.
 * @param string $notice_type Optional. The name of the notice type - either error, success or notice.
 * @param array  $data        Optional notice data.
 */
function hw_add_notice( $message, $notice_type = 'success', $data = array() ) {
	
	$notices = hw()->session->get( 'hw_notices', array() );

	if ( ! empty( $message ) ) {
		$notices[ $notice_type ][] = array(
			'notice' => $message,
			'data'   => $data,
		);
	}

	hw()->session->set( 'hw_notices', $notices );
}

/**
 * Unset all notices.
 *
 * @since 1.0
 */
function hw_clear_notices() {
	
	hw()->session->set( 'hw_notices', null );
}

/**
 * Prints messages and errors which are stored in the session, then clears them.
 *
 * @since 1.0
 * @param bool $return true to return rather than echo. @since 3.5.0.
 * @return string|null
 */
function hw_print_notices( $return = false ) {

	$all_notices  = hw()->session->get( 'hw_notices', array() );
    $notice_types = array( 'error', 'success', 'notice' );
    
	// Buffer output.
	ob_start();

	foreach ( $notice_types as $notice_type ) {
		if ( hw_notice_count( $notice_type ) > 0 ) {

            $notices = $all_notices[$notice_type];
            
            switch($notice_type){
                case 'error':
                    $notice_type = 'danger';
                    break;
            }
            
            ?>
            <div class="alert alert-<?= $notice_type ?> is-dismissible" role="alert">
                <?php foreach ( $notices as $notice ) : ?>
                    <div>
                        <?= $notice['notice']; ?>
				</div>
                <?php endforeach; ?>
            </div>
            <?php
		}
	}

	hw_clear_notices();

	$notices = ob_get_clean();

	if ( $return ) {
		return $notices;
	}

	echo $notices;
}