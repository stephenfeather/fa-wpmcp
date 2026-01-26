<?php

/**
 * Rate limiter interface.
 *
 * @package FAWpmcp\RateLimiting
 */

declare(strict_types=1);

namespace FAWpmcp\RateLimiting;

use FAWpmcp\ValueObjects\RateLimitResult;

/**
 * Interface for rate limiting orchestration.
 *
 * Enables dependency injection and testing of rate limiting.
 *
 * @package FAWpmcp\RateLimiting
 */
interface RateLimiterInterface
{
    /**
     * Check if a request is within rate limits.
     *
     * @param string $ability Ability name.
     * @param int    $user_id User ID (0 for anonymous).
     * @param string $ip      IP address.
     * @return RateLimitResult Result indicating if request is allowed.
     */
    public function check(string $ability, int $user_id, string $ip): RateLimitResult;

    /**
     * Record a request for rate limiting.
     *
     * Increments counters in both minute and hour windows.
     *
     * @param string $ability Ability name.
     * @param int    $user_id User ID (0 for anonymous).
     * @param string $ip      IP address.
     * @return void
     */
    public function record(string $ability, int $user_id, string $ip): void;
}
