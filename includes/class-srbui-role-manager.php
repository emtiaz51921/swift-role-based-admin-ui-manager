<?php
/**
 * Role Manager Class
 *
 * @package SwiftRoleBasedAdminUIManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles role fetching and settings storage.
 */
class SRBUI_Role_Manager {

	/**
	 * Settings option name.
	 *
	 * @var string
	 */
	private $option_name = 'srbui_settings';

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Initialization.
	}

	/**
	 * Get all registered WordPress roles.
	 *
	 * @return array List of roles with label and name.
	 */
	public static function get_roles() {
		return wp_roles()->get_names();
	}

	/**
	 * Get settings for a specific role or all settings.
	 *
	 * @param string|null $role Role name.
	 * @return array Settings array.
	 */
	public static function get_settings( $role = null ) {
		$settings = get_option( 'srbui_settings', array() );

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		if ( $role ) {
			return isset( $settings[ $role ] ) ? (array) $settings[ $role ] : array();
		}

		return $settings;
	}

	/**
	 * Update settings for a specific role.
	 *
	 * @param string $role Role name.
	 * @param array  $data Settings data.
	 * @return bool True on success, false on failure.
	 */
	public static function update_settings( $role, $data ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		$settings = get_option( 'srbui_settings', array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$settings[ $role ] = (array) $data;

		self::clear_cache();

		return update_option( 'srbui_settings', $settings );
	}

	/**
	 * Clear transient caches that are safe to rebuild.
	 *
	 * Called after saving role settings. Intentionally does NOT delete
	 * the menus master transient (`srbui_menus_master_v2`) because that
	 * transient stores which menus *exist* in WordPress — not which are
	 * hidden. It can only be rebuilt during a real page load (when the
	 * `admin_menu` hook fires and populates the `$menu` globals). During
	 * AJAX, the globals are empty, so deleting the transient here would
	 * cause "No menus available for this role." on the next role switch.
	 */
	public static function clear_cache() {
		delete_transient( 'srbui_plugins_list' );
	}

	/**
	 * Clear ALL transient caches including the menus master list.
	 *
	 * Should only be called when the set of registered menus actually
	 * changes, e.g. on plugin activation/deactivation — NOT on settings
	 * save. The next real admin page load will recapture the full menu.
	 */
	public static function clear_all_cache() {
		delete_transient( 'srbui_plugins_list' );
		delete_transient( 'srbui_menus_master_v2' );
	}
}
