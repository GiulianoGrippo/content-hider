<?php
/**
 * Gestione visibilità widget per ruolo.
 *
 * @package RBContentHider\Admin
 */

namespace RBContentHider\Admin;

use RBContentHider\Plugin;

/**
 * Classe WidgetVisibility.
 *
 * Aggiunge controlli di visibilità ai widget di WordPress.
 */
class WidgetVisibility {

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
		// Aggiunge il form nella configurazione widget.
		add_action( 'in_widget_form', array( $this, 'render_form' ), 10, 3 );

		// Salva le impostazioni del widget.
		add_filter( 'widget_update_callback', array( $this, 'save_settings' ), 10, 4 );

		// Filtra la visualizzazione del widget nel frontend.
		if ( ! is_admin() ) {
			add_filter( 'widget_display_callback', array( $this, 'filter_widget_display' ), 10, 3 );
		}
	}

	/**
	 * Renderizza il form dei checkbox nella configurazione widget.
	 *
	 * @param \WP_Widget $widget   Istanza del widget.
	 * @param null       $return   Valore di ritorno.
	 * @param array      $instance Impostazioni correnti del widget.
	 * @return void
	 */
	public function render_form( $widget, $return, $instance ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInMiddle
		$hidden_roles = isset( $instance['rbch_hidden_roles'] ) ? (array) $instance['rbch_hidden_roles'] : array();
		$roles        = Plugin::get_available_roles();
		$field_name   = $widget->get_field_name( 'rbch_hidden_roles' );
		$field_id     = $widget->get_field_id( 'rbch_hidden_roles' );

		wp_nonce_field( 'rbch_widget_nonce', 'rbch_widget_nonce_field' );
		?>
		<div class="rbch-widget-visibility">
			<p>
				<strong><?php esc_html_e( 'Hide from roles:', 'rb-content-hider' ); ?></strong>
			</p>
			<div class="rbch-widget-roles">
				<?php foreach ( $roles as $role_slug => $role_name ) : ?>
					<label style="display: block; margin-bottom: 4px;">
						<input
							type="checkbox"
							name="<?php echo esc_attr( $field_name ); ?>[]"
							id="<?php echo esc_attr( $field_id . '-' . $role_slug ); ?>"
							value="<?php echo esc_attr( $role_slug ); ?>"
							<?php checked( in_array( $role_slug, $hidden_roles, true ) ); ?>
						/>
						<?php echo esc_html( $role_name ); ?>
					</label>
				<?php endforeach; ?>
			</div>
			<hr />
		</div>
		<?php
	}

	/**
	 * Salva le impostazioni di visibilità del widget.
	 *
	 * @param array      $instance     Nuove impostazioni.
	 * @param array      $new_instance Nuove impostazioni raw.
	 * @param array      $old_instance Vecchie impostazioni.
	 * @param \WP_Widget $widget       Istanza del widget.
	 * @return array Impostazioni aggiornate.
	 */
	public function save_settings( $instance, $new_instance, $old_instance, $widget ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( isset( $_POST['rbch_widget_nonce_field'] ) && ! wp_verify_nonce(
			sanitize_text_field( wp_unslash( $_POST['rbch_widget_nonce_field'] ) ),
			'rbch_widget_nonce'
		) ) {
			return $instance;
		}

		if ( isset( $new_instance['rbch_hidden_roles'] ) && is_array( $new_instance['rbch_hidden_roles'] ) ) {
			$instance['rbch_hidden_roles'] = array_map( 'sanitize_text_field', $new_instance['rbch_hidden_roles'] );
		} else {
			$instance['rbch_hidden_roles'] = array();
		}

		return $instance;
	}

	/**
	 * Filtra la visualizzazione del widget nel frontend.
	 *
	 * @param array      $instance Impostazioni del widget.
	 * @param \WP_Widget $widget   Istanza del widget.
	 * @param array      $args     Argomenti di visualizzazione.
	 * @return array|false False per nascondere il widget.
	 */
	public function filter_widget_display( $instance, $widget, $args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( empty( $instance['rbch_hidden_roles'] ) || ! is_array( $instance['rbch_hidden_roles'] ) ) {
			return $instance;
		}

		$hidden_roles = $instance['rbch_hidden_roles'];

		/**
		 * Filtra i ruoli nascosti per un widget specifico.
		 *
		 * @param array      $hidden_roles Ruoli per cui nascondere il widget.
		 * @param array      $instance Impostazioni del widget.
		 * @param \WP_Widget $widget Istanza del widget.
		 */
		$hidden_roles = apply_filters( 'rbch_widget_hidden_roles', $hidden_roles, $instance, $widget );

		if ( Plugin::should_hide_for_current_user( $hidden_roles ) ) {
			return false;
		}

		return $instance;
	}

	/**
	 * Restituisce tutti i widget con restrizioni di ruolo.
	 *
	 * @return array<int, array{name: string, sidebar: string, roles: array}>
	 */
	public static function get_restricted_widgets(): array {
		$restricted = array();

		$sidebars_widgets = wp_get_sidebars_widgets();
		$registered       = isset( $GLOBALS['wp_registered_widgets'] ) ? $GLOBALS['wp_registered_widgets'] : array();
		$registered_sb    = isset( $GLOBALS['wp_registered_sidebars'] ) ? $GLOBALS['wp_registered_sidebars'] : array();

		foreach ( $sidebars_widgets as $sidebar_id => $widgets ) {
			if ( 'wp_inactive_widgets' === $sidebar_id || ! is_array( $widgets ) ) {
				continue;
			}

			foreach ( $widgets as $widget_id ) {
				if ( ! isset( $registered[ $widget_id ] ) ) {
					continue;
				}

				$widget_obj = $registered[ $widget_id ];
				$callback   = $widget_obj['callback'] ?? null;

				if ( ! $callback || ! is_array( $callback ) || ! isset( $callback[0] ) ) {
					continue;
				}

				$widget_instance = $callback[0];
				if ( ! ( $widget_instance instanceof \WP_Widget ) ) {
					continue;
				}

				$settings = $widget_instance->get_settings();
				$number   = $widget_obj['params'][0]['number'] ?? 0;

				if ( isset( $settings[ $number ]['rbch_hidden_roles'] )
					&& ! empty( $settings[ $number ]['rbch_hidden_roles'] ) ) {

					$sidebar_name = isset( $registered_sb[ $sidebar_id ] )
						? $registered_sb[ $sidebar_id ]['name']
						: $sidebar_id;

					$restricted[] = array(
						'name'    => $widget_obj['name'] ?? $widget_id,
						'sidebar' => $sidebar_name,
						'roles'   => $settings[ $number ]['rbch_hidden_roles'],
					);
				}
			}
		}

		return $restricted;
	}
}
