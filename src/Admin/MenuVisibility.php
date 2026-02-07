<?php
/**
 * Gestione visibilità menu items per ruolo.
 *
 * @package RBContentHider\Admin
 */

namespace RBContentHider\Admin;

use RBContentHider\Plugin;

/**
 * Classe MenuVisibility.
 *
 * Aggiunge controlli di visibilità ai menu items di WordPress.
 */
class MenuVisibility {

	/**
	 * Meta key per salvare i ruoli nascosti.
	 *
	 * @var string
	 */
	const META_KEY = '_rb_hidden_roles';

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
		// Aggiunge i campi personalizzati nell'editor dei menu.
		add_filter( 'wp_setup_nav_menu_item', array( $this, 'add_custom_fields' ) );
		add_action( 'wp_nav_menu_item_custom_fields', array( $this, 'render_fields' ), 10, 5 );
		add_action( 'wp_update_nav_menu_item', array( $this, 'save_fields' ), 10, 3 );

		// Filtra i menu items nel frontend.
		if ( ! is_admin() ) {
			add_filter( 'wp_get_nav_menu_items', array( $this, 'filter_menu_items' ), 20 );
		}
	}

	/**
	 * Aggiunge la proprietà personalizzata al menu item.
	 *
	 * @param \WP_Post $menu_item Il menu item.
	 * @return \WP_Post
	 */
	public function add_custom_fields( $menu_item ) {
		$menu_item->rb_hidden_roles = get_post_meta( $menu_item->ID, self::META_KEY, true );
		if ( ! is_array( $menu_item->rb_hidden_roles ) ) {
			$menu_item->rb_hidden_roles = array();
		}
		return $menu_item;
	}

	/**
	 * Renderizza i campi checkbox nella schermata di modifica menu.
	 *
	 * @param int        $item_id           ID del menu item.
	 * @param \WP_Post   $menu_item         Oggetto menu item.
	 * @param int        $depth             Profondità del menu.
	 * @param \stdClass  $args              Argomenti del walker.
	 * @param int        $current_object_id ID oggetto corrente.
	 * @return void
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function render_fields( $item_id, $menu_item, $depth, $args, $current_object_id ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$hidden_roles = get_post_meta( $item_id, self::META_KEY, true );
		if ( ! is_array( $hidden_roles ) ) {
			$hidden_roles = array();
		}

		$roles = Plugin::get_available_roles();

		wp_nonce_field( 'rbch_menu_nonce_' . $item_id, 'rbch_menu_nonce_' . $item_id );
		?>
		<fieldset class="field-rb-visibility description description-wide rbch-menu-fieldset">
			<legend class="description">
				<strong><?php esc_html_e( 'Hide from roles', 'rb-content-hider' ); ?></strong>
			</legend>
			<div class="rbch-roles-checkboxes">
				<?php foreach ( $roles as $role_slug => $role_name ) : ?>
					<label class="rbch-role-label">
						<input
							type="checkbox"
							name="rbch_menu_roles[<?php echo esc_attr( $item_id ); ?>][]"
							value="<?php echo esc_attr( $role_slug ); ?>"
							<?php checked( in_array( $role_slug, $hidden_roles, true ) ); ?>
						/>
						<?php echo esc_html( $role_name ); ?>
					</label>
				<?php endforeach; ?>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Salva le impostazioni di visibilità del menu item.
	 *
	 * @param int   $menu_id ID del menu.
	 * @param int   $item_id ID del menu item.
	 * @param array $args    Argomenti del menu item.
	 * @return void
	 */
	public function save_fields( $menu_id, $item_id, $args ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundBeforeLastUsed
		$nonce_key = 'rbch_menu_nonce_' . $item_id;

		if ( ! isset( $_POST[ $nonce_key ] ) || ! wp_verify_nonce(
			sanitize_text_field( wp_unslash( $_POST[ $nonce_key ] ) ),
			'rbch_menu_nonce_' . $item_id
		) ) {
			return;
		}

		if ( isset( $_POST['rbch_menu_roles'][ $item_id ] ) && is_array( $_POST['rbch_menu_roles'][ $item_id ] ) ) {
			$roles = array_map( 'sanitize_text_field', wp_unslash( $_POST['rbch_menu_roles'][ $item_id ] ) );
			update_post_meta( $item_id, self::META_KEY, $roles );
		} else {
			delete_post_meta( $item_id, self::META_KEY );
		}
	}

	/**
	 * Filtra i menu items nel frontend in base al ruolo dell'utente.
	 *
	 * @param array $items Array di menu items.
	 * @return array Menu items filtrati.
	 */
	public function filter_menu_items( $items ) {
		if ( is_admin() ) {
			return $items;
		}

		$filtered = array();
		$hidden_ids = array();

		foreach ( $items as $item ) {
			$hidden_roles = get_post_meta( $item->ID, self::META_KEY, true );
			if ( ! is_array( $hidden_roles ) ) {
				$hidden_roles = array();
			}

			/**
			 * Filtra i ruoli nascosti per un menu item specifico.
			 *
			 * @param array    $hidden_roles Ruoli per cui nascondere il menu item.
			 * @param \WP_Post $item Il menu item.
			 */
			$hidden_roles = apply_filters( 'rbch_menu_hidden_roles', $hidden_roles, $item );

			if ( Plugin::should_hide_for_current_user( $hidden_roles ) ) {
				$hidden_ids[] = $item->ID;
				continue;
			}

			// Nascondi anche i figli di un menu item nascosto.
			if ( ! empty( $item->menu_item_parent ) && in_array( (int) $item->menu_item_parent, $hidden_ids, true ) ) {
				$hidden_ids[] = $item->ID;
				continue;
			}

			$filtered[] = $item;
		}

		return $filtered;
	}

	/**
	 * Restituisce tutti i menu items con restrizioni di ruolo.
	 *
	 * Usato dalla pagina di impostazioni per la panoramica.
	 *
	 * @return array<int, array{id: int, title: string, menu: string, roles: array}>
	 */
	public static function get_restricted_items(): array {
		$restricted = array();

		$menus = wp_get_nav_menus();
		foreach ( $menus as $menu ) {
			$items = wp_get_nav_menu_items( $menu->term_id );
			if ( ! $items ) {
				continue;
			}

			foreach ( $items as $item ) {
				$hidden_roles = get_post_meta( $item->ID, '_rb_hidden_roles', true );
				if ( ! empty( $hidden_roles ) && is_array( $hidden_roles ) ) {
					$restricted[] = array(
						'id'    => $item->ID,
						'title' => $item->title,
						'menu'  => $menu->name,
						'roles' => $hidden_roles,
					);
				}
			}
		}

		return $restricted;
	}
}
