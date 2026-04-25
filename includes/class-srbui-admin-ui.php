<?php
/**
 * Admin UI Class
 *
 * @package SwiftRoleBasedAdminUIManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the admin settings page and AJAX logic.
 */
class SRBUI_Admin_UI {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_srbui_save_settings', array( $this, 'save_settings' ) );
		add_action( 'wp_ajax_srbui_load_settings', array( $this, 'load_role_settings' ) );
	}

	/**
	 * Add settings page under "Settings".
	 */
	public function add_settings_page() {
		add_options_page(
			esc_html__( 'Swift Role-Based Admin UI Manager', 'swift-role-based-admin-ui-manager' ),
			esc_html__( 'Swift Role UI', 'swift-role-based-admin-ui-manager' ),
			'manage_options',
			'srbui-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( $hook ) {
		if ( 'settings_page_srbui-settings' !== $hook ) {
			return;
		}

		wp_enqueue_style( 'srbui-admin-style', SRBUI_URL . 'admin/css/srbui-admin.css', array(), SRBUI::VERSION );
		wp_enqueue_script( 'srbui-admin-js', SRBUI_URL . 'admin/js/srbui-admin.js', array( 'jquery' ), SRBUI::VERSION, true );

		wp_localize_script( 'srbui-admin-js', 'srbui_vars', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'srbui_nonce' ),
			'messages' => array(
				'saving' => __( 'Saving...', 'swift-role-based-admin-ui-manager' ),
				'saved'  => __( 'Settings saved!', 'swift-role-based-admin-ui-manager' ),
				'error'  => __( 'An error occurred.', 'swift-role-based-admin-ui-manager' ),
			),
		) );
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$roles = SRBUI_Role_Manager::get_roles();
		?>
		<div class="wrap srbui-wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			
			<?php 
			// Display reset success message.
			if ( isset( $_GET['reset'] ) && 'success' === $_GET['reset'] && isset( $_GET['_srbui_notice_nonce'] ) && wp_verify_nonce( sanitize_key( $_GET['_srbui_notice_nonce'] ), 'srbui_reset_success' ) ) {
				?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'All restrictions have been reset successfully.', 'swift-role-based-admin-ui-manager' ); ?></p>
				</div>
				<?php
			}
			?>

			<div class="srbui-role-selector-wrap">
				<label for="srbui-role-select"><?php esc_html_e( 'Select User Role:', 'swift-role-based-admin-ui-manager' ); ?></label>
				<select id="srbui-role-select">
					<option value=""><?php esc_html_e( 'Choose a role...', 'swift-role-based-admin-ui-manager' ); ?></option>
					<?php foreach ( $roles as $role_slug => $role_name ) : ?>
						<option value="<?php echo esc_attr( $role_slug ); ?>"><?php echo esc_html( $role_name ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div id="srbui-settings-container" style="display: none;">
				<nav class="nav-tab-wrapper">
					<a href="#menus" class="nav-tab nav-tab-active"><?php esc_html_e( 'Menus', 'swift-role-based-admin-ui-manager' ); ?></a>
					<a href="#admin-bar" class="nav-tab"><?php esc_html_e( 'Admin Bar', 'swift-role-based-admin-ui-manager' ); ?></a>
					<a href="#dashboard" class="nav-tab"><?php esc_html_e( 'Dashboard', 'swift-role-based-admin-ui-manager' ); ?></a>
					<a href="#plugins" class="nav-tab"><?php esc_html_e( 'Plugins', 'swift-role-based-admin-ui-manager' ); ?></a>
					<a href="#safety" class="nav-tab"><?php esc_html_e( 'Safety', 'swift-role-based-admin-ui-manager' ); ?></a>
				</nav>

				<form id="srbui-settings-form">
					<input type="hidden" name="role" id="srbui-selected-role" value="">
					<?php wp_nonce_field( 'srbui_nonce', 'nonce' ); ?>
					
					<div id="tab-menus" class="tab-content">
						<h2><?php esc_html_e( 'Hide Admin Menus', 'swift-role-based-admin-ui-manager' ); ?></h2>
						<p class="description"><?php esc_html_e( 'Check the menus and submenus you want to HIDE for this role.', 'swift-role-based-admin-ui-manager' ); ?></p>
						<div id="srbui-menus-grid" class="srbui-grid">
							<!-- Populated via AJAX -->
						</div>
					</div>

					<div id="tab-admin-bar" class="tab-content" style="display:none;">
						<h2><?php esc_html_e( 'Hide Admin Bar Nodes', 'swift-role-based-admin-ui-manager' ); ?></h2>
						<p class="description"><?php esc_html_e( 'Check the nodes you want to remove from the admin bar.', 'swift-role-based-admin-ui-manager' ); ?></p>
						<div id="srbui-admin-bar-grid" class="srbui-grid">
							<!-- Populated via AJAX -->
						</div>
					</div>

					<div id="tab-dashboard" class="tab-content" style="display:none;">
						<h2><?php esc_html_e( 'Hide Dashboard Widgets', 'swift-role-based-admin-ui-manager' ); ?></h2>
						<p class="description"><?php esc_html_e( 'Disable selected dashboard widgets for this role.', 'swift-role-based-admin-ui-manager' ); ?></p>
						<div id="srbui-dashboard-grid" class="srbui-grid">
							<!-- Populated via AJAX -->
						</div>
					</div>

					<div id="tab-plugins" class="tab-content" style="display:none;">
						<h2><?php esc_html_e( 'Hide Plugins', 'swift-role-based-admin-ui-manager' ); ?></h2>
						<p class="description"><?php esc_html_e( 'Hide selected plugins from the plugins list for this role.', 'swift-role-based-admin-ui-manager' ); ?></p>
						<div id="srbui-plugins-grid" class="srbui-grid">
							<!-- Populated via AJAX -->
						</div>
					</div>

					<div id="tab-safety" class="tab-content" style="display:none;">
						<h2><?php esc_html_e( 'Safety & Lockout Prevention', 'swift-role-based-admin-ui-manager' ); ?></h2>
						<p class="description"><?php esc_html_e( 'Configure users and settings that are ALWAYS exempt from restrictions.', 'swift-role-based-admin-ui-manager' ); ?></p>
						
						<div class="srbui-safety-section">
							<h3><?php esc_html_e( 'Excluded User IDs', 'swift-role-based-admin-ui-manager' ); ?></h3>
							<p class="description"><?php esc_html_e( 'Enter User IDs (comma-separated) that should never see any UI restrictions.', 'swift-role-based-admin-ui-manager' ); ?></p>
							<?php $excluded = get_option( 'srbui_excluded_users', array() ); ?>
							<input type="text" name="excluded_users" value="<?php echo esc_attr( implode( ', ', (array) $excluded ) ); ?>" class="regular-text" placeholder="e.g. 1, 5, 12">
						</div>

						<div class="srbui-safety-section" style="margin-top: 20px;">
							<h3><?php esc_html_e( 'Emergency Reset', 'swift-role-based-admin-ui-manager' ); ?></h3>
							<p class="description"><?php esc_html_e( 'If you accidentally lock yourself out, use the following URL to reset all settings:', 'swift-role-based-admin-ui-manager' ); ?></p>
							<code><?php echo esc_url( wp_nonce_url( admin_url( '?srbui_reset=1' ), 'srbui_emergency_reset' ) ); ?></code>
						</div>
					</div>

					<div class="submit">
						<button type="submit" class="button button-primary" id="srbui-save-btn"><?php esc_html_e( 'Save Role Settings', 'swift-role-based-admin-ui-manager' ); ?></button>
						<span class="spinner" id="srbui-spinner"></span>
						<span id="srbui-msg"></span>
					</div>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Save settings via AJAX.
	 */
	public function save_settings() {
		check_ajax_referer( 'srbui_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized.', 'swift-role-based-admin-ui-manager' ) );
		}

		$role = isset( $_POST['role'] ) ? sanitize_text_field( wp_unslash( $_POST['role'] ) ) : '';
		if ( ! $role ) {
			wp_send_json_error( __( 'No role selected.', 'swift-role-based-admin-ui-manager' ) );
		}

		// Handle Excluded Users.
		if ( isset( $_POST['excluded_users'] ) ) {
			$excluded_raw = sanitize_text_field( wp_unslash( $_POST['excluded_users'] ) );
			$excluded_ids = array_filter( array_map( 'intval', explode( ',', $excluded_raw ) ) );
			update_option( 'srbui_excluded_users', $excluded_ids );
		}

		$data = array(
			'menus'     => isset( $_POST['menus'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['menus'] ) ) : array(),
			'admin_bar' => isset( $_POST['admin_bar'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['admin_bar'] ) ) : array(),
			'dashboard' => isset( $_POST['dashboard'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['dashboard'] ) ) : array(),
			'plugins'   => isset( $_POST['plugins'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['plugins'] ) ) : array(),
		);

		SRBUI_Role_Manager::update_settings( $role, $data );

		wp_send_json_success( __( 'Settings saved successfully!', 'swift-role-based-admin-ui-manager' ) );
	}

	/**
	 * Load settings and filtered HTML for a specific role via AJAX.
	 */
	public function load_role_settings() {
		check_ajax_referer( 'srbui_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized.', 'swift-role-based-admin-ui-manager' ) );
		}

		$role_slug = isset( $_POST['role'] ) ? sanitize_text_field( wp_unslash( $_POST['role'] ) ) : '';
		if ( ! $role_slug ) {
			wp_send_json_error( __( 'No role selected.', 'swift-role-based-admin-ui-manager' ) );
		}

		$settings = SRBUI_Role_Manager::get_settings( $role_slug );

		ob_start();
		$this->render_menu_checkboxes( $role_slug, $settings );
		$menus_html = ob_get_clean();

		ob_start();
		$this->render_admin_bar_checkboxes( $role_slug, $settings );
		$admin_bar_html = ob_get_clean();

		ob_start();
		$this->render_dashboard_checkboxes( $role_slug, $settings );
		$dashboard_html = ob_get_clean();

		ob_start();
		$this->render_plugin_checkboxes( $role_slug, $settings );
		$plugins_html = ob_get_clean();

		wp_send_json_success( array(
			'settings' => $settings,
			'html'     => array(
				'menus'     => $menus_html,
				'admin_bar' => $admin_bar_html,
				'dashboard' => $dashboard_html,
				'plugins'   => $plugins_html,
			),
		) );
	}

	/**
	 * Render menu checkboxes for a specific role.
	 */
	private function render_menu_checkboxes( $role_slug, $settings ) {
		$all_menus = SRBUI_Menu_Manager::get_all_menus( $role_slug );
		$selected  = isset( $settings['menus'] ) ? (array) $settings['menus'] : array();

		if ( empty( $all_menus ) ) {
			echo '<p class="description">' . esc_html__( 'No menus available for this role.', 'swift-role-based-admin-ui-manager' ) . '</p>';
			return;
		}

		foreach ( $all_menus as $menu ) : ?>
			<div class="menu-item-group">
				<label>
					<input type="checkbox" name="menus[]" value="<?php echo esc_attr( $menu['slug'] ); ?>" <?php checked( in_array( $menu['slug'], $selected, true ) ); ?>>
					<strong><?php echo esc_html( $menu['label'] ); ?></strong>
				</label>
				<?php if ( ! empty( $menu['subs'] ) ) : ?>
					<div class="submenu-items">
						<?php foreach ( $menu['subs'] as $sub ) : ?>
							<label>
								<input type="checkbox" name="menus[]" value="<?php echo esc_attr( $sub['slug'] ); ?>" <?php checked( in_array( $sub['slug'], $selected, true ) ); ?>>
								<?php echo esc_html( $sub['label'] ); ?>
							</label>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		<?php endforeach;
	}

	/**
	 * Render admin bar checkboxes.
	 */
	private function render_admin_bar_checkboxes( $role_slug, $settings ) {
		$bar_nodes = array(
			'wp-logo'      => __( 'WordPress Logo', 'swift-role-based-admin-ui-manager' ),
			'site-name'    => __( 'Site Name', 'swift-role-based-admin-ui-manager' ),
			'updates'      => __( 'Updates', 'swift-role-based-admin-ui-manager' ),
			'comments'     => __( 'Comments', 'swift-role-based-admin-ui-manager' ),
			'new-content'  => __( 'New Content', 'swift-role-based-admin-ui-manager' ),
			'my-account'   => __( 'My Account (Profile)', 'swift-role-based-admin-ui-manager' ),
			'search'       => __( 'Search', 'swift-role-based-admin-ui-manager' ),
			'top-secondary'=> __( 'Secondary Menu', 'swift-role-based-admin-ui-manager' ),
		);
		$selected = isset( $settings['admin_bar'] ) ? (array) $settings['admin_bar'] : array();

		foreach ( $bar_nodes as $node_id => $node_label ) : ?>
			<label>
				<input type="checkbox" name="admin_bar[]" value="<?php echo esc_attr( $node_id ); ?>" <?php checked( in_array( $node_id, $selected, true ) ); ?>>
				<?php echo esc_html( $node_label ); ?>
			</label>
		<?php endforeach;
	}

	/**
	 * Render dashboard widget checkboxes.
	 */
	private function render_dashboard_checkboxes( $role_slug, $settings ) {
		$widgets  = SRBUI_Dashboard_Manager::get_default_widgets();
		$selected = isset( $settings['dashboard'] ) ? (array) $settings['dashboard'] : array();

		foreach ( $widgets as $id => $label ) : ?>
			<label>
				<input type="checkbox" name="dashboard[]" value="<?php echo esc_attr( $id ); ?>" <?php checked( in_array( $id, $selected, true ) ); ?>>
				<?php echo esc_html( $label ); ?>
			</label>
		<?php endforeach;
	}

	/**
	 * Render plugin checkboxes.
	 */
	private function render_plugin_checkboxes( $role_slug, $settings ) {
		$plugins  = SRBUI_Plugin_Manager::get_installed_plugins();
		$selected = isset( $settings['plugins'] ) ? (array) $settings['plugins'] : array();
		$role     = get_role( $role_slug );

		// Only show plugins if the role can see plugins page by default.
		if ( $role && ! $role->has_cap( 'activate_plugins' ) ) {
			echo '<p class="description">' . esc_html__( 'This role cannot see plugins by default.', 'swift-role-based-admin-ui-manager' ) . '</p>';
			return;
		}

		foreach ( $plugins as $file => $data ) : ?>
			<label title="<?php echo esc_attr( $file ); ?>">
				<input type="checkbox" name="plugins[]" value="<?php echo esc_attr( $file ); ?>" <?php checked( in_array( $file, $selected, true ) ); ?>>
				<?php echo esc_html( $data['Name'] ); ?>
			</label>
		<?php endforeach;
	}
}
