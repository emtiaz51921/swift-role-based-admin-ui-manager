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
	 * Clear all transients used for caching.
	 */
	public static function clear_cache() {
		delete_transient( 'srbui_plugins_list' );
		delete_transient( 'srbui_menus_master_v2' );
	}
}
