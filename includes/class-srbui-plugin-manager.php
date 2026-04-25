<?php
/**
 * Plugin Manager Class
 *
 * @package SwiftRoleBasedAdminUIManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles plugin visibility control per role.
 */
class SRBUI_Plugin_Manager {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'all_plugins', array( $this, 'hide_plugins' ), 999 );
	}

	/**
	 * Hide plugins based on settings.
	 *
	 * @param array $all_plugins List of all plugins.
	 * @return array Filtered list of plugins.
	 */
	public function hide_plugins( $all_plugins ) {
		if ( SRBUI_Security::should_bypass() ) {
			return $all_plugins;
		}

		// Only hide if we are in the admin and not during plugin activation/deactivation.
		if ( ! is_admin() || wp_doing_ajax() ) {
			return $all_plugins;
		}

		$user = wp_get_current_user();
		if ( ! $user || empty( $user->roles ) ) {
			return $all_plugins;
		}

		$settings = SRBUI_Role_Manager::get_settings();
		$hidden_plugins = array();

		foreach ( $user->roles as $role ) {
			if ( isset( $settings[ $role ]['plugins'] ) ) {
				$hidden_plugins = array_merge( $hidden_plugins, $settings[ $role ]['plugins'] );
			}
		}

		if ( empty( $hidden_plugins ) ) {
			return $all_plugins;
		}

		foreach ( $hidden_plugins as $plugin_file ) {
			if ( isset( $all_plugins[ $plugin_file ] ) ) {
				unset( $all_plugins[ $plugin_file ] );
			}
		}

		return $all_plugins;
	}

	/**
	 * Get all installed plugins.
	 *
	 * @return array
	 */
	public static function get_installed_plugins() {
		$plugins = get_transient( 'srbui_plugins_list' );

		if ( false === $plugins ) {
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$plugins = get_plugins();
			// Cache for 1 hour.
			set_transient( 'srbui_plugins_list', $plugins, HOUR_IN_SECONDS );
		}

		return $plugins;
	}
}
