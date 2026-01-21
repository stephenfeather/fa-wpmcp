<?php
/**
 * Retry timing calculations for webhooks.
 *
 * @package FAWpmcp
 */

declare(strict_types=1);

namespace FAWpmcp\Webhooks;

use DateTimeImmutable;

/**
 * Pure functions for retry timing calculations.
 *
 * Implements exponential backoff: 60s, 120s, 240s (1min, 2min, 4min).
 */
final class RetryCalculator {
	/**
	 * Calculate next retry attempt time.
	 *
	 * Pure function: deterministic output based on attempt count.
	 *
	 * @param int                    $attempt_count Number of previous attempts (0-indexed).
	 * @param DateTimeImmutable|null $now           Current time (null = now).
	 *
	 * @return DateTimeImmutable Next attempt time.
	 */
	public static function calculate_next_attempt(
		int $attempt_count,
		?DateTimeImmutable $now = null
	): DateTimeImmutable {
		$now = $now ?? new DateTimeImmutable();

		// Exponential backoff: 2^attempt * 60 seconds.
		// Attempt 0 (first retry): 60s.
		// Attempt 1 (second retry): 120s.
		// Attempt 2 (third retry): 240s.
		$delay_seconds = ( 2 ** $attempt_count ) * 60;

		return $now->modify( "+{$delay_seconds} seconds" );
	}
}
