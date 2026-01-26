<?php
/**
 * Pure functions for building webhook payloads.
 *
 * @package FAWpmcp
 */

declare(strict_types=1);

namespace FAWpmcp\Webhooks;

use FAWpmcp\ValueObjects\WebhookPayload;
use DateTimeImmutable;

/**
 * Pure functions for building webhook payloads.
 *
 * All methods are static and side-effect free.
 */
final class PayloadBuilder {

	/**
	 * Build complete webhook payload.
	 *
	 * Pure function: same inputs produce same payload.
	 *
	 * @param string                 $event     Event name (e.g., 'ability.after_execute').
	 * @param array<string, mixed>   $ability   Ability context (name, category, operation).
	 * @param array<string, mixed>   $user      User context (user_id, user_login, ip).
	 * @param array<string, mixed>   $execution Execution data (input, output, success, execution_time_ms).
	 * @param DateTimeImmutable|null $timestamp Timestamp (null = current time).
	 */
	public static function build(
		string $event,
		array $ability,
		array $user,
		array $execution,
		?DateTimeImmutable $timestamp = null,
	): WebhookPayload {
		return new WebhookPayload(
			event: $event,
			timestamp: $timestamp ?? new DateTimeImmutable(),
			ability: array(
				'name'      => $ability['name'],
				'category'  => $ability['category'],
				'operation' => $ability['operation'],
			),
			user: array(
				'id'    => $user['user_id'],
				'login' => $user['user_login'],
				'ip'    => $user['ip'],
			),
			input: $execution['input'],
			output: $execution['output'],
			success: $execution['success'],
			execution_time_ms: $execution['execution_time_ms'],
		);
	}
}
