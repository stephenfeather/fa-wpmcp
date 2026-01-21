<?php
/**
 * Main plugin orchestration class.
 *
 * @package FAWpmcp
 */

declare(strict_types=1);

namespace FAWpmcp;

/**
 * Main plugin orchestration class.
 *
 * This class handles plugin initialization, activation, deactivation,
 * and uninstallation. It follows the singleton pattern for WordPress
 * plugin architecture.
 *
 * @package FAWpmcp
 */
final class Plugin {
	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Service container for dependency injection.
	 *
	 * @var array<string, object>
	 */
	private array $services = array();

	/**
	 * Private constructor to prevent direct instantiation.
	 */
	private function __construct() {
		// Singleton - use get_instance().
	}

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize plugin.
	 *
	 * Registers hooks and initializes components.
	 *
	 * @return void
	 */
	public function init(): void {
		$this->register_hooks();

		// Initialize webhook system.
		$webhook_service = new \FAWpmcp\Webhooks\WebhookService();
		$webhook_service->init();
		$this->register_service( 'webhook', $webhook_service );

		// Initialize Ability Framework.
		// 1. Create AbilityRegistry.
		$ability_registry = new \FAWpmcp\Abilities\AbilityRegistry();
		$this->register_service( 'ability_registry', $ability_registry );

		// 2. Create dependencies for AbilityExecutor.
		// Permission settings from WordPress options.
		$permission_settings = \FAWpmcp\Permissions\OptionsPermissionSettings::load();

		// Rate limiter with transient storage.
		$rate_limit_store  = new \FAWpmcp\RateLimiting\TransientRateLimitStore();
		$rate_limit_config = new \FAWpmcp\RateLimiting\OptionsRateLimitConfig();
		$rate_limiter      = new \FAWpmcp\RateLimiting\RateLimiter( $rate_limit_store, $rate_limit_config );
		$this->register_service( 'rate_limiter', $rate_limiter );

		// Activity logger with database repository.
		global $wpdb;
		$log_repository = new \FAWpmcp\Logging\LogRepository( $wpdb );
		$activity_logger = new \FAWpmcp\Logging\ActivityLogger(
			$log_repository,
			fn() => wp_generate_uuid4()
		);
		$this->register_service( 'activity_logger', $activity_logger );

		// Webhook manager from webhook service.
		$webhook_manager = $webhook_service->get_manager();

		// 3. Create AbilityExecutor with all dependencies.
		$ability_executor = new \FAWpmcp\Abilities\AbilityExecutor(
			$permission_settings,
			$rate_limiter,
			$activity_logger,
			$webhook_manager
		);
		$this->register_service( 'ability_executor', $ability_executor );
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	private function register_hooks(): void {
		add_action( 'admin_init', array( $this, 'check_abilities_api_and_show_notice' ) );
	}

	/**
	 * Register a service in the container.
	 *
	 * @param string $name    Service name.
	 * @param object $service Service instance.
	 * @return void
	 */
	public function register_service( string $name, object $service ): void {
		$this->services[ $name ] = $service;
	}

	/**
	 * Get a service from the container.
	 *
	 * @param string $name Service name.
	 * @return object|null Service instance or null if not found.
	 */
	public function get_service( string $name ): ?object {
		return $this->services[ $name ] ?? null;
	}

	/**
	 * Check if WordPress Abilities API is available.
	 *
	 * @return bool True if Abilities API is available.
	 */
	public function has_abilities_api(): bool {
		return function_exists( 'wp_register_ability' );
	}

	/**
	 * Check Abilities API availability and show admin notice if missing.
	 *
	 * @return void
	 */
	public function check_abilities_api_and_show_notice(): void {
		if ( ! $this->has_abilities_api() ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="error"><p>';
					echo esc_html__(
						'FA WPMCP requires WordPress Abilities API (WordPress 6.9+). Please update WordPress.',
						'fa-wpmcp'
					);
					echo '</p></div>';
				}
			);
		}
	}

	/**
	 * Plugin activation handler.
	 *
	 * @return void
	 */
	public function activate(): void {
		// Activation logic will be added in later phases.
	}

	/**
	 * Plugin deactivation handler.
	 *
	 * @return void
	 */
	public function deactivate(): void {
		// Clean up webhook system.
		$webhook_service = $this->get_service( 'webhook' );
		if ( $webhook_service instanceof \FAWpmcp\Webhooks\WebhookService ) {
			$webhook_service->deactivate();
		}
	}

	/**
	 * Plugin uninstall handler.
	 *
	 * Removes all plugin data if configured to do so.
	 *
	 * @return void
	 */
	public function uninstall(): void {
		// Uninstall logic will be added in Phase 14.
	}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {
		// Singleton - prevent cloning.
	}

	/**
	 * Prevent unserialization.
	 */
	public function __wakeup(): void {
		// Singleton - prevent unserialization.
	}
}
