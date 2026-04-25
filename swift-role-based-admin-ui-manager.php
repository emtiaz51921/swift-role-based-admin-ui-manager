<?php
/**
 * Plugin Name: Swift Role-Based Admin UI Manager
 * Description: Customize the WordPress admin interface per user role. Hide menus, admin bar nodes, dashboard widgets, and plugins.
 * Version: 1.0.0
 * Author: emtiaz51921
 * Author URI: http://imtiazshamim.com/
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: swift-role-based-admin-ui-manager
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * @package SwiftRoleBasedAdminUIManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Main Plugin Class
 */
final class SRBUI {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public const VERSION = '1.0.0';

	/**
	 * Singleton instance.
	 *
	 * @var SRBUI|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return SRBUI
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->define_constants();
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Define plugin constants.
	 */
	private function define_constants() {
		define( 'SRBUI_PATH', plugin_dir_path( __FILE__ ) );
		define( 'SRBUI_URL', plugin_dir_url( __FILE__ ) );
		define( 'SRBUI_BASENAME', plugin_basename( __FILE__ ) );
	}

	/**
	 * Include required files.
	 */
	private function includes() {
		require_once SRBUI_PATH . 'includes/class-srbui-security.php';
		require_once SRBUI_PATH . 'includes/class-srbui-role-manager.php';
		require_once SRBUI_PATH . 'includes/class-srbui-admin-ui.php';
		require_once SRBUI_PATH . 'includes/class-srbui-menu-manager.php';
		require_once SRBUI_PATH . 'includes/class-srbui-dashboard-manager.php';
		require_once SRBUI_PATH . 'includes/class-srbui-plugin-manager.php';
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		add_action( 'activated_plugin', array( 'SRBUI_Role_Manager', 'clear_cache' ) );
		add_action( 'deactivated_plugin', array( 'SRBUI_Role_Manager', 'clear_cache' ) );
		
		// Initialize components.
		if ( is_admin() ) {
			new SRBUI_Security();
			new SRBUI_Role_Manager();
			new SRBUI_Admin_UI();
			new SRBUI_Menu_Manager();
			new SRBUI_Dashboard_Manager();
			new SRBUI_Plugin_Manager();
		}
	}
}

/**
 * Initialize the plugin.
 */
function srbui_init() {
	return SRBUI::get_instance();
}

srbui_init();
