<?php

namespace HW\Admin;

class Menus{
    
    use \HW\Traits\Singleton;
    
    public function __construct(){
        
        add_action( 'admin_head-nav-menus.php', array( $this, 'add_nav_menu_meta_boxes' ) );
    }
    
    public function add_nav_menu_meta_boxes() {
		add_meta_box( 'hw_endpoints_nav_link', __( 'Hola Weddings endpoints', 'hola-weddings' ), [ $this, 'nav_menu_links' ], 'nav-menus', 'side', 'low' );
	}
    
    public function nav_menu_links() {
		// Get items from account menu.
		$endpoints = array(
            'home' => [ 'label' => __( 'Home', 'hola-weddings' ), 'endpoint' => ''],
            'travel-info' => [ 'label' => __( 'Travel Info', 'hola-weddings' ), 'endpoint' => 'travel-info'],
            'book' => [ 'label' => __( 'Book', 'hola-weddings' ) , 'endpoint' => 'book']
        );
        
		?>
		<div id="posttype-hw-endpoints" class="posttypediv">
			<div id="tabs-panel-hw-endpoints" class="tabs-panel tabs-panel-active">
				<ul id="hw-checklist" class="categorychecklist form-no-clear">
					<?php
					$i = -1;
					foreach ( $endpoints as $key => $item ) :
						?>
						<li>
							<label class="menu-item-title">
								<input type="checkbox" class="menu-item-checkbox" name="menu-item[<?php echo esc_attr( $i ); ?>][menu-item-object-id]" value="<?php echo esc_attr( $i ); ?>" /> <?php echo esc_html( $item['label'] ); ?>
							</label>
							<input type="hidden" class="menu-item-type" name="menu-item[<?php echo esc_attr( $i ); ?>][menu-item-type]" value="custom" />
							<input type="hidden" class="menu-item-title" name="menu-item[<?php echo esc_attr( $i ); ?>][menu-item-title]" value="<?php echo esc_attr( $item['label'] ); ?>" />
							<input type="hidden" class="menu-item-url" name="menu-item[<?php echo esc_attr( $i ); ?>][menu-item-url]" value="<?php echo esc_url( home_url( $item['endpoint']  )); ?>" />
							<input type="hidden" class="menu-item-classes" name="menu-item[<?php echo esc_attr( $i ); ?>][menu-item-classes]" />
						</li>
						<?php
						$i--;
					endforeach;
					?>
				</ul>
			</div>
			<p class="button-controls" data-items-type="posttype-hw-endpoints">
                <span class="list-controls hide-if-no-js">
                    <input type="checkbox" id="hw-endpoint-tab" class="select-all">
                    <label for="hw-endpoint-tab">Select All</label>
                </span>
				<span class="add-to-menu">
					<button type="submit" class="button-secondary submit-add-to-menu right" value="<?php esc_attr_e( 'Add to menu', 'hola-weddings' ); ?>" name="add-post-type-menu-item" id="submit-posttype-hw-endpoints">
                        <?php esc_html_e( 'Add to menu', 'hola-weddings' ); ?>
                    </button>
					<span class="spinner"></span>
				</span>
			</p>
		</div>
		<?php
	}
}