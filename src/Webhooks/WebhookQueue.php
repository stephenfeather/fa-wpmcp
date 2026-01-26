<?php

/**
 * Webhook queue management interface.
 *
 * @package FAWpmcp
 */

declare(strict_types=1);

namespace FAWpmcp\Webhooks;

use FAWpmcp\ValueObjects\WebhookPayload;
use DateTimeImmutable;

/**
 * Interface for webhook queue management.
 *
 * Implementations handle persistence (database, transients, etc.)
 */
interface WebhookQueue {

	/**
	 * Enqueue a webhook for delivery.
	 *
	 * @param string         $url     Webhook URL.
	 * @param WebhookPayload $payload Payload to send.
	 */
	public function enqueue( string $url, WebhookPayload $payload ): void;

	/**
	 * Get pending webhooks.
	 *
	 * @param int $limit Maximum number to retrieve.
	 *
	 * @return array<int, array{id: int, url: string, payload: string, attempt_count: int}>
	 */
	public function getPending( int $limit ): array;

	/**
	 * Mark webhook as successfully delivered.
	 *
	 * @param int $id Webhook ID.
	 */
	public function markComplete( int $id ): void;

	/**
	 * Mark webhook as failed after max retries.
	 *
	 * @param int    $id            Webhook ID.
	 * @param string $error_message Error message.
	 */
	public function markFailed( int $id, string $error_message ): void;

	/**
	 * Schedule webhook for retry.
	 *
	 * @param int               $id           Webhook ID.
	 * @param DateTimeImmutable $next_attempt Next attempt time.
	 */
	public function scheduleRetry( int $id, DateTimeImmutable $next_attempt ): void;
}
