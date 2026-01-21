<?php
/**
 * Rate limiter orchestration.
 *
 * @package FAWpmcp\RateLimiting
 */

declare(strict_types=1);

namespace FAWpmcp\RateLimiting;

use FAWpmcp\ValueObjects\RateLimitResult;

/**
 * Orchestrates rate limiting by coordinating Calculator and Store.
 *
 * This class contains side effects (reads/writes to store).
 * Pure calculations are delegated to RateLimitCalculator.
 *
 * @package FAWpmcp\RateLimiting
 */
class RateLimiter implements RateLimiterInterface {
    /**
     * Constructor.
     *
     * @param RateLimitStore  $store  Rate limit counter storage.
     * @param RateLimitConfig $config Rate limit configuration.
     */
    public function __construct(
        private readonly RateLimitStore $store,
        private readonly RateLimitConfig $config,
    ) {
    }

    /**
     * Check if a request is within rate limits.
     *
     * Reads current counts from store and delegates to pure Calculator.
     *
     * @param string $ability Ability name.
     * @param int    $user_id User ID (0 for anonymous).
     * @param string $ip      IP address.
     * @return RateLimitResult Result indicating if request is allowed.
     */
    public function check( string $ability, int $user_id, string $ip ): RateLimitResult {
        // Get configuration (pure).
        $limit = RateLimitCalculator::getLimitsForAbility( $ability, $this->config->getAll() );

        // Build keys (pure).
        $minute_key = RateLimitCalculator::buildKey( $user_id, $ip, $ability, 'minute' );
        $hour_key   = RateLimitCalculator::buildKey( $user_id, $ip, $ability, 'hour' );

        // Get current counts (side effect: storage reads).
        $minute_count = $this->store->get( $minute_key );
        $hour_count   = $this->store->get( $hour_key );

        // Pure calculation.
        return RateLimitCalculator::check( $limit, $minute_count, $hour_count );
    }

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
    public function record( string $ability, int $user_id, string $ip ): void {
        // Build keys (pure).
        $minute_key = RateLimitCalculator::buildKey( $user_id, $ip, $ability, 'minute' );
        $hour_key   = RateLimitCalculator::buildKey( $user_id, $ip, $ability, 'hour' );

        // Increment counters (side effect: storage writes).
        $this->store->increment( $minute_key, 60 );
        $this->store->increment( $hour_key, 3600 );
    }
}
