<?php
/**
 * Plugin Name: Hola Weddings Library
 * Plugin URI:  https://mcweb.mx
 * Description: Hola Weddings Library Plugin for wedding couple sites
 * Author:      Marco Cariolato
 * Author URI:  https://mcweb.mx
 * Version:     1.0.0
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

define( 'HWL_URL' , plugin_dir_url( __FILE__ ) );
define( 'HWL_PLUGIN_PATH', __DIR__ );
define( 'HWL_INCLUDES_PATH', HWL_PLUGIN_PATH . '/includes' );
define( 'HWL_ASSETS_PATH', HWL_PLUGIN_PATH . '/assets' );
define( 'HWL_TEMPLATE_PARTS', HWL_PLUGIN_PATH . '/template-parts' );

define( 'HWL_ASSETS_URL', HWL_URL . '/assets' );

require_once( HWL_PLUGIN_PATH . '/vendor/autoload.php' );
