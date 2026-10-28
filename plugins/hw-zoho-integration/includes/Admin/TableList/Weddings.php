<?php

namespace HWZoho\Admin\TableList;
use \HWZoho\API\CRM\Modules\Potentials;

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Weddings extends \WP_List_Table{

    protected $pagination_info;

    /**
     * Add columns
     *
     * @return array
     */
    function get_columns(){
        
        $columns = array(
            'id' => 'Record ID',
            'Deal_Name' => 'Name',
            'options' => 'Options',
        );
        
        return $columns;
    }

    /**
     * display columns content
     *
     * @param object $item
     * @param string $column_name
     * @return string
     */
    public function column_default( $item, $column_name ) {
        switch( $column_name ) {
            case 'options':
                ?>
				<a href="<?= network_admin_url('admin.php?page=zoho-import-potential&id=' . $item->getKeyValue('id')); ?>" target="_blank">
					<span class="dashicons dashicons-database-import"></span>	
					<?= __('Import Wedding', 'hw-zoho') ?>
				</a>
				<?php
            default:
                return $item->getKeyValue($column_name);
        }
    }

    /**
     * Prepare items
     *
     * @return void
     */
    public function prepare_items(){

        $args = [];
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);

        // set page
        if(isset($_GET['paged']) && $_GET['paged'] > 0){
            $args['page'] = (int)$_GET['paged'];
        }

		if(isset($_GET['s']) && !empty($_GET['s'])){
			$args['s'] = $_GET['s'];
		}

        $responseWrapper =  Potentials::getRecords($args);
        $records = $responseWrapper->getData();
        $pagination_info = $responseWrapper->getInfo();

        $this->items = $records;
        $this->pagination_info = $pagination_info;

        // Set the pagination
        $this->set_pagination_args( [
            'total_items' => $pagination_info->getCount(),
            'per_page' => $pagination_info->getPerPage(),
			'count' => $pagination_info->getCount()
            //'total_pages' => ceil( $count / $per_page )
        ] );
    }

    /**
	 * Gets the current page number.
	 *
	 * @since 3.1.0
	 *
	 * @return int
	 */
	public function get_pagenum() {
		$pagenum = isset( $_REQUEST['paged'] ) ? absint( $_REQUEST['paged'] ) : 0;

		return max( 1, $pagenum );
	}

    /**
     * pagination
     *
     * @param string $which
     * @return void
     */
    protected function pagination( $which ) {
		if ( empty( $this->_pagination_args ) ) {
			return;
		}

		$current              = $this->get_pagenum();
		$total_items     = $this->_pagination_args['total_items'];
		$total_pages     = $this->_pagination_args['total_pages'];
		$per_page     = $this->_pagination_args['per_page'];
		$min_limit = 1;

		if($current > 1){
			$min_limit = $min_limit + ( ($current - 1) * $per_page);
		}

		$infinite_scroll = false;
		if ( isset( $this->_pagination_args['infinite_scroll'] ) ) {
			$infinite_scroll = $this->_pagination_args['infinite_scroll'];
		}

		if ( 'top' === $which && $total_pages > 1 ) {
			$this->screen->render_screen_reader_content( 'heading_pagination' );
		}

		$output = '<span class="displaying-num">Displaying ' . sprintf(
			/* translators: %s: Number of items. */
			_n( '%s - %s item', '%s - %s items', $total_items ),
			number_format_i18n( $min_limit ),
			number_format_i18n( ($per_page * ( $current - 1)) + $total_items )
		) . '</span>';

		$removable_query_args = wp_removable_query_args();

		$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );

		$current_url = remove_query_arg( $removable_query_args, $current_url );

		$page_links = array();

		$total_pages_before = '<span class="paging-input">';
		$total_pages_after  = '</span></span>';

		$disable_prev  = false;
		$disable_next  = false;

		if ( 1 == $current ) {
			$disable_prev  = true;
		}
		if ( !$this->pagination_info->getMoreRecords() ) {
			$disable_next = true;
		}

		if ( $disable_prev ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='prev-page button' href='%s'>" .
					"<span class='screen-reader-text'>%s</span>" .
					"<span aria-hidden='true'>%s</span>" .
				'</a>',
				esc_url( add_query_arg( 'paged', max( 1, $current - 1 ), $current_url ) ),
				/* translators: Hidden accessibility text. */
				__( 'Previous page' ),
				'&lsaquo;'
			);
		}

		if ( 'bottom' === $which ) {
			$html_current_page  = $current;
			$total_pages_before = sprintf(
				'<span class="screen-reader-text">%s</span>' .
				'<span id="table-paging" class="paging-input">' .
				'<span class="tablenav-paging-text">',
				/* translators: Hidden accessibility text. */
				__( 'Current Page' )
			);
		} else {
			$html_current_page = sprintf(
				'<label for="current-page-selector" class="screen-reader-text">%s</label>' .
				"<input class='current-page' id='current-page-selector' type='text'
					name='paged' value='%s' size='%d' aria-describedby='table-paging' />" .
				"<span class='tablenav-paging-text'>",
				/* translators: Hidden accessibility text. */
				__( 'Current Page' ),
				$current,
				strlen( $total_pages )
			);
		}

		$html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );

		$page_links[] = $total_pages_before . sprintf(
			/* translators: 1: Current page, 2: Total pages. */
			_x( '%1$s', 'paging' ),
			$html_current_page,
		) . $total_pages_after;

		if ( $disable_next ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='next-page button' href='%s'>" .
					"<span class='screen-reader-text'>%s</span>" .
					"<span aria-hidden='true'>%s</span>" .
				'</a>',
				esc_url( add_query_arg( 'paged', $current + 1 , $current_url ) ),
				/* translators: Hidden accessibility text. */
				__( 'Next page' ),
				'&rsaquo;'
			);
		}

		$pagination_links_class = 'pagination-links';
		if ( ! empty( $infinite_scroll ) ) {
			$pagination_links_class .= ' hide-if-js';
		}
		$output .= "\n<span class='$pagination_links_class'>" . implode( "\n", $page_links ) . '</span>';

		$this->_pagination = "<div class='tablenav-pages'>$output</div>";

		echo $this->_pagination;
	}
}