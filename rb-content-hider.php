<?php
/**
 * Plugin Name:       Role Based Content Hider
 * Plugin URI:        https://example.com/rb-content-hider
 * Description:       Hide menus, widgets, and content based on WordPress user roles.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Role Based Content Hider
 * Author URI:        https://example.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       rb-content-hider
 * Domain Path:       /languages
 *
 * @package RBContentHider
 */

// Impedisci accesso diretto.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Costanti del plugin.
 */
define( 'RBCH_VERSION', '1.0.0' );
define( 'RBCH_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RBCH_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'RBCH_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Carica autoloader di Composer.
 */
if ( file_exists( RBCH_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once RBCH_PLUGIN_DIR . 'vendor/autoload.php';
} else {
	add_action(
		'admin_notices',
		function () {
			echo '<div class="notice notice-error"><p>';
			echo esc_html__(
				'Role Based Content Hider: Composer autoloader not found. Please run "composer install" in the plugin directory.',
				'rb-content-hider'
			);
			echo '</p></div>';
		}
	);
	return;
}

/**
 * Carica il text domain per le traduzioni.
 */
function rbch_load_textdomain() {
	load_plugin_textdomain( 'rb-content-hider', false, dirname( RBCH_PLUGIN_BASENAME ) . '/languages' );
}
add_action( 'plugins_loaded', 'rbch_load_textdomain' );

/**
 * Inizializza il plugin.
 */
function rbch_init() {
	return \RBContentHider\Plugin::get_instance();
}
add_action( 'plugins_loaded', 'rbch_init' );

/**
 * Attivazione del plugin.
 */
function rbch_activate() {
	$defaults = array(
		'hide_from_admins' => 0,
		'debug_mode'       => 0,
	);
	if ( ! get_option( 'rbch_settings' ) ) {
		add_option( 'rbch_settings', $defaults );
	}
}
register_activation_hook( __FILE__, 'rbch_activate' );

/**
 * Disattivazione del plugin.
 */
function rbch_deactivate() {
	// Pulizia se necessaria.
}
register_deactivation_hook( __FILE__, 'rbch_deactivate' );
