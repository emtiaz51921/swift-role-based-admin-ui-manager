<?php
/**
 * Security and Safety Class
 *
 * @package SwiftRoleBasedAdminUIManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles bypass logic and emergency resets to prevent lockouts.
 */
class SRBUI_Security {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'handle_emergency_reset' ) );
	}

	/**
	 * Centralized bypass function.
	 * Determines if restrictions should be skipped for the current user.
	 *
	 * @return bool True if restrictions should be bypassed, false otherwise.
	 */
	public static function should_bypass() {
		// 1. Check for Safe Mode via wp-config.php constant.
		if ( defined( 'SRBUI_SAFE_MODE' ) && SRBUI_SAFE_MODE ) {
			return true;
		}

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}

		// 2. Always bypass for Super Admins (Multisite) for absolute safety.
		if ( is_multisite() && is_super_admin( $user_id ) ) {
			return true;
		}

		// 3. Check for Excluded Users system (by ID).
		$excluded_users = get_option( 'srbui_excluded_users', array() );
		if ( ! is_array( $excluded_users ) ) {
			$excluded_users = array();
		}

		if ( in_array( $user_id, array_map( 'intval', $excluded_users ), true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Handle emergency reset via URL parameter.
	 * URL: /wp-admin/?srbui_reset=1&_wpnonce=...
	 */
	public function handle_emergency_reset() {
		if ( ! isset( $_GET['srbui_reset'] ) || '1' !== $_GET['srbui_reset'] ) {
			return;
		}

		// Safety check: User must still have manage_options capability to perform reset.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to reset settings.', 'swift-role-based-admin-ui-manager' ) );
		}

		// Verify nonce for security.
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'srbui_emergency_reset' ) ) {
			$reset_url = wp_nonce_url( admin_url( '?srbui_reset=1' ), 'srbui_emergency_reset' );
			wp_die( 
				sprintf( 
					/* translators: %s: Reset URL */
					esc_html__( 'Are you sure you want to reset all Role UI restrictions? %s', 'swift-role-based-admin-ui-manager' ), 
					'<a href="' . esc_url( $reset_url ) . '">' . esc_html__( 'Confirm Reset', 'swift-role-based-admin-ui-manager' ) . '</a>'
				) 
			);
		}

		// Selective reset: Only clear 'administrator' role settings.
		$settings = get_option( 'srbui_settings', array() );
		if ( is_array( $settings ) && isset( $settings['administrator'] ) ) {
			unset( $settings['administrator'] );
			update_option( 'srbui_settings', $settings );
		}

		// Add current user to excluded list for immediate safety.
		$excluded_users = get_option( 'srbui_excluded_users', array() );
		if ( ! is_array( $excluded_users ) ) {
			$excluded_users = array();
		}
		
		$current_user_id = get_current_user_id();
		if ( $current_user_id && ! in_array( $current_user_id, $excluded_users, true ) ) {
			$excluded_users[] = $current_user_id;
			update_option( 'srbui_excluded_users', array_map( 'intval', $excluded_users ) );
		}

		// Redirect to settings page with success message and nonce.
		$redirect_url = add_query_arg( 
			array( 
				'reset' => 'success',
				'_srbui_notice_nonce' => wp_create_nonce( 'srbui_reset_success' )
			), 
			admin_url( 'options-general.php?page=srbui-settings' ) 
		);
		wp_safe_redirect( $redirect_url );
		exit;
	}
}
