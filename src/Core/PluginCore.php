<?php
/**
 * The core plugin class.
 *
 * Define internationalization, admin-specific hooks,
 * and public-facing site hooks.
 *
 * @package MZMBOACCESS
 */

namespace MZoo\MzMboAccess\Core;

use MZoo\MzMboAccess as NS;
use MZoo\MzMindbody as MZ;
use MZoo\MzMboAccess\Access;
use MZoo\MzMboAccess\Client;
use MZoo\MzMboAccess\Backend;
use MZoo\MzMboAccess\Session;
use MZoo\MzMboAccess\Carbon_Fields;

/**
 * The core plugin class.
 *
 * Define internationalization, admin-specific hooks,
 * and public-facing site hooks.
 *
 * @link  http://mzoo.org
 * @since 1.0.0
 *
 * @author Mike iLL/mZoo.org
 */
class PluginCore {

	/**
	 * Instance
	 *
	 * @var   MzMindbodyApi The one true MzMindbodyApi
	 * @since 1.0.1
	 */
	private static $instance;

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @var Loader $loader Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since  1.0.1
	 * @access protected
	 * @var    string $plugin_base_name The string used to uniquely identify this plugin.
	 */
	protected $plugin_basename;

	/**
	 * The current version of the plugin.
	 *
	 * @since  1.0.1
	 * @access protected
	 * @var    string $version The current version of the plugin.
	 */
	protected $version;

	/**
	 * Format for date display, specific to MBO API Plugin.
	 *
	 * @since  1.0.1
	 * @access public
	 * @var    string $date_format WP date format option.
	 */
	public static $date_format;

	/**
	 * Format for time display, specific to MBO API Plugin.
	 *
	 * @since  1.0.1
	 * @access public
	 * @var    string $time_format
	 */
	public static $time_format;

	/**
	 * Timezone string returned by WordPress get_timezone function.
	 *
	 * For example 'US/Eastern'
	 *
	 * @since  1.0.1
	 * @access protected
	 * @var    string $timezone PHP Date formatting string.
	 */
	public static $timezone;

	/**
	 * WordPress option for start of week.
	 *
	 * @since  1.0.1
	 * @access protected
	 * @var    integer $start_of_week.
	 */
	public static $start_of_week;

	/**
	 * Session object
	 *
	 * @var    $session MzAccessSession
	 * @access private
	 */
	private $session;

	/**
	 * Initialize and define the core functionality of the plugin.
	 */
	public function __construct() {

		$this->plugin_name        = NS\PLUGIN_NAME;
		$this->version            = NS\PLUGIN_VERSION;
		$this->plugin_basename    = NS\PLUGIN_BASENAME;
		$this->plugin_text_domain = 'mz-mbo-access';

		$this->load_dependencies();
		$this->set_locale();
		// Could also define_admin_hooks here.
		$this->define_public_hooks();
		$this->add_settings_page();
		// $this->register_shortcodes();

		$this->session = Session\MzAccessSession::instance();
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'mz-mbo-access' ), '2.1' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'mz-mbo-access' ), '2.1' );
	}


	/**
	 * Return our session instance
	 */
	public function get_session() {
		return $this->session;
	}

	/**
	 * Loads the following required dependencies for this plugin.
	 *
	 * - Loader - Orchestrates the hooks of the plugin.
	 * - InternationalizationI18n - Defines internationalization functionality.
	 * - Admin - Defines all hooks for the admin area.
	 * - Frontend - Defines all hooks for the public side of the site.
	 *
	 * @access private
	 */
	private function load_dependencies() {
		$this->loader = new Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the InternationalizationI18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @access private
	 */
	private function set_locale() {

		$plugin_i18n = new InternationalizationI18n( $this->plugin_text_domain );

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @access private
	 */
	private function define_admin_hooks() {
		/*
		* Additional Hooks go here
		*
		* e.g.
		*
		* //admin menu pages
		* $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
		*
		*  //plugin action links
		* $this->loader->add_filter( 'plugin_action_links_' . $this->plugin_basename, $plugin_admin, 'add_additional_action_link' );
		*
		*/

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @access private
	 */
	private function define_public_hooks() {
		$access_portal = new Access\AccessPortal();
		$client_portal = new Client\ClientPortal();
		$carbon_fields = new Carbon_Fields\Carbon_Fields_Init();

		// Load Carbon Fields.
		$this->loader->add_action( 'after_setup_theme', $carbon_fields, 'crb_load', 1 );

		// Add Options page for Mindbody Access Levels.
		$this->loader->add_action( 'carbon_fields_register_fields', $carbon_fields, 'access_levels_page' );

		// Hook into Carbon Fields Fields Registered for shortcode so we have carbon fields option access.
		$this->loader->add_action( 'carbon_fields_fields_registered', $this, 'register_shortcode' );

		// Start Ajax Access Management.
		$this->loader->add_action( 'wp_ajax_nopriv_ajax_login_check_access_permissions', $access_portal, 'ajax_login_check_access_permissions' );
		$this->loader->add_action( 'wp_ajax_ajax_login_check_access_permissions', $access_portal, 'ajax_login_check_access_permissions' );

		$this->loader->add_action( 'wp_ajax_nopriv_ajax_check_access_permissions', $access_portal, 'ajax_check_access_permissions' );
		$this->loader->add_action( 'wp_ajax_ajax_check_access_permissions', $access_portal, 'ajax_check_access_permissions' );

		// Start Ajax Client Check Logged.
		$this->loader->add_action( 'wp_ajax_nopriv_ajax_register_for_class', $client_portal, 'ajax_register_for_class' );
		$this->loader->add_action( 'wp_ajax_ajax_register_for_class', $client_portal, 'ajax_register_for_class' );

		// Start Ajax Client Create Account.
		$this->loader->add_action( 'wp_ajax_nopriv_ajax_create_mbo_account', $client_portal, 'ajax_create_mbo_account' );
		$this->loader->add_action( 'wp_ajax_ajax_create_mbo_account', $client_portal, 'ajax_create_mbo_account' );

		// Start Ajax Client Create Account.
		$this->loader->add_action( 'wp_ajax_nopriv_ajax_generate_signup_form', $client_portal, 'ajax_generate_mbo_signup_form' );
		$this->loader->add_action( 'wp_ajax_ajax_generate_signup_form', $client_portal, 'ajax_generate_mbo_signup_form' );

		// Start Ajax Client Log In.
		$this->loader->add_action( 'wp_ajax_nopriv_ajax_client_login', $client_portal, 'ajax_client_login' );
		$this->loader->add_action( 'wp_ajax_ajax_client_login', $client_portal, 'ajax_client_login' );

		// Start Ajax Client Log Out.
		$this->loader->add_action( 'wp_ajax_nopriv_ajax_client_logout', $client_portal, 'ajax_client_logout' );
		$this->loader->add_action( 'wp_ajax_ajax_client_logout', $client_portal, 'ajax_client_logout' );

		// Start Ajax Display Client Schedule.
		$this->loader->add_action( 'wp_ajax_nopriv_ajax_display_client_schedule', $client_portal, 'ajax_display_client_schedule' );
		$this->loader->add_action( 'wp_ajax_ajax_display_client_schedule', $client_portal, 'ajax_display_client_schedule' );

		// Start Ajax Check Client Logged Status.
		$this->loader->add_action( 'wp_ajax_nopriv_ajax_check_client_logged', $client_portal, 'ajax_check_client_logged' );
		$this->loader->add_action( 'wp_ajax_ajax_check_client_logged', $client_portal, 'ajax_check_client_logged' );

	}


	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @return Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since  1.0.0
	 * @return string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Retrieve the text domain of the plugin.
	 *
	 * @since  1.0.0
	 * @return string    The text domain of the plugin.
	 */
	public function get_plugin_text_domain() {
		return $this->plugin_text_domain;
	}

	/**
	 * Add our settings page
	 *
	 * @since 1.0.0
	 */
	public function add_settings_page() {
		$settings_page = new Backend\SettingsPage();
		$settings_page->addSections();
	}

	/**
	 * Registers the plugins shortcode.
	 *
	 * Called _after_ Carbon fields hook so we have access.
	 *
	 * @access public
	 */
	public function register_shortcode() {
		$access_display = new Access\AccessDisplay();
		add_shortcode( 'mbo-client-access', array( $access_display, 'handle_shortcode' ) );
	}
}
