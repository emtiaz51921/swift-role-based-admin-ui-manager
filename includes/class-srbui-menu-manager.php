<?php
/**
 * Menu Manager Class
 *
 * @package SwiftRoleBasedAdminUIManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles admin menu control per role.
 */
class SRBUI_Menu_Manager {

	public function __construct() {
		// Capture master menu list before we start hiding things.
		add_action( 'admin_menu', array( $this, 'capture_master_menu' ), 9998 );
		add_action( 'admin_menu', array( $this, 'hide_menus' ), 9999 );
		add_action( 'wp_before_admin_bar_render', array( $this, 'hide_admin_bar_nodes' ), 9999 );
	}

	/**
	 * Capture all registered menus into a transient for use in Settings AJAX.
	 *
	 * This runs at priority 9998 \u2014 BEFORE hide_menus() (9999) mutates the
	 * globals \u2014 so the snapshot is always the full, unfiltered menu list.
	 * We intentionally skip this in AJAX context: during AJAX the globals
	 * may already be pruned, and we must not overwrite a good transient
	 * with a partially-hidden one.
	 */
	public function capture_master_menu() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Never capture during AJAX \u2014 menus may already be filtered by this point.
		if ( wp_doing_ajax() ) {
			return;
		}

		global $menu, $submenu;
		if ( ! empty( $menu ) ) {
			set_transient( 'srbui_menus_master_v2', array( 'menu' => $menu, 'submenu' => $submenu ), HOUR_IN_SECONDS );
		}
	}

	/**
	 * Bust the master menu transient cache.
	 *
	 * Should be called when the registered menu list itself changes, e.g. when
	 * a plugin is activated or deactivated. Do NOT call on settings save \u2014
	 * the transient stores which menus *exist*, not which are hidden; deleting
	 * it on save causes the next AJAX role-switch to fall back to the
	 * already-pruned globals and show \"No menus available for this role.\"
	 */
	public static function bust_menu_cache() {
		delete_transient( 'srbui_menus_master_v2' );
	}

	/**
	 * Hide admin menu items based on settings for current user role.
	 */
	public function hide_menus() {
		if ( SRBUI_Security::should_bypass() ) {
			return;
		}

		$user = wp_get_current_user();
		if ( ! $user || empty( $user->roles ) ) {
			return;
		}

		$settings = SRBUI_Role_Manager::get_settings();
		$hidden_items = array();

		foreach ( $user->roles as $role ) {
			if ( isset( $settings[ $role ]['menus'] ) ) {
				$hidden_items = array_merge( $hidden_items, (array) $settings[ $role ]['menus'] );
			}
		}

		if ( empty( $hidden_items ) ) {
			return;
		}

		// Normalize hidden items for comparison.
		$hidden_items = array_map( 'html_entity_decode', $hidden_items );

		global $menu, $submenu;

		// Handle Top-level Menus.
		if ( is_array( $menu ) ) {
			foreach ( $menu as $key => $item ) {
				if ( isset( $item[2] ) ) {
					$slug = html_entity_decode( $item[2] );
					// Check for exact match or if it's an elementor specific menu that might be missed.
					if ( in_array( $slug, $hidden_items, true ) || ( in_array( 'elementor', $hidden_items, true ) && strpos( $slug, 'elementor' ) !== false ) ) {
						remove_menu_page( $item[2] );
						unset( $menu[ $key ] );
					}
				}
			}
		}

		// Handle Sub-menus.
		if ( is_array( $submenu ) ) {
			foreach ( $submenu as $parent => $sub_items ) {
				if ( ! is_array( $sub_items ) ) {
					continue;
				}
				foreach ( $sub_items as $key => $sub_item ) {
					if ( isset( $sub_item[2] ) ) {
						$sub_slug = html_entity_decode( $sub_item[2] );
						if ( in_array( $sub_slug, $hidden_items, true ) || ( in_array( 'elementor', $hidden_items, true ) && strpos( $sub_slug, 'elementor' ) !== false ) ) {
							remove_submenu_page( $parent, $sub_item[2] );
							unset( $submenu[ $parent ][ $key ] );
						}
					}
				}
			}
		}
	}

	/**
	 * Hide admin bar nodes based on settings.
	 */
	public function hide_admin_bar_nodes() {
		if ( SRBUI_Security::should_bypass() ) {
			return;
		}

		global $wp_admin_bar;

		if ( ! $wp_admin_bar ) {
			return;
		}

		$user = wp_get_current_user();
		if ( ! $user || empty( $user->roles ) ) {
			return;
		}

		$settings = SRBUI_Role_Manager::get_settings();
		$hidden_nodes = array();

		foreach ( $user->roles as $role ) {
			if ( isset( $settings[ $role ]['admin_bar'] ) ) {
				$hidden_nodes = array_merge( $hidden_nodes, (array) $settings[ $role ]['admin_bar'] );
			}
		}

		if ( empty( $hidden_nodes ) ) {
			return;
		}

		foreach ( $hidden_nodes as $node_id ) {
			$wp_admin_bar->remove_node( $node_id );
		}
	}

	/**
	 * Flag to prevent infinite loops during menu fetching.
	 *
	 * @var bool
	 */
	private static $is_fetching = false;

	/**
	 * Get all admin menu items that a specific role can see by default.
	 *
	 * @param string $role_slug The role slug to check against.
	 * @return array
	 */
	public static function get_all_menus( $role_slug = '' ) {
		global $menu, $submenu;

		// Prevent recursion.
		if ( self::$is_fetching ) {
			return array();
		}

		$cache_key = 'srbui_menus_master_v2';
		$cached_data = get_transient( $cache_key );

		if ( false !== $cached_data && is_array( $cached_data ) ) {
			$master_menu    = $cached_data['menu'];
			$master_submenu = $cached_data['submenu'];
		} else {
			/*
			 * Read the $menu and $submenu globals that WordPress populates during
			 * the standard admin_menu hook lifecycle. This plugin's hide_menus()
			 * already runs at priority 9999, so by the time this method is called
			 * from the admin UI (via AJAX), all menus are registered by core and
			 * third-party plugins through their own admin_menu callbacks.
			 *
			 * NOTE: Direct inclusion of wp-admin/menu.php is explicitly forbidden
			 * by WordPress.org repository guidelines and has been removed.
			 */
			self::$is_fetching = true;

			$master_menu    = is_array( $menu )    ? $menu    : array();
			$master_submenu = is_array( $submenu ) ? $submenu : array();

			// Only cache when menus contain data AND we are NOT in an AJAX
			// request. In AJAX context, hide_menus() may have already pruned
			// the globals; writing them back would corrupt the transient.
			if ( ! empty( $master_menu ) && ! wp_doing_ajax() ) {
				set_transient( $cache_key, array( 'menu' => $master_menu, 'submenu' => $master_submenu ), HOUR_IN_SECONDS );
			}

			self::$is_fetching = false;
		}

		$all_menus = array();
		$role      = get_role( $role_slug );

		if ( empty( $master_menu ) || ! is_array( $master_menu ) ) {
			return array();
		}

		foreach ( $master_menu as $item ) {
			if ( ! is_array( $item ) || empty( $item[0] ) || ! isset( $item[2] ) ) {
				continue;
			}

			if ( $role && ! empty( $item[1] ) && ! $role->has_cap( $item[1] ) ) {
				continue;
			}

			$label = wp_strip_all_tags( $item[0] );
			$slug  = $item[2];

			$subs = array();
			if ( isset( $master_submenu[ $slug ] ) && is_array( $master_submenu[ $slug ] ) ) {
				foreach ( $master_submenu[ $slug ] as $sub_item ) {
					if ( ! is_array( $sub_item ) || empty( $sub_item[0] ) || ! isset( $sub_item[2] ) ) {
						continue;
					}

					if ( $role && ! empty( $sub_item[1] ) && ! $role->has_cap( $sub_item[1] ) ) {
						continue;
					}

					$subs[] = array(
						'label' => wp_strip_all_tags( $sub_item[0] ),
						'slug'  => $sub_item[2],
					);
				}
			}

			$all_menus[] = array(
				'label' => $label,
				'slug'  => $slug,
				'subs'  => $subs,
			);
		}

		return $all_menus;
	}
}
