<?php
/**
 * Immutable rate limit configuration value object.
 *
 * @package FAWpmcp\ValueObjects
 */

declare(strict_types=1);

namespace FAWpmcp\ValueObjects;

/**
 * Immutable rate limit configuration.
 *
 * Uses readonly properties (PHP 8.1+) to enforce immutability.
 * No methods - pure data container.
 *
 * @package FAWpmcp\ValueObjects
 */
final readonly class RateLimit {

	/**
	 * Constructor.
	 *
	 * @param int    $requests_per_minute Maximum requests allowed per minute window.
	 * @param int    $requests_per_hour   Maximum requests allowed per hour window.
	 * @param string $ability             Ability name this limit applies to.
	 */
	public function __construct(
		public int $requests_per_minute,
		public int $requests_per_hour,
		public string $ability,
	) {
	}
}
