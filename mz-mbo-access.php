<?php
/**
 * Plugin Name: mZ Mindbody Access
 *
 * Description: Child plugin for mZoo Mindbody Interface, which can limit user access to content based on MBO client account details.
 *
 * @package MZMBOACCESS
 *
 * @wordpress-plugin
 * Version:         2.1.6
 * Stable tag:      2.1.6
 * Author:          mZoo.org
 * Author URI:      http://www.mZoo.org/
 * Plugin URI:      http://www.mzoo.org/
 * License:         GPL-2.0+
 * License URI:     http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:     mz-mbo-access
 * Domain Path:     /languages
 */

namespace MZoo\MzMboAccess;

use MZoo\MzMboAccess as NS;
use MZoo\MzMindbody;
use MZoo\MzMindbody\Core as Core;

/*
 * TODO consider more eloquent appoach like EDD JILT work!
 * Based on http://wptavern.com/how-to-prevent-wordpress-plugins-from-activating-on-sites-with-incompatible-hosting-environments
 */

/*
 * Define Constants
 */

define( __NAMESPACE__ . '\NS', __NAMESPACE__ . '\\' );

define( NS . 'MZ', 'MZoo\MzMindbody' );

define( NS . 'PLUGIN_NAME', 'mz-mbo-access' );

define( NS . 'PLUGIN_VERSION', '2.1.6' );

define( NS . 'PLUGIN_NAME_DIR', plugin_dir_path( __FILE__ ) );

define( NS . 'PLUGIN_NAME_URL', plugin_dir_url( __FILE__ ) );

define( NS . 'PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

define( NS . 'MINIMUM_PHP_VERSION', 7.1 );

define( NS . 'INIT_LEVEL', 20 );

/**
 * Check the minimum PHP version.
 */
if ( version_compare( PHP_VERSION, MINIMUM_PHP_VERSION, '<' ) ) {
	add_action( 'admin_notices', NS . 'minimum_php_version' );
	add_action( 'admin_init', __NAMESPACE__ . '\deactivate_plugins', INIT_LEVEL );
} else {
	/**
	 * Autoload Classes
	 */
	$wp_mbo_access_autoload = NS\PLUGIN_NAME_DIR . '/vendor/autoload.php';
	if ( file_exists( $wp_mbo_access_autoload ) ) {
		include_once $wp_mbo_access_autoload;
	}

	// Mozart-managed dependencies.
	$wp_mbo_access_mozart_autoload = NS\PLUGIN_NAME_DIR . '/src/Mozart/autoload.php';
	if ( file_exists( $wp_mbo_access_mozart_autoload ) ) {
		include_once $wp_mbo_access_mozart_autoload;
	}

	if ( ! class_exists( '\MZoo\MzMboAccess\Core\PluginCore' ) ) {
		add_action( 'admin_notices', NS . 'missing_composer' );
		add_action( 'admin_init', __NAMESPACE__ . '\deactivate_plugins', INIT_LEVEL );
	} else {

		/**
		 * Register Activation and Deactivation Hooks
		 * This action is documented in src/core/class-activator.php
		 */

		register_activation_hook( __FILE__, array( NS . 'Core\Activator', 'activate' ) );

		/**
		 * The code that runs during plugin deactivation.
		 * This action is documented src/core/class-deactivator.php
		 */

		register_deactivation_hook( __FILE__, array( NS . 'Core\Deactivator', 'deactivate' ) );


		/**
		 * Check for the dependencies and run if everything looks okay.
		 */

		add_action( 'plugins_loaded', __NAMESPACE__ . '\mbo_access_has_mindbody_api', INIT_LEVEL );

	}
}


/**
 * Instance of the main plugin class.
 */
class MzMboAccess {

	/**
	 * The instance of the plugin.
	 *
	 * @since 1.0.1
	 * @var   Init $init Instance of the plugin.
	 */
	private static $instance;

	/**
	 * Main MzMindbody Instance.
	 *
	 * Insures that only one instance of MzMindbody exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * Totally borrowed from Easy_Digital_Downloads, and certainly used with some
	 * ignorance.
	 *
	 * @since     1.0.1
	 * @static
	 * @staticvar array $instance
	 * @see       MBO_Access()
	 * @return    object|MzMboAccess The one true MzMboAccess
	 */
	public static function instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof PluginCore ) ) {
			self::$instance = new NS\Core\PluginCore();
			self::$instance->run();
		}

		return self::$instance;
	}
}

/**
 * Begins execution of the plugin
 *
 * The main function for that returns MzMboAccess
 *
 * The main function responsible for returning the one true MzMboAccess
 * Instance to functions everywhere.
 *
 * Borrowed from Easy_Digital_Downloads.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $mZmboAccess = MBO_Access(); ?>
 *
 * @since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * Also returns copy of the app object so 3rd party developers
 * can interact with the plugin's hooks contained within.
 *
 * @since  1.4
 * @return object|MzMboAccess The one true MzMboAccess Instance.
 */
// @codingStandardsIgnoreStart
function MBO_Access() {
	// @codingStandardsIgnoreEnd
	return NS\MzMboAccess::instance();
}

/**
 * Deactivation and message when initialization fails.
 *
 * @param string $error        Error message to output.
 * @since 2.1.1
 * @return void.
 */
function activation_failed( $error ) {
	if ( is_admin() && current_user_can( 'activate_plugins' ) ) {
		?>
			<div class="notice notice-error is-dismissible"><p><strong>
				<?php echo esc_html( $error ); ?>
			</strong></p></div>
		<?php
	}
}

/**
 * Deactivate plugins.
 *
 * @since 2.1.1
 * @return void.
 */
function deactivate_plugins() {
	\deactivate_plugins( plugin_basename( __FILE__ ) );
	if ( is_admin() && current_user_can( 'activate_plugins' ) ) {
		?>
			<div class="notice notice-success is-dismissible"><p>
				<?php esc_html_e( 'MZ MBO Access plugin has been deactivated.', 'mz-mbo-access' ); ?>
			</p></div>
		<?php
	}
}

/**
 * Notice of missing composer.
 *
 * @since 2.1.1
 * @return void.
 */
function missing_composer() {
	activation_failed( __( 'MZ MBO Access requires Composer autoloading, which is not configured.', 'mz-mbo-access' ) );
}

/**
 * Notice of php version error.
 *
 * @since 2.1.1
 * @return void.
 */
function minimum_php_version() {
	activation_failed( __( 'MZ MBO Access requires PHP version', 'mz-mbo-access' ) . sprintf( ' %1.1f.', MINIMUM_PHP_VERSION ) );
}

/**
 * Insure that parent plugin, is active or deactivate plugin.
 */
function mbo_access_has_mindbody_api() {
	if ( ! class_exists( MZ . '\Core\MzMindbodyApi' ) ) {
		activation_failed( __( 'MZ MBO Access requires MZ Mindbody Api.', 'mz-mbo-access' ) );
		add_action( 'admin_init', __NAMESPACE__ . '\deactivate_plugins', INIT_LEVEL );
	} else {

		// Get MzMboAccess Instance.
		MBO_Access();

	}
}

?>
