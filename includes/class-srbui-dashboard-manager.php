<?php
/**
 * Dashboard Manager Class
 *
 * @package SwiftRoleBasedAdminUIManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles dashboard widget control per role.
 */
class SRBUI_Dashboard_Manager {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_dashboard_setup', array( $this, 'hide_dashboard_widgets' ), 999 );
	}

	/**
	 * Hide dashboard widgets based on settings.
	 */
	public function hide_dashboard_widgets() {
		if ( SRBUI_Security::should_bypass() ) {
			return;
		}

		$user = wp_get_current_user();
		if ( ! $user || empty( $user->roles ) ) {
			return;
		}

		$settings = SRBUI_Role_Manager::get_settings();
		$hidden_widgets = array();

		foreach ( $user->roles as $role ) {
			if ( isset( $settings[ $role ]['dashboard'] ) ) {
				$hidden_widgets = array_merge( $hidden_widgets, $settings[ $role ]['dashboard'] );
			}
		}

		if ( empty( $hidden_widgets ) ) {
			return;
		}

		foreach ( $hidden_widgets as $widget_id ) {
			remove_meta_box( $widget_id, 'dashboard', 'normal' );
			remove_meta_box( $widget_id, 'dashboard', 'side' );
			remove_meta_box( $widget_id, 'dashboard', 'column3' );
			remove_meta_box( $widget_id, 'dashboard', 'column4' );
		}
	}

	/**
	 * Get default dashboard widgets.
	 *
	 * @return array
	 */
	public static function get_default_widgets() {
		return array(
			'dashboard_activity'        => __( 'Activity', 'swift-role-based-admin-ui-manager' ),
			'dashboard_right_now'       => __( 'At a Glance', 'swift-role-based-admin-ui-manager' ),
			'dashboard_quick_press'     => __( 'Quick Draft', 'swift-role-based-admin-ui-manager' ),
			'dashboard_primary'         => __( 'WordPress Events and News', 'swift-role-based-admin-ui-manager' ),
			'dashboard_site_health'     => __( 'Site Health Status', 'swift-role-based-admin-ui-manager' ),
		);
	}
}
