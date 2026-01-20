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
		// Deactivation logic will be added in later phases.
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
