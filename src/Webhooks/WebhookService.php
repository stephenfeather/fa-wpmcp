<?php
/**
 * Webhook system service.
 *
 * @package FAWpmcp
 */

declare(strict_types=1);

namespace FAWpmcp\Webhooks;

/**
 * Webhook system service.
 *
 * Main entry point for webhook system.
 * Wires together all webhook components and registers hooks.
 */
final class WebhookService {

	/**
	 * Webhook manager.
	 *
	 * @var WebhookManager
	 */
	private WebhookManager $manager;

	/**
	 * Webhook scheduler.
	 *
	 * @var WebhookScheduler
	 */
	private WebhookScheduler $scheduler;

	/**
	 * Constructor.
	 *
	 * Initializes webhook components with concrete implementations.
	 */
	public function __construct() {
		// Instantiate concrete implementations.
		$queue      = new DatabaseWebhookQueue();
		$sender     = new WpHttpWebhookSender();
		$encryption = SecretEncryptionFactory::create();
		$config     = new OptionsWebhookConfig( $encryption );

		// Create manager.
		$this->manager = new WebhookManager( $queue, $sender, $config );

		// Create scheduler.
		$this->scheduler = new WebhookScheduler( $this->manager );
	}

	/**
	 * Initialize webhook system.
	 *
	 * Sets up scheduler and registers WordPress hooks.
	 */
	public function init(): void {
		// Initialize scheduler.
		$this->scheduler->init();

		// Register ability lifecycle hooks.
		$this->registerAbilityHooks();
	}

	/**
	 * Register ability lifecycle hooks.
	 *
	 * Listens for ability events and triggers webhooks.
	 */
	private function registerAbilityHooks(): void {
		// Trigger webhooks before ability execution.
		add_action(
			'fa_wpmcp_ability_before_execute',
			array( $this, 'onBeforeExecute' ),
			10,
			1
		);

		// Trigger webhooks after successful ability execution.
		add_action(
			'fa_wpmcp_ability_after_execute',
			array( $this, 'onAfterExecute' ),
			10,
			1
		);

		// Trigger webhooks after ability execution failure.
		add_action(
			'fa_wpmcp_ability_failed',
			array( $this, 'onFailed' ),
			10,
			1
		);
	}

	/**
	 * Handle ability.before_execute event.
	 *
	 * @param array<string, mixed> $context Event context.
	 */
	public function onBeforeExecute( array $context ): void {
		$this->manager->trigger( 'ability.before_execute', $context );
	}

	/**
	 * Handle ability.after_execute event.
	 *
	 * @param array<string, mixed> $context Event context.
	 */
	public function onAfterExecute( array $context ): void {
		$this->manager->trigger( 'ability.after_execute', $context );
	}

	/**
	 * Handle ability.failed event.
	 *
	 * @param array<string, mixed> $context Event context.
	 */
	public function onFailed( array $context ): void {
		$this->manager->trigger( 'ability.failed', $context );
	}

	/**
	 * Unschedule webhook processing.
	 *
	 * Called on plugin deactivation.
	 */
	public function deactivate(): void {
		$this->scheduler->unschedule();
	}

	/**
	 * Get webhook manager.
	 *
	 * For testing and advanced usage.
	 *
	 * @return WebhookManager Webhook manager instance.
	 */
	public function getManager(): WebhookManager {
		return $this->manager;
	}
}
