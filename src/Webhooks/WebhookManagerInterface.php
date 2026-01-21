<?php
/**
 * Webhook manager interface.
 *
 * @package FAWpmcp\Webhooks
 */

declare(strict_types=1);

namespace FAWpmcp\Webhooks;

/**
 * Interface for webhook system orchestration.
 *
 * Enables dependency injection and testing of webhook triggering.
 *
 * @package FAWpmcp\Webhooks
 */
interface WebhookManagerInterface {
	/**
	 * Trigger webhooks for an event.
	 *
	 * Builds payload and enqueues webhooks for subscribed URLs.
	 *
	 * @param string               $event   Event name.
	 * @param array<string, mixed> $context Event context data.
	 * @return void
	 */
	public function trigger( string $event, array $context ): void;

	/**
	 * Process pending webhooks from queue.
	 *
	 * Retrieves pending webhooks and attempts delivery.
	 *
	 * @return void
	 */
	public function process_queue(): void;
}
