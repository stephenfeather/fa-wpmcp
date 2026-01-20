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
		// Hook registration will be added in Phase 1.2.
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
