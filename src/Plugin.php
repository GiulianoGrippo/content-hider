<?php
/**
 * Classe principale del plugin.
 *
 * @package RBContentHider
 */

namespace RBContentHider;

use RBContentHider\Admin\MenuVisibility;
use RBContentHider\Admin\WidgetVisibility;
use RBContentHider\Admin\SettingsPage;
use RBContentHider\Frontend\Shortcodes;

/**
 * Classe Plugin - Singleton principale.
 *
 * Coordina tutti i componenti del plugin.
 */
class Plugin {

	/**
	 * Istanza singleton.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Istanza MenuVisibility.
	 *
	 * @var MenuVisibility
	 */
	private MenuVisibility $menu_visibility;

	/**
	 * Istanza WidgetVisibility.
	 *
	 * @var WidgetVisibility
	 */
	private WidgetVisibility $widget_visibility;

	/**
	 * Istanza SettingsPage.
	 *
	 * @var SettingsPage
	 */
	private SettingsPage $settings_page;

	/**
	 * Istanza Shortcodes.
	 *
	 * @var Shortcodes
	 */
	private Shortcodes $shortcodes;

	/**
	 * Costruttore privato (Singleton).
	 */
	private function __construct() {
		$this->init_components();
		$this->register_hooks();
	}

	/**
	 * Ottiene l'istanza singleton.
	 *
	 * @return Plugin
	 */
	public static function get_instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Inizializza i componenti del plugin.
	 *
	 * @return void
	 */
	private function init_components(): void {
		$this->menu_visibility   = new MenuVisibility();
		$this->widget_visibility = new WidgetVisibility();
		$this->settings_page     = new SettingsPage();
		$this->shortcodes        = new Shortcodes();
	}

	/**
	 * Registra gli hook principali.
	 *
	 * @return void
	 */
	private function register_hooks(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		/**
		 * Fires after the plugin is fully initialized.
		 *
		 * @param Plugin $plugin The plugin instance.
		 */
		do_action( 'rbch_loaded', $this );
	}

	/**
	 * Carica gli asset CSS e JS per l'admin.
	 *
	 * @param string $hook_suffix La pagina admin corrente.
	 * @return void
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {
		$allowed_hooks = array(
			'nav-menus.php',
			'widgets.php',
			'settings_page_rb-content-hider',
		);

		if ( ! in_array( $hook_suffix, $allowed_hooks, true ) ) {
			return;
		}

		wp_enqueue_style(
			'rbch-admin-style',
			RBCH_PLUGIN_URL . 'assets/css/admin-style.css',
			array(),
			RBCH_VERSION
		);

		wp_enqueue_script(
			'rbch-admin-script',
			RBCH_PLUGIN_URL . 'assets/js/admin-script.js',
			array( 'jquery' ),
			RBCH_VERSION,
			true
		);

		wp_localize_script(
			'rbch-admin-script',
			'rbchAdmin',
			array(
				'nonce'   => wp_create_nonce( 'rbch_admin_nonce' ),
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			)
		);
	}

	/**
	 * Restituisce le impostazioni del plugin.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_settings(): array {
		$defaults = array(
			'hide_from_admins' => 0,
			'debug_mode'       => 0,
		);

		$settings = get_option( 'rbch_settings', $defaults );

		return wp_parse_args( $settings, $defaults );
	}

	/**
	 * Restituisce la lista dei ruoli disponibili incluso guest.
	 *
	 * @return array<string, string> Associativo slug => label.
	 */
	public static function get_available_roles(): array {
		$roles = array(
			'guest' => __( 'Guest (not logged in)', 'rb-content-hider' ),
		);

		$wp_roles = wp_roles();
		foreach ( $wp_roles->role_names as $slug => $name ) {
			$roles[ $slug ] = translate_user_role( $name );
		}

		return $roles;
	}

	/**
	 * Verifica se il contenuto deve essere nascosto per l'utente corrente.
	 *
	 * @param array<int, string> $hidden_roles Ruoli per cui nascondere il contenuto.
	 * @return bool True se il contenuto deve essere nascosto.
	 */
	public static function should_hide_for_current_user( array $hidden_roles ): bool {
		if ( empty( $hidden_roles ) ) {
			return false;
		}

		$settings = self::get_settings();

		// Se l'opzione 'hide_from_admins' è disattivata, non nascondere mai agli admin.
		if ( empty( $settings['hide_from_admins'] ) && current_user_can( 'manage_options' ) ) {
			return false;
		}

		// Controlla utente guest.
		if ( ! is_user_logged_in() ) {
			return in_array( 'guest', $hidden_roles, true );
		}

		// Controlla ruoli dell'utente corrente.
		$user = wp_get_current_user();
		foreach ( $user->roles as $role ) {
			if ( in_array( $role, $hidden_roles, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Impedisci clonazione.
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Impedisci deserializzazione.
	 *
	 * @return void
	 * @throws \Exception Sempre.
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton' );
	}
}
