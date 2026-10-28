<?php
/**
 * Plugin Name: Hola Weddings V2
 * Plugin URI:  https://mcweb.mx
 * Description: Hola Weddings V2 Plugin for wedding couple sites
 * Author:      Marco Cariolato
 * Author URI:  https://mcweb.mx
 * Version:     2.3.4.1
 * Text Domain: hola-wedding
 * Domain Path: languages
 *
 * You should have received a copy of the GNU General Public License
 * along with WPForms. If not, see <http://www.gnu.org/licenses/>.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Makes sure the plugin is defined before trying to use it
if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
    require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
}

// check for library plugin to be activated
if ( !is_plugin_active_for_network( 'hola-weddings-library/hola-weddings.php' ) ) {
    return;
}

define( 'HW_PLUGIN_FILE', __FILE__ );
define( 'HW_PLUGIN_PATH', __DIR__ );
define( 'HW_INCLUDES_PATH', HW_PLUGIN_PATH . '/includes' );
define( 'HW_ASSETS_PATH', HW_PLUGIN_PATH . '/assets' );
define( 'HW_TEMPLATE_PARTS', HW_PLUGIN_PATH . '/template-parts' );

define( 'HW_URL' , plugin_dir_url( __FILE__ ) );
define( 'HW_ASSETS_URL', HW_URL . 'assets/' );
define( 'HW_ASSET_DIST_URL', HW_ASSETS_URL . 'dist/' );
define( 'HW_VENDOR_URL', HW_ASSETS_URL . 'vendor/' );

if( !class_exists( 'Psr4AutoloaderClass' ) ){
    
    require_once( HW_PLUGIN_PATH . '/class-autoloader.php' );
}

// instantiate the loader
$loader = Psr4AutoloaderClass::getInstance();

// register the autoloader
$loader->register();
  
// register the base directories for the namespace prefix
$loader->addNamespace( 'HW', HW_INCLUDES_PATH );

require_once( HW_PLUGIN_PATH . '/vendor/autoload.php' );

// functions
require_once( HW_INCLUDES_PATH . '/functions.php' );
//require_once( HW_INCLUDES_PATH . '/hw-redux-framework.php' );
require_once( HW_INCLUDES_PATH . '/hw-settings-functions.php' );
require_once( HW_INCLUDES_PATH . '/hw-user-functions.php' );
require_once( HW_INCLUDES_PATH . '/hw-template-functions.php' );
require_once( HW_INCLUDES_PATH . '/hw-acf-fields.php' );
require_once( HW_INCLUDES_PATH . '/ACF/init.php' );
require_once( HW_INCLUDES_PATH . '/hw-conditional-functions.php' );
require_once( HW_INCLUDES_PATH . '/hw-notices-functions.php' );
require_once( HW_INCLUDES_PATH . '/hw-utility-functions.php' );
require_once( HW_INCLUDES_PATH . '/hw-multisite-functions.php' );
require_once( HW_INCLUDES_PATH . '/cart-functions.php' );
require_once( HW_INCLUDES_PATH . '/hw-cart-hooks.php' );
require_once( HW_INCLUDES_PATH . '/elementor-functions.php' );
require_once( HW_INCLUDES_PATH . '/hw-dynamic-prices-functions.php' );
require_once( HW_INCLUDES_PATH . '/hw-room-block-functions.php' );
require_once( HW_INCLUDES_PATH . '/hw-quote-functions.php' );
require_once( HW_INCLUDES_PATH . '/hw-account-functions.php' );
require_once( HW_INCLUDES_PATH . '/hw-invoices-functions.php' );
require_once( HW_INCLUDES_PATH . '/hw-transactions-functions.php' );
require_once( HW_INCLUDES_PATH . '/hw-shortcodes-functions.php' );
require_once( HW_INCLUDES_PATH . '/hw-pos-functions.php' );
require_once( HW_INCLUDES_PATH . '/hw-email-functions.php' );
require_once( HW_INCLUDES_PATH . '/hw-email-hooks.php' );
require_once( HW_INCLUDES_PATH . '/API/functions.php' );

// hooks
require_once( HW_INCLUDES_PATH . '/hw-acf-hooks.php' );
require_once( HW_INCLUDES_PATH . '/hw-invoices-hooks.php' );
require_once( HW_INCLUDES_PATH . '/hw-onrequest-hooks.php' );
require_once( HW_INCLUDES_PATH . '/hw-pos-hooks.php' );
require_once( HW_INCLUDES_PATH . '/hw-template-hooks.php' );
require_once( HW_INCLUDES_PATH . '/hw-network-hooks.php' );
require_once( HW_INCLUDES_PATH . '/hw-network-functions.php' );

/**
 * Init updater
 */
//$updater = new \HW\Updater\Updater(__FILE__);

function hw(){
    return \HW\App::getInstance();
}
hw();

register_activation_hook( __FILE__, 'hw_configure_roles' );

// CLI
if(class_exists( 'WP_CLI' )){
    
	HW\CLI::getInstance();
}
