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
	 * @param string                 $event             Event name (e.g., 'ability.after_execute').
	 * @param string                 $ability_name      Ability name (e.g., 'fa-wpmcp/create-post').
	 * @param string                 $category          Category (e.g., 'posts-pages').
	 * @param string                 $operation         Operation (e.g., 'read', 'write').
	 * @param int                    $user_id           WordPress user ID.
	 * @param string                 $user_login        WordPress user login.
	 * @param string                 $ip                IP address.
	 * @param array<string, mixed>   $input             Input parameters.
	 * @param array<string, mixed>   $output            Output data.
	 * @param bool                   $success           Whether execution succeeded.
	 * @param int                    $execution_time_ms Execution time in milliseconds.
	 * @param DateTimeImmutable|null $timestamp         Timestamp (null = current time).
	 */
	public static function build(
		string $event,
		string $ability_name,
		string $category,
		string $operation,
		int $user_id,
		string $user_login,
		string $ip,
		array $input,
		array $output,
		bool $success,
		int $execution_time_ms,
		?DateTimeImmutable $timestamp = null,
	): WebhookPayload {
		return new WebhookPayload(
			event: $event,
			timestamp: $timestamp ?? new DateTimeImmutable(),
			ability: [
				'name'      => $ability_name,
				'category'  => $category,
				'operation' => $operation,
			],
			user: [
				'id'    => $user_id,
				'login' => $user_login,
				'ip'    => $ip,
			],
			input: $input,
			output: $output,
			success: $success,
			execution_time_ms: $execution_time_ms,
		);
	}
}
