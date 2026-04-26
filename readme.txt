=== Swift Role-Based Admin UI Manager ===
Contributors: emtiaz51921
Tags: admin, user roles, dashboard, admin menu, customization
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html



Customize the WordPress admin interface per user role. Hide menus, admin bar nodes, dashboard widgets, and plugins.

== Description ==

Swift Role-Based Admin UI Manager is a powerful and lightweight plugin that allows WordPress administrators to customize and simplify the admin dashboard for each user role.

Instead of exposing all menus, settings, and widgets to every user, you can create a clean and focused interface tailored to specific roles. This is especially useful for client projects, membership sites, or multi-user environments where simplicity and control are essential.

Whether you want to hide advanced settings, reduce clutter, or prevent accidental changes, this plugin gives you full control over the WordPress admin experience.

<h2>Features:</h2>

* **Role-Based Menu Control**: Hide specific admin menu items and submenus per user role.
* **Admin Bar Control**: Remove unwanted items from the admin toolbar for a cleaner interface.
* **Dashboard Widget Control**: Enable or disable default dashboard widgets based on role.
* **Plugin Visibility Control**: Hide selected plugins from the plugins page for non-admin users.
* **Role-Based Configuration**: Easily switch between roles and customize settings dynamically.
* **Safe & Secure**: Built with capability checks to prevent unauthorized access.
* **Lightweight & Fast**: No unnecessary bloat, optimized for performance.
* **Developer Friendly**: Clean OOP structure following WordPress coding standards.

== Installation ==

1. Upload the `swift-role-based-admin-ui-manager` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to `Settings > Swift Role UI` to start customizing.

== Frequently Asked Questions ==

= Does this plugin modify or delete any data? =
No. The plugin only hides elements from the admin interface. All data and functionality remain unchanged.

= Can I hide menus for Administrators? =
Yes, you can. However, it is recommended to keep access to the plugin settings or use the built-in safety mechanisms to avoid locking yourself out.

= Will hidden menus still be accessible via direct URL? =
No. The plugin uses capability checks to restrict access, not just UI hiding.

= Is this plugin suitable for client projects? =
Absolutely. It is ideal for agencies and freelancers who want to simplify the dashboard for clients.

= Does it affect site performance? =
No. The plugin is lightweight and only runs in the admin area.

= Can I revert changes easily? =
Yes. You can update settings anytime or disable the plugin to restore the default WordPress admin interface.

== Screenshots ==

1. Admin menu settings
2. Admin bar settings
3. Dashboard widget settings
4. Plugin visibility settings
5. Safety & Lockout Prevention

== Changelog ==

= 1.0.0 =
* Initial release.
