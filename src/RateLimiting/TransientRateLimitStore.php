<?php
/**
 * Transient-based rate limit storage.
 *
 * @package FAWpmcp\RateLimiting
 */

declare(strict_types=1);

namespace FAWpmcp\RateLimiting;

/**
 * WordPress transient-based implementation of rate limit storage.
 *
 * Uses WordPress transients for storing rate limit counters.
 * Transients automatically expire, eliminating need for manual cleanup.
 *
 * @package FAWpmcp\RateLimiting
 */
final class TransientRateLimitStore implements RateLimitStore {
	/**
	 * Get current count for a key.
	 *
	 * @param string $key Rate limit key.
	 * @return int Current count (0 if not found).
	 */
	public function get( string $key ): int {
		$value = get_transient( $key );
		return is_numeric( $value ) ? (int) $value : 0;
	}

	/**
	 * Increment counter for a key.
	 *
	 * Creates counter with TTL if it doesn't exist.
	 * Increments existing counter otherwise.
	 *
	 * @param string $key Rate limit key.
	 * @param int    $ttl Time to live in seconds.
	 * @return int New count after increment.
	 */
	public function increment( string $key, int $ttl ): int {
		$current   = $this->get( $key );
		$new_value = $current + 1;
		set_transient( $key, $new_value, $ttl );
		return $new_value;
	}

	/**
	 * Delete a key.
	 *
	 * @param string $key Key to delete.
	 * @return bool True if deleted, false otherwise.
	 */
	public function delete( string $key ): bool {
		return delete_transient( $key );
	}
}
