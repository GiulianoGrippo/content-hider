<?php
/**
 * Pagina impostazioni del plugin.
 *
 * @package RBContentHider\Admin
 */

namespace RBContentHider\Admin;

use RBContentHider\Plugin;

/**
 * Classe SettingsPage.
 *
 * Gestisce la pagina di impostazioni nell'admin di WordPress.
 */
class SettingsPage {

	/**
	 * Slug della pagina.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'rb-content-hider';

	/**
	 * Gruppo opzioni.
	 *
	 * @var string
	 */
	const OPTION_GROUP = 'rbch_settings_group';

	/**
	 * Nome opzione.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'rbch_settings';

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
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Aggiunge la pagina al menu Impostazioni.
	 *
	 * @return void
	 */
	public function add_menu_page(): void {
		add_options_page(
			__( 'Content Hider Settings', 'rb-content-hider' ),
			__( 'Content Hider', 'rb-content-hider' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Registra le impostazioni.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			self::OPTION_GROUP,
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => array(
					'hide_from_admins' => 0,
					'debug_mode'       => 0,
				),
			)
		);
	}

	/**
	 * Sanitizza le impostazioni salvate.
	 *
	 * @param array $input Input dall'utente.
	 * @return array Impostazioni sanitizzate.
	 */
	public function sanitize_settings( $input ): array {
		$sanitized = array();

		$sanitized['hide_from_admins'] = ! empty( $input['hide_from_admins'] ) ? 1 : 0;
		$sanitized['debug_mode']       = ! empty( $input['debug_mode'] ) ? 1 : 0;

		return $sanitized;
	}

	/**
	 * Renderizza la pagina impostazioni.
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'rb-content-hider' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'overview';
		?>
		<div class="wrap rbch-settings-wrap">
			<h1><?php esc_html_e( 'Role Based Content Hider', 'rb-content-hider' ); ?></h1>

			<nav class="nav-tab-wrapper rbch-tabs">
				<a href="<?php echo esc_url( admin_url( 'options-general.php?page=' . self::PAGE_SLUG . '&tab=overview' ) ); ?>" class="nav-tab <?php echo 'overview' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Overview', 'rb-content-hider' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'options-general.php?page=' . self::PAGE_SLUG . '&tab=guide' ) ); ?>" class="nav-tab <?php echo 'guide' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Usage Guide', 'rb-content-hider' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'options-general.php?page=' . self::PAGE_SLUG . '&tab=settings' ) ); ?>" class="nav-tab <?php echo 'settings' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Settings', 'rb-content-hider' ); ?>
				</a>
			</nav>

			<div class="rbch-tab-content">
				<?php
				switch ( $active_tab ) {
					case 'guide':
						$this->render_guide_tab();
						break;
					case 'settings':
						$this->render_settings_tab();
						break;
					default:
						$this->render_overview_tab();
						break;
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Renderizza il tab panoramica.
	 *
	 * @return void
	 */
	private function render_overview_tab(): void {
		$restricted_menus   = MenuVisibility::get_restricted_items();
		$restricted_widgets = WidgetVisibility::get_restricted_widgets();
		$all_roles          = Plugin::get_available_roles();
		?>
		<div class="rbch-overview">
			<h2><?php esc_html_e( 'Restricted Menu Items', 'rb-content-hider' ); ?></h2>

			<?php if ( empty( $restricted_menus ) ) : ?>
				<p class="rbch-no-items">
					<?php esc_html_e( 'No menu items have role-based restrictions.', 'rb-content-hider' ); ?>
				</p>
			<?php else : ?>
				<table class="widefat striped rbch-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Menu Item', 'rb-content-hider' ); ?></th>
							<th><?php esc_html_e( 'Menu', 'rb-content-hider' ); ?></th>
							<th><?php esc_html_e( 'Hidden from', 'rb-content-hider' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $restricted_menus as $item ) : ?>
							<tr>
								<td><?php echo esc_html( $item['title'] ); ?></td>
								<td><?php echo esc_html( $item['menu'] ); ?></td>
								<td>
									<?php
									$role_labels = array();
									foreach ( $item['roles'] as $role_slug ) {
										$role_labels[] = $all_roles[ $role_slug ] ?? $role_slug;
									}
									echo esc_html( implode( ', ', $role_labels ) );
									?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Restricted Widgets', 'rb-content-hider' ); ?></h2>

			<?php if ( empty( $restricted_widgets ) ) : ?>
				<p class="rbch-no-items">
					<?php esc_html_e( 'No widgets have role-based restrictions.', 'rb-content-hider' ); ?>
				</p>
			<?php else : ?>
				<table class="widefat striped rbch-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Widget', 'rb-content-hider' ); ?></th>
							<th><?php esc_html_e( 'Sidebar', 'rb-content-hider' ); ?></th>
							<th><?php esc_html_e( 'Hidden from', 'rb-content-hider' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $restricted_widgets as $widget ) : ?>
							<tr>
								<td><?php echo esc_html( $widget['name'] ); ?></td>
								<td><?php echo esc_html( $widget['sidebar'] ); ?></td>
								<td>
									<?php
									$role_labels = array();
									foreach ( $widget['roles'] as $role_slug ) {
										$role_labels[] = $all_roles[ $role_slug ] ?? $role_slug;
									}
									echo esc_html( implode( ', ', $role_labels ) );
									?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Renderizza il tab guida all'uso.
	 *
	 * @return void
	 */
	private function render_guide_tab(): void {
		?>
		<div class="rbch-guide">
			<h2><?php esc_html_e( 'Shortcode Usage', 'rb-content-hider' ); ?></h2>

			<div class="rbch-guide-section">
				<h3><?php esc_html_e( 'Hide content from specific roles', 'rb-content-hider' ); ?></h3>
				<p><?php esc_html_e( 'Use the "roles" parameter to specify which roles should NOT see the content:', 'rb-content-hider' ); ?></p>
				<code>[rb_hide roles="subscriber,guest"]<?php esc_html_e( 'This content is hidden from subscribers and guests.', 'rb-content-hider' ); ?>[/rb_hide]</code>
			</div>

			<div class="rbch-guide-section">
				<h3><?php esc_html_e( 'Show content only to specific roles', 'rb-content-hider' ); ?></h3>
				<p><?php esc_html_e( 'Use the "show_to" parameter to specify which roles CAN see the content:', 'rb-content-hider' ); ?></p>
				<code>[rb_hide show_to="administrator,editor"]<?php esc_html_e( 'Only admins and editors can see this.', 'rb-content-hider' ); ?>[/rb_hide]</code>
			</div>

			<div class="rbch-guide-section">
				<h3><?php esc_html_e( 'Available roles', 'rb-content-hider' ); ?></h3>
				<ul class="rbch-roles-list">
					<?php foreach ( Plugin::get_available_roles() as $slug => $name ) : ?>
						<li><code><?php echo esc_html( $slug ); ?></code> — <?php echo esc_html( $name ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>

			<div class="rbch-guide-section">
				<h3><?php esc_html_e( 'Nested shortcodes', 'rb-content-hider' ); ?></h3>
				<p><?php esc_html_e( 'You can nest other shortcodes inside [rb_hide]:', 'rb-content-hider' ); ?></p>
				<code>[rb_hide show_to="administrator"][gallery ids="1,2,3"][/rb_hide]</code>
			</div>

			<div class="rbch-guide-section">
				<h3><?php esc_html_e( 'Menu Items', 'rb-content-hider' ); ?></h3>
				<p>
					<?php
					esc_html_e(
						'Go to Appearance > Menus, expand a menu item, and check the roles that should NOT see it.',
						'rb-content-hider'
					);
					?>
				</p>
			</div>

			<div class="rbch-guide-section">
				<h3><?php esc_html_e( 'Widgets', 'rb-content-hider' ); ?></h3>
				<p>
					<?php
					esc_html_e(
						'Go to Appearance > Widgets, expand a widget, and check the roles that should NOT see it.',
						'rb-content-hider'
					);
					?>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Renderizza il tab impostazioni generali.
	 *
	 * @return void
	 */
	private function render_settings_tab(): void {
		$settings = Plugin::get_settings();
		?>
		<div class="rbch-settings">
			<form method="post" action="options.php">
				<?php settings_fields( self::OPTION_GROUP ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Apply to administrators', 'rb-content-hider' ); ?>
						</th>
						<td>
							<label>
								<input
									type="checkbox"
									name="<?php echo esc_attr( self::OPTION_NAME ); ?>[hide_from_admins]"
									value="1"
									<?php checked( $settings['hide_from_admins'], 1 ); ?>
								/>
								<?php esc_html_e( 'Also hide content from administrators when their role is selected.', 'rb-content-hider' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'By default, administrators always see all content regardless of visibility settings.', 'rb-content-hider' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Debug mode', 'rb-content-hider' ); ?>
						</th>
						<td>
							<label>
								<input
									type="checkbox"
									name="<?php echo esc_attr( self::OPTION_NAME ); ?>[debug_mode]"
									value="1"
									<?php checked( $settings['debug_mode'], 1 ); ?>
								/>
								<?php esc_html_e( 'Enable debug mode (shows HTML comments in source code for hidden elements).', 'rb-content-hider' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'Only visible to administrators. Useful for troubleshooting visibility rules.', 'rb-content-hider' ); ?>
							</p>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
