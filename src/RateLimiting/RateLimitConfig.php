<?php

/**
 * Rate limit configuration interface.
 *
 * @package FAWpmcp\RateLimiting
 */

declare(strict_types=1);

namespace FAWpmcp\RateLimiting;

/**
 * Interface for rate limit configuration.
 *
 * Provides access to rate limit settings per ability.
 *
 * @package FAWpmcp\RateLimiting
 */
interface RateLimitConfig {

	/**
	 * Get all rate limit configuration.
	 *
	 * Returns array keyed by ability name with rate limit settings.
	 * Example:
	 * [
	 *     'fa-wpmcp/create-post' => [
	 *         'requests_per_minute' => 10,
	 *         'requests_per_hour' => 100,
	 *     ],
	 * ]
	 *
	 * @return array<string, array{requests_per_minute?: int, requests_per_hour?: int}>
	 */
	public function getAll(): array;

	/**
	 * Get rate limit configuration for a specific ability.
	 *
	 * @param string $ability Ability name.
	 * @return array{requests_per_minute?: int, requests_per_hour?: int}|null Configuration or null if not found.
	 */
	public function get( string $ability ): ?array;
}
