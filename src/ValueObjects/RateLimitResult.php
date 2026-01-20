<?php
/**
 * Immutable rate limit result value object.
 *
 * @package FAWpmcp\ValueObjects
 */

declare(strict_types=1);

namespace FAWpmcp\ValueObjects;

/**
 * Immutable rate limit check result.
 *
 * Uses readonly properties (PHP 8.1+) to enforce immutability.
 * Factory methods for common result types.
 *
 * @package FAWpmcp\ValueObjects
 */
final readonly class RateLimitResult {
	/**
	 * Constructor.
	 *
	 * @param bool   $allowed     Whether the request is allowed.
	 * @param string $limit_type  Type of limit hit ('none', 'minute', or 'hour').
	 * @param int    $retry_after Seconds until request may be retried (0 if allowed).
	 */
	public function __construct(
		public bool $allowed,
		public string $limit_type,
		public int $retry_after,
	) {
	}

	/**
	 * Create an allowed result.
	 *
	 * Factory method for requests under rate limits.
	 *
	 * @return self Allowed result with no retry_after.
	 */
	public static function allowed(): self {
		return new self(
			allowed: true,
			limit_type: 'none',
			retry_after: 0,
		);
	}

	/**
	 * Create a denied result.
	 *
	 * Factory method for requests over rate limits.
	 *
	 * @param int    $retry_after Seconds until request may be retried.
	 * @param string $limit_type  Type of limit hit ('minute' or 'hour').
	 * @return self Denied result with retry_after.
	 */
	public static function denied( int $retry_after, string $limit_type ): self {
		return new self(
			allowed: false,
			limit_type: $limit_type,
			retry_after: $retry_after,
		);
	}
}
