<?php
/**
 * Gestione shortcode condizionale.
 *
 * @package RBContentHider\Frontend
 */

namespace RBContentHider\Frontend;

use RBContentHider\Plugin;

/**
 * Classe Shortcodes.
 *
 * Registra e gestisce lo shortcode [rb_hide].
 */
class Shortcodes {

	/**
	 * Costruttore.
	 */
	public function __construct() {
		$this->register_hooks();
	}

	/**
	 * Registra gli hook WordPress.
	 *
	 * @return void
	 */
	private function register_hooks(): void {
		add_shortcode( 'rb_hide', array( $this, 'handle_shortcode' ) );
	}

	/**
	 * Gestisce lo shortcode [rb_hide].
	 *
	 * Uso con 'roles' (nasconde a questi ruoli):
	 *   [rb_hide roles="subscriber,guest"]Contenuto nascosto[/rb_hide]
	 *
	 * Uso con 'show_to' (mostra solo a questi ruoli):
	 *   [rb_hide show_to="administrator,editor"]Solo per admin e editor[/rb_hide]
	 *
	 * @param array|string $atts Attributi dello shortcode.
	 * @param string|null  $content Contenuto dello shortcode.
	 * @return string Contenuto filtrato.
	 */
	public function handle_shortcode( $atts, ?string $content = null ): string {
		if ( null === $content ) {
			return '';
		}

		$atts = shortcode_atts(
			array(
				'roles'   => '',
				'show_to' => '',
			),
			$atts,
			'rb_hide'
		);

		$should_hide = false;

		if ( ! empty( $atts['show_to'] ) ) {
			// Modalità "mostra solo a": mostra il contenuto solo ai ruoli specificati.
			$show_roles = $this->parse_roles( $atts['show_to'] );

			/**
			 * Filtra i ruoli a cui mostrare il contenuto dello shortcode.
			 *
			 * @param array  $show_roles Ruoli a cui mostrare il contenuto.
			 * @param string $content Il contenuto dello shortcode.
			 */
			$show_roles = apply_filters( 'rbch_shortcode_show_roles', $show_roles, $content );

			$should_hide = ! $this->current_user_has_role( $show_roles );
		} elseif ( ! empty( $atts['roles'] ) ) {
			// Modalità "nascondi a": nasconde il contenuto ai ruoli specificati.
			$hidden_roles = $this->parse_roles( $atts['roles'] );

			/**
			 * Filtra i ruoli a cui nascondere il contenuto dello shortcode.
			 *
			 * @param array  $hidden_roles Ruoli a cui nascondere il contenuto.
			 * @param string $content Il contenuto dello shortcode.
			 */
			$hidden_roles = apply_filters( 'rbch_shortcode_hidden_roles', $hidden_roles, $content );

			$should_hide = Plugin::should_hide_for_current_user( $hidden_roles );
		}

		/**
		 * Filtra se il contenuto dello shortcode deve essere nascosto.
		 *
		 * @param bool   $should_hide Se nascondere il contenuto.
		 * @param array  $atts Attributi dello shortcode.
		 * @param string $content Il contenuto.
		 */
		$should_hide = apply_filters( 'rbch_shortcode_should_hide', $should_hide, $atts, $content );

		if ( $should_hide ) {
			return '';
		}

		// Supporta shortcode annidati.
		return do_shortcode( $content );
	}

	/**
	 * Parsa una stringa di ruoli separati da virgola.
	 *
	 * @param string $roles_string Stringa di ruoli separati da virgola.
	 * @return array<int, string> Array di ruoli sanitizzati.
	 */
	private function parse_roles( string $roles_string ): array {
		$roles = explode( ',', $roles_string );
		$roles = array_map( 'trim', $roles );
		$roles = array_map( 'sanitize_text_field', $roles );
		$roles = array_filter( $roles );

		return array_values( $roles );
	}

	/**
	 * Verifica se l'utente corrente ha uno dei ruoli specificati.
	 *
	 * @param array<int, string> $roles Ruoli da verificare.
	 * @return bool True se l'utente ha almeno un ruolo nella lista.
	 */
	private function current_user_has_role( array $roles ): bool {
		if ( empty( $roles ) ) {
			return false;
		}

		// Controlla guest.
		if ( ! is_user_logged_in() ) {
			return in_array( 'guest', $roles, true );
		}

		$user = wp_get_current_user();
		foreach ( $user->roles as $role ) {
			if ( in_array( $role, $roles, true ) ) {
				return true;
			}
		}

		return false;
	}
}
