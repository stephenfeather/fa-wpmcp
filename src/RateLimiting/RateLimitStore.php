<?php
/**
 * Rate limit storage interface.
 *
 * @package FAWpmcp\RateLimiting
 */

declare(strict_types=1);

namespace FAWpmcp\RateLimiting;

/**
 * Interface for rate limit counter storage.
 *
 * Side effects are isolated to implementations of this interface.
 * Allows for different storage backends (transients, Redis, etc.).
 *
 * @package FAWpmcp\RateLimiting
 */
interface RateLimitStore {
	/**
	 * Get current count for a key.
	 *
	 * @param string $key Rate limit key.
	 * @return int Current count (0 if not found).
	 */
	public function get( string $key ): int;

	/**
	 * Increment counter for a key.
	 *
	 * @param string $key Key to increment.
	 * @param int    $ttl Time-to-live in seconds.
	 * @return int New count after increment.
	 */
	public function increment( string $key, int $ttl ): int;

	/**
	 * Delete a key.
	 *
	 * @param string $key Key to delete.
	 * @return bool True if deleted, false otherwise.
	 */
	public function delete( string $key ): bool;
}
