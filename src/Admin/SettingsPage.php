<?php
/**
 * Admin Settings Page for FA WPMCP.
 *
 * @package FAWpmcp\Admin
 */

declare(strict_types=1);

namespace FAWpmcp\Admin;

use FAWpmcp\Abilities\AbilityRegistry;

/**
 * Admin settings page for managing plugin configuration.
 *
 * Provides UI for:
 * - Permission toggles (global, category, ability levels)
 * - Rate limit configuration
 * - Webhook configuration
 *
 * @package FAWpmcp\Admin
 */
final class SettingsPage {
	/**
	 * Menu slug for the settings page.
	 *
	 * @var string
	 */
	private const MENU_SLUG = 'fa-wpmcp';

	/**
	 * Required capability to access settings.
	 *
	 * @var string
	 */
	private const CAPABILITY = 'manage_options';

	/**
	 * Nonce action for settings forms.
	 *
	 * @var string
	 */
	private const NONCE_ACTION = 'fa_wpmcp_settings';

	/**
	 * Nonce field name.
	 *
	 * @var string
	 */
	private const NONCE_NAME = 'fa_wpmcp_nonce';

	/**
	 * Available webhook events.
	 *
	 * @var array<string>
	 */
	private const WEBHOOK_EVENTS = array(
		'ability.before_execute',
		'ability.after_execute',
		'ability.error',
	);

	/**
	 * Error message: Permission denied to access page.
	 *
	 * @var string
	 */
	private const MSG_NO_ACCESS = 'You do not have permission to access this page.';

	/**
	 * Error title: Permission denied.
	 *
	 * @var string
	 */
	private const MSG_PERMISSION_DENIED = 'Permission Denied';

	/**
	 * Error message: Security check failed.
	 *
	 * @var string
	 */
	private const MSG_SECURITY_FAILED = 'Security check failed. Please try again.';

	/**
	 * Error title: Security error.
	 *
	 * @var string
	 */
	private const MSG_SECURITY_ERROR = 'Security Error';

	/**
	 * Error message: No permission to perform action.
	 *
	 * @var string
	 */
	private const MSG_NO_PERMISSION = 'You do not have permission to perform this action.';

	/**
	 * Ability registry.
	 *
	 * @var AbilityRegistry
	 */
	private AbilityRegistry $registry;

	/**
	 * Constructor.
	 *
	 * @param AbilityRegistry $registry Ability registry instance.
	 */
	public function __construct( AbilityRegistry $registry ) {
		$this->registry = $registry;
	}

	/**
	 * Initialize the settings page.
	 *
	 * Registers WordPress hooks for admin menu and form handling.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_fa_wpmcp_save_settings', array( $this, 'handle_settings_save' ) );
		add_action( 'admin_post_fa_wpmcp_save_permissions', array( $this, 'handle_permissions_save' ) );
		add_action( 'admin_post_fa_wpmcp_save_rate_limits', array( $this, 'handle_rate_limits_save' ) );
		add_action( 'admin_post_fa_wpmcp_save_webhooks', array( $this, 'handle_webhooks_save' ) );
	}

	/**
	 * Get the menu slug.
	 *
	 * @return string Menu slug.
	 */
	public function get_menu_slug(): string {
		return self::MENU_SLUG;
	}

	/**
	 * Get the required capability.
	 *
	 * @return string Capability name.
	 */
	public function get_capability(): string {
		return self::CAPABILITY;
	}

	/**
	 * Register admin menu and submenu pages.
	 *
	 * @return void
	 */
	public function register_menu(): void {
		add_menu_page(
			'FA WPMCP',
			'FA WPMCP',
			self::CAPABILITY,
			self::MENU_SLUG,
			array( $this, 'render_settings_page' ),
			'dashicons-admin-generic',
			null
		);

		add_submenu_page(
			self::MENU_SLUG,
			'Permissions',
			'Permissions',
			self::CAPABILITY,
			'fa-wpmcp-permissions',
			array( $this, 'render_permissions_page' )
		);

		add_submenu_page(
			self::MENU_SLUG,
			'Rate Limits',
			'Rate Limits',
			self::CAPABILITY,
			'fa-wpmcp-rate-limits',
			array( $this, 'render_rate_limits_page' )
		);

		add_submenu_page(
			self::MENU_SLUG,
			'Webhooks',
			'Webhooks',
			self::CAPABILITY,
			'fa-wpmcp-webhooks',
			array( $this, 'render_webhooks_page' )
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * Only loads assets on plugin settings pages.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		$plugin_pages = array(
			'toplevel_page_fa-wpmcp',
			'fa-wpmcp_page_fa-wpmcp-permissions',
			'fa-wpmcp_page_fa-wpmcp-rate-limits',
			'fa-wpmcp_page_fa-wpmcp-webhooks',
		);

		if ( ! in_array( $hook_suffix, $plugin_pages, true ) ) {
			return;
		}

		wp_enqueue_style(
			'fa-wpmcp-admin',
			FA_WPMCP_URL . 'assets/css/admin.css',
			array(),
			FA_WPMCP_VERSION
		);

		wp_enqueue_script(
			'fa-wpmcp-admin',
			FA_WPMCP_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			FA_WPMCP_VERSION,
			true
		);
	}

	/**
	 * Render the main settings page.
	 *
	 * @return string Rendered HTML.
	 */
	public function render_settings_page(): string {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die(
				self::MSG_NO_ACCESS,
				self::MSG_PERMISSION_DENIED,
				array( 'response' => 403 )
			);
			return '';
		}

		$output = '<div class="wrap">';
		$output .= '<h1>' . esc_html( 'FA WPMCP Settings' ) . '</h1>';

		// Success/error notices.
		$output .= $this->render_notices();

		$output .= '<form method="post" action="' . esc_attr( admin_url( 'admin-post.php' ) ) . '">';
		$output .= '<input type="hidden" name="action" value="fa_wpmcp_save_settings" />';
		$output .= wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME, true, false );

		$output .= '<p>' . esc_html( 'Welcome to FA WPMCP. Use the submenus to configure permissions, rate limits, and webhooks.' ) . '</p>';

		$output .= '<p class="submit">';
		$output .= '<input type="submit" name="submit" class="button button-primary" value="' . esc_attr( 'Save Settings' ) . '" />';
		$output .= '</p>';

		$output .= '</form>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * Render the permissions settings page.
	 *
	 * @return string Rendered HTML.
	 */
	public function render_permissions_page(): string {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die(
				self::MSG_NO_ACCESS,
				self::MSG_PERMISSION_DENIED,
				array( 'response' => 403 )
			);
			return '';
		}

		$settings = $this->get_permissions_settings();

		$output = '<div class="wrap">';
		$output .= '<h1>' . esc_html( 'Permission Settings' ) . '</h1>';

		$output .= '<form method="post" action="' . esc_attr( admin_url( 'admin-post.php' ) ) . '">';
		$output .= '<input type="hidden" name="action" value="fa_wpmcp_save_permissions" />';
		$output .= wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME, true, false );

		// Global permission toggles.
		$output .= '<h2>' . esc_html( 'Global Permissions' ) . '</h2>';
		$output .= '<table class="form-table">';

		$output .= '<tr>';
		$output .= '<th scope="row"><label for="global_read_enabled">' . esc_html( 'Enable Read Operations' ) . '</label></th>';
		$output .= '<td>';
		$output .= '<input type="checkbox" id="global_read_enabled" name="global_read_enabled" value="1" ';
		$output .= checked( $settings['global_read_enabled'] ?? true, true, false );
		$output .= ' />';
		$output .= '</td>';
		$output .= '</tr>';

		$output .= '<tr>';
		$output .= '<th scope="row"><label for="global_write_enabled">' . esc_html( 'Enable Write Operations' ) . '</label></th>';
		$output .= '<td>';
		$output .= '<input type="checkbox" id="global_write_enabled" name="global_write_enabled" value="1" ';
		$output .= checked( $settings['global_write_enabled'] ?? false, true, false );
		$output .= ' />';
		$output .= '</td>';
		$output .= '</tr>';

		$output .= '</table>';

		// Category-level permissions.
		$categories = $this->registry->categories();
		if ( ! empty( $categories ) ) {
			$output .= '<h2>' . esc_html( 'Category Permissions' ) . '</h2>';
			$output .= '<table class="form-table">';

			foreach ( $categories as $category ) {
				$category_settings = $settings['category_settings'][ $category ] ?? array();

				$output .= '<tr>';
				$output .= '<th scope="row">' . esc_html( ucwords( str_replace( '-', ' ', $category ) ) ) . '</th>';
				$output .= '<td>';

				$output .= '<label>';
				$output .= '<input type="checkbox" name="category_settings[' . esc_attr( $category ) . '][enable_read]" value="1" ';
				$output .= checked( $category_settings['enable_read'] ?? true, true, false );
				$output .= ' /> ';
				$output .= esc_html( 'Enable Read' );
				$output .= '</label> ';

				$output .= '<label>';
				$output .= '<input type="checkbox" name="category_settings[' . esc_attr( $category ) . '][enable_write]" value="1" ';
				$output .= checked( $category_settings['enable_write'] ?? true, true, false );
				$output .= ' /> ';
				$output .= esc_html( 'Enable Write' );
				$output .= '</label>';

				$output .= '</td>';
				$output .= '</tr>';
			}

			$output .= '</table>';
		}

		// Ability-level permissions.
		$abilities = $this->registry->all();
		if ( ! empty( $abilities ) ) {
			$output .= '<h2>' . esc_html( 'Ability Permissions' ) . '</h2>';
			$output .= '<table class="form-table">';

			foreach ( $abilities as $name => $ability ) {
				$ability_settings = $settings['ability_settings'][ $name ] ?? array();

				$output .= '<tr>';
				$output .= '<th scope="row">' . esc_html( $ability->get_label() ) . '</th>';
				$output .= '<td>';
				$output .= '<label>';
				$output .= '<input type="checkbox" name="ability_settings[' . esc_attr( $name ) . '][enabled]" value="1" ';
				$output .= checked( $ability_settings['enabled'] ?? true, true, false );
				$output .= ' /> ';
				$output .= esc_html( 'Enabled' );
				$output .= '</label>';
				$output .= '</td>';
				$output .= '</tr>';
			}

			$output .= '</table>';
		}

		$output .= '<p class="submit">';
		$output .= '<input type="submit" name="submit" class="button button-primary" value="' . esc_attr( 'Save Permissions' ) . '" />';
		$output .= '</p>';

		$output .= '</form>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * Render the rate limits settings page.
	 *
	 * @return string Rendered HTML.
	 */
	public function render_rate_limits_page(): string {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die(
				self::MSG_NO_ACCESS,
				self::MSG_PERMISSION_DENIED,
				array( 'response' => 403 )
			);
			return '';
		}

		$settings = $this->get_rate_limits_settings();

		$output = '<div class="wrap">';
		$output .= '<h1>' . esc_html( 'Rate Limit Settings' ) . '</h1>';

		$output .= '<form method="post" action="' . esc_attr( admin_url( 'admin-post.php' ) ) . '">';
		$output .= '<input type="hidden" name="action" value="fa_wpmcp_save_rate_limits" />';
		$output .= wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME, true, false );

		// Default rate limits.
		$output .= '<h2>' . esc_html( 'Default Rate Limits' ) . '</h2>';
		$output .= '<table class="form-table">';

		$output .= '<tr>';
		$output .= '<th scope="row"><label for="default_requests_per_minute">' . esc_html( 'Requests per Minute' ) . '</label></th>';
		$output .= '<td>';
		$output .= '<input type="number" id="default_requests_per_minute" name="default_requests_per_minute" ';
		$output .= 'value="' . esc_attr( (string) ( $settings['default_requests_per_minute'] ?? 60 ) ) . '" ';
		$output .= 'min="1" max="1000" />';
		$output .= '</td>';
		$output .= '</tr>';

		$output .= '<tr>';
		$output .= '<th scope="row"><label for="default_requests_per_hour">' . esc_html( 'Requests per Hour' ) . '</label></th>';
		$output .= '<td>';
		$output .= '<input type="number" id="default_requests_per_hour" name="default_requests_per_hour" ';
		$output .= 'value="' . esc_attr( (string) ( $settings['default_requests_per_hour'] ?? 500 ) ) . '" ';
		$output .= 'min="1" max="10000" />';
		$output .= '</td>';
		$output .= '</tr>';

		$output .= '</table>';

		// Per-ability rate limits.
		$abilities = $this->registry->all();
		if ( ! empty( $abilities ) ) {
			$output .= '<h2>' . esc_html( 'Per-Ability Rate Limits' ) . '</h2>';
			$output .= '<table class="form-table">';

			foreach ( $abilities as $name => $ability ) {
				$ability_limits = $settings['ability_rate_limits'][ $name ] ?? array();

				$output .= '<tr>';
				$output .= '<th scope="row">' . esc_html( $ability->get_label() ) . '</th>';
				$output .= '<td>';

				$output .= '<label>' . esc_html( 'Per minute: ' ) . '</label>';
				$output .= '<input type="number" name="ability_rate_limits[' . esc_attr( $name ) . '][requests_per_minute]" ';
				$output .= 'value="' . esc_attr( (string) ( $ability_limits['requests_per_minute'] ?? '' ) ) . '" ';
				$output .= 'min="0" max="1000" placeholder="' . esc_attr( 'Use default' ) . '" /> ';

				$output .= '<label>' . esc_html( 'Per hour: ' ) . '</label>';
				$output .= '<input type="number" name="ability_rate_limits[' . esc_attr( $name ) . '][requests_per_hour]" ';
				$output .= 'value="' . esc_attr( (string) ( $ability_limits['requests_per_hour'] ?? '' ) ) . '" ';
				$output .= 'min="0" max="10000" placeholder="' . esc_attr( 'Use default' ) . '" />';

				$output .= '</td>';
				$output .= '</tr>';
			}

			$output .= '</table>';
		}

		$output .= '<p class="submit">';
		$output .= '<input type="submit" name="submit" class="button button-primary" value="' . esc_attr( 'Save Rate Limits' ) . '" />';
		$output .= '</p>';

		$output .= '</form>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * Render the webhooks settings page.
	 *
	 * @return string Rendered HTML.
	 */
	public function render_webhooks_page(): string {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die(
				self::MSG_NO_ACCESS,
				self::MSG_PERMISSION_DENIED,
				array( 'response' => 403 )
			);
			return '';
		}

		$settings = $this->get_webhooks_settings();

		$output = '<div class="wrap">';
		$output .= '<h1>' . esc_html( 'Webhook Settings' ) . '</h1>';

		$output .= '<form method="post" action="' . esc_attr( admin_url( 'admin-post.php' ) ) . '">';
		$output .= '<input type="hidden" name="action" value="fa_wpmcp_save_webhooks" />';
		$output .= wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME, true, false );

		// Webhook secret.
		$output .= '<h2>' . esc_html( 'Webhook Secret' ) . '</h2>';
		$output .= '<table class="form-table">';

		$output .= '<tr>';
		$output .= '<th scope="row"><label for="webhook_secret">' . esc_html( 'Secret Key' ) . '</label></th>';
		$output .= '<td>';
		$output .= '<input type="password" id="webhook_secret" name="webhook_secret" ';
		$output .= 'value="' . esc_attr( $settings['webhook_secret'] ?? '' ) . '" ';
		$output .= 'class="regular-text" />';
		$output .= '<p class="description">' . esc_html( 'Used to sign webhook payloads for verification.' ) . '</p>';
		$output .= '</td>';
		$output .= '</tr>';

		$output .= '</table>';

		// Webhook endpoints.
		$output .= '<h2>' . esc_html( 'Webhook Endpoints' ) . '</h2>';

		$endpoints = $settings['webhook_endpoints'] ?? array();
		$endpoint_index = 0;

		if ( ! empty( $endpoints ) ) {
			foreach ( $endpoints as $endpoint ) {
				$output .= $this->render_webhook_endpoint_fields( $endpoint_index, $endpoint );
				++$endpoint_index;
			}
		}

		// Empty endpoint for adding new.
		$output .= $this->render_webhook_endpoint_fields( $endpoint_index, array() );

		$output .= '<p class="submit">';
		$output .= '<input type="submit" name="submit" class="button button-primary" value="' . esc_attr( 'Save Webhooks' ) . '" />';
		$output .= '</p>';

		$output .= '</form>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * Render webhook endpoint fields.
	 *
	 * @param int                  $index    Endpoint index.
	 * @param array<string, mixed> $endpoint Endpoint data.
	 * @return string Rendered HTML.
	 */
	private function render_webhook_endpoint_fields( int $index, array $endpoint ): string {
		$url = $endpoint['url'] ?? '';
		$events = $endpoint['events'] ?? array();

		$output = '<div class="webhook-endpoint" style="border: 1px solid #ccc; padding: 15px; margin-bottom: 15px;">';

		$output .= '<table class="form-table">';

		$output .= '<tr>';
		$output .= '<th scope="row"><label>' . esc_html( 'Endpoint URL' ) . '</label></th>';
		$output .= '<td>';
		$output .= '<input type="url" name="webhook_endpoints[' . esc_attr( (string) $index ) . '][url]" ';
		$output .= 'value="' . esc_url( $url ) . '" ';
		$output .= 'class="regular-text" placeholder="' . esc_attr( 'https://example.com/webhook' ) . '" />';
		$output .= '</td>';
		$output .= '</tr>';

		$output .= '<tr>';
		$output .= '<th scope="row">' . esc_html( 'Events' ) . '</th>';
		$output .= '<td>';

		foreach ( self::WEBHOOK_EVENTS as $event ) {
			$output .= '<label style="display: block; margin-bottom: 5px;">';
			$output .= '<input type="checkbox" name="webhook_endpoints[' . esc_attr( (string) $index ) . '][events][]" ';
			$output .= 'value="' . esc_attr( $event ) . '" ';
			$output .= checked( in_array( $event, $events, true ), true, false );
			$output .= ' /> ';
			$output .= esc_html( $event );
			$output .= '</label>';
		}

		$output .= '</td>';
		$output .= '</tr>';

		$output .= '</table>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * Render admin notices.
	 *
	 * @return string Rendered HTML.
	 */
	private function render_notices(): string {
		$output = '';

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Just displaying notice, no data processing.
		if ( isset( $_GET['settings-updated'] ) && 'true' === $_GET['settings-updated'] ) {
			$output .= '<div class="notice notice-success is-dismissible">';
			$output .= '<p>' . esc_html( 'Settings saved successfully.' ) . '</p>';
			$output .= '</div>';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Just displaying notice, no data processing.
		if ( isset( $_GET['settings-error'] ) && 'true' === $_GET['settings-error'] ) {
			$output .= '<div class="notice notice-error is-dismissible">';
			$output .= '<p>' . esc_html( 'There was an error saving your settings.' ) . '</p>';
			$output .= '</div>';
		}

		return $output;
	}

	/**
	 * Handle main settings save.
	 *
	 * @return void
	 */
	public function handle_settings_save(): void {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce is verified, not used for output.
		$nonce = isset( $_POST[ self::NONCE_NAME ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			wp_die(
				self::MSG_SECURITY_FAILED,
				self::MSG_SECURITY_ERROR,
				array( 'response' => 403 )
			);
			return;
		}

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die(
				self::MSG_NO_PERMISSION,
				self::MSG_PERMISSION_DENIED,
				array( 'response' => 403 )
			);
			return;
		}

		// Save general settings.
		$settings = array(
			'version' => FA_WPMCP_VERSION,
		);

		update_option( 'fa_wpmcp_settings', $settings );

		wp_safe_redirect(
			add_query_arg(
				array( 'settings-updated' => 'true' ),
				admin_url( 'admin.php?page=' . self::MENU_SLUG )
			)
		);
	}

	/**
	 * Handle permissions save.
	 *
	 * @return void
	 */
	public function handle_permissions_save(): void {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce is verified, not used for output.
		$nonce = isset( $_POST[ self::NONCE_NAME ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			wp_die(
				self::MSG_SECURITY_FAILED,
				self::MSG_SECURITY_ERROR,
				array( 'response' => 403 )
			);
			return;
		}

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die(
				self::MSG_NO_PERMISSION,
				self::MSG_PERMISSION_DENIED,
				array( 'response' => 403 )
			);
			return;
		}

		// Sanitize and save permission settings.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized in helper methods.
		$category_settings = isset( $_POST['category_settings'] ) ? map_deep( wp_unslash( $_POST['category_settings'] ), 'sanitize_text_field' ) : array();
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized in helper methods.
		$ability_settings = isset( $_POST['ability_settings'] ) ? map_deep( wp_unslash( $_POST['ability_settings'] ), 'sanitize_text_field' ) : array();

		$settings = array(
			'global_read_enabled'  => isset( $_POST['global_read_enabled'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['global_read_enabled'] ) ),
			'global_write_enabled' => isset( $_POST['global_write_enabled'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['global_write_enabled'] ) ),
			'category_settings'    => $this->sanitize_category_settings( $category_settings ),
			'ability_settings'     => $this->sanitize_ability_settings( $ability_settings ),
		);

		update_option( 'fa_wpmcp_permissions', $settings );

		wp_safe_redirect(
			add_query_arg(
				array( 'settings-updated' => 'true' ),
				admin_url( 'admin.php?page=fa-wpmcp-permissions' )
			)
		);
	}

	/**
	 * Handle rate limits save.
	 *
	 * @return void
	 */
	public function handle_rate_limits_save(): void {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce is verified, not used for output.
		$nonce = isset( $_POST[ self::NONCE_NAME ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			wp_die(
				self::MSG_SECURITY_FAILED,
				self::MSG_SECURITY_ERROR,
				array( 'response' => 403 )
			);
			return;
		}

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die(
				self::MSG_NO_PERMISSION,
				self::MSG_PERMISSION_DENIED,
				array( 'response' => 403 )
			);
			return;
		}

		// Sanitize and save rate limit settings.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized in helper method.
		$ability_rate_limits = isset( $_POST['ability_rate_limits'] ) ? map_deep( wp_unslash( $_POST['ability_rate_limits'] ), 'absint' ) : array();

		$settings = array(
			'default_requests_per_minute' => isset( $_POST['default_requests_per_minute'] ) ? absint( wp_unslash( $_POST['default_requests_per_minute'] ) ) : 60,
			'default_requests_per_hour'   => isset( $_POST['default_requests_per_hour'] ) ? absint( wp_unslash( $_POST['default_requests_per_hour'] ) ) : 500,
			'ability_rate_limits'         => $this->sanitize_ability_rate_limits( $ability_rate_limits ),
		);

		update_option( 'fa_wpmcp_rate_limits', $settings );

		wp_safe_redirect(
			add_query_arg(
				array( 'settings-updated' => 'true' ),
				admin_url( 'admin.php?page=fa-wpmcp-rate-limits' )
			)
		);
	}

	/**
	 * Handle webhooks save.
	 *
	 * @return void
	 */
	public function handle_webhooks_save(): void {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce is verified, not used for output.
		$nonce = isset( $_POST[ self::NONCE_NAME ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			wp_die(
				self::MSG_SECURITY_FAILED,
				self::MSG_SECURITY_ERROR,
				array( 'response' => 403 )
			);
			return;
		}

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die(
				self::MSG_NO_PERMISSION,
				self::MSG_PERMISSION_DENIED,
				array( 'response' => 403 )
			);
			return;
		}

		// Sanitize and save webhook settings.
		$webhook_secret = isset( $_POST['webhook_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['webhook_secret'] ) ) : '';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized in helper method.
		$webhook_endpoints = isset( $_POST['webhook_endpoints'] ) ? wp_unslash( $_POST['webhook_endpoints'] ) : array();

		$settings = array(
			'webhook_secret'    => $webhook_secret,
			'webhook_endpoints' => $this->sanitize_webhook_endpoints( $webhook_endpoints ),
		);

		update_option( 'fa_wpmcp_webhooks', $settings );

		wp_safe_redirect(
			add_query_arg(
				array( 'settings-updated' => 'true' ),
				admin_url( 'admin.php?page=fa-wpmcp-webhooks' )
			)
		);
	}

	/**
	 * Get permissions settings.
	 *
	 * @return array<string, mixed> Settings array.
	 */
	private function get_permissions_settings(): array {
		$defaults = array(
			'global_read_enabled'  => true,
			'global_write_enabled' => false,
			'category_settings'    => array(),
			'ability_settings'     => array(),
		);

		$settings = get_option( 'fa_wpmcp_permissions', $defaults );

		if ( ! is_array( $settings ) ) {
			return $defaults;
		}

		return array_merge( $defaults, $settings );
	}

	/**
	 * Get rate limits settings.
	 *
	 * @return array<string, mixed> Settings array.
	 */
	private function get_rate_limits_settings(): array {
		$defaults = array(
			'default_requests_per_minute' => 60,
			'default_requests_per_hour'   => 500,
			'ability_rate_limits'         => array(),
		);

		$settings = get_option( 'fa_wpmcp_rate_limits', $defaults );

		if ( ! is_array( $settings ) ) {
			return $defaults;
		}

		return array_merge( $defaults, $settings );
	}

	/**
	 * Get webhooks settings.
	 *
	 * @return array<string, mixed> Settings array.
	 */
	private function get_webhooks_settings(): array {
		$defaults = array(
			'webhook_secret'    => '',
			'webhook_endpoints' => array(),
		);

		$settings = get_option( 'fa_wpmcp_webhooks', $defaults );

		if ( ! is_array( $settings ) ) {
			return $defaults;
		}

		return array_merge( $defaults, $settings );
	}

	/**
	 * Sanitize category settings.
	 *
	 * @param mixed $input Raw input data.
	 * @return array<string, array<string, bool>> Sanitized settings.
	 */
	private function sanitize_category_settings( $input ): array {
		if ( ! is_array( $input ) ) {
			return array();
		}

		$sanitized = array();

		foreach ( $input as $category => $settings ) {
			$category = sanitize_text_field( $category );
			$sanitized[ $category ] = array(
				'enable_read'  => isset( $settings['enable_read'] ) && '1' === sanitize_text_field( $settings['enable_read'] ),
				'enable_write' => isset( $settings['enable_write'] ) && '1' === sanitize_text_field( $settings['enable_write'] ),
			);
		}

		return $sanitized;
	}

	/**
	 * Sanitize ability settings.
	 *
	 * @param mixed $input Raw input data.
	 * @return array<string, array<string, bool>> Sanitized settings.
	 */
	private function sanitize_ability_settings( $input ): array {
		if ( ! is_array( $input ) ) {
			return array();
		}

		$sanitized = array();

		foreach ( $input as $ability => $settings ) {
			$ability = sanitize_text_field( $ability );
			$sanitized[ $ability ] = array(
				'enabled' => isset( $settings['enabled'] ) && '1' === sanitize_text_field( $settings['enabled'] ),
			);
		}

		return $sanitized;
	}

	/**
	 * Sanitize ability rate limits.
	 *
	 * @param mixed $input Raw input data.
	 * @return array<string, array<string, int>> Sanitized settings.
	 */
	private function sanitize_ability_rate_limits( $input ): array {
		if ( ! is_array( $input ) ) {
			return array();
		}

		$sanitized = array();

		foreach ( $input as $ability => $limits ) {
			$ability = sanitize_text_field( $ability );
			$sanitized[ $ability ] = array(
				'requests_per_minute' => absint( $limits['requests_per_minute'] ?? 0 ),
				'requests_per_hour'   => absint( $limits['requests_per_hour'] ?? 0 ),
			);
		}

		return $sanitized;
	}

	/**
	 * Sanitize webhook endpoints.
	 *
	 * @param mixed $input Raw input data.
	 * @return array<array<string, mixed>> Sanitized endpoints.
	 */
	private function sanitize_webhook_endpoints( $input ): array {
		if ( ! is_array( $input ) ) {
			return array();
		}

		$sanitized = array();

		foreach ( $input as $endpoint ) {
			$sanitized_endpoint = $this->sanitize_single_endpoint( $endpoint );
			if ( $sanitized_endpoint !== null ) {
				$sanitized[] = $sanitized_endpoint;
			}
		}

		return $sanitized;
	}

	/**
	 * Sanitize a single webhook endpoint.
	 *
	 * @param mixed $endpoint Endpoint data.
	 * @return array<string, mixed>|null Sanitized endpoint or null if invalid.
	 */
	private function sanitize_single_endpoint( $endpoint ): ?array {
		if ( ! is_array( $endpoint ) ) {
			return null;
		}

		$url = isset( $endpoint['url'] ) ? esc_url_raw( $endpoint['url'] ) : '';
		if ( empty( $url ) ) {
			return null;
		}

		return array(
			'url'    => $url,
			'events' => $this->sanitize_webhook_events( $endpoint['events'] ?? null ),
		);
	}

	/**
	 * Sanitize webhook events array.
	 *
	 * @param mixed $events Raw events data.
	 * @return array<string> Sanitized event names.
	 */
	private function sanitize_webhook_events( $events ): array {
		if ( ! is_array( $events ) ) {
			return array();
		}

		$sanitized = array();
		foreach ( $events as $event ) {
			$event = sanitize_text_field( $event );
			if ( in_array( $event, self::WEBHOOK_EVENTS, true ) ) {
				$sanitized[] = $event;
			}
		}

		return $sanitized;
	}
}
