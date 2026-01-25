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
     * Checks BOTH IP-based and user-based limits. If EITHER is exceeded, deny the request.
     * This prevents distributed attacks that bypass per-IP limits.
     *
     * @param string $ability Ability name.
     * @param int    $user_id User ID (0 for anonymous).
     * @param string $ip      IP address.
     * @return RateLimitResult Result indicating if request is allowed.
     */
    public function check( string $ability, int $user_id, string $ip ): RateLimitResult {
        // Get configuration (pure).
        $limit = RateLimitCalculator::getLimitsForAbility( $ability, $this->config->getAll() );

        // Check IP-based limits.
        $ip_result = $this->checkIpLimit( $ability, $ip, $limit );
        if ( ! $ip_result->allowed ) {
            return $ip_result;
        }

        // Check user-based limits (skip for anonymous users).
        if ( $user_id > 0 ) {
            $user_result = $this->checkUserLimit( $ability, $user_id, $limit );
            if ( ! $user_result->allowed ) {
                return $user_result;
            }
        }

        return RateLimitResult::allowed();
    }

    /**
     * Check IP-based rate limit.
     *
     * @param string    $ability Ability name.
     * @param string    $ip      IP address.
     * @param RateLimit $limit   Rate limit configuration.
     * @return RateLimitResult Result indicating if request is allowed.
     */
    private function checkIpLimit( string $ability, string $ip, $limit ): RateLimitResult {
        $minute_key = RateLimitCalculator::buildIpKey( $ip, $ability, 'minute' );
        $hour_key   = RateLimitCalculator::buildIpKey( $ip, $ability, 'hour' );

        $minute_count = $this->store->get( $minute_key );
        $hour_count   = $this->store->get( $hour_key );

        return RateLimitCalculator::check( $limit, $minute_count, $hour_count );
    }

    /**
     * Check user-based rate limit.
     *
     * @param string    $ability Ability name.
     * @param int       $user_id User ID.
     * @param RateLimit $limit   Rate limit configuration.
     * @return RateLimitResult Result indicating if request is allowed.
     */
    private function checkUserLimit( string $ability, int $user_id, $limit ): RateLimitResult {
        $minute_key = RateLimitCalculator::buildUserKey( $user_id, $ability, 'minute' );
        $hour_key   = RateLimitCalculator::buildUserKey( $user_id, $ability, 'hour' );

        $minute_count = $this->store->get( $minute_key );
        $hour_count   = $this->store->get( $hour_key );

        return RateLimitCalculator::check( $limit, $minute_count, $hour_count );
    }

    /**
     * Record a request for rate limiting.
     *
     * Increments BOTH IP-based and user-based counters in minute and hour windows.
     *
     * @param string $ability Ability name.
     * @param int    $user_id User ID (0 for anonymous).
     * @param string $ip      IP address.
     * @return void
     */
    public function record( string $ability, int $user_id, string $ip ): void {
        // Record IP-based counters.
        $ip_minute_key = RateLimitCalculator::buildIpKey( $ip, $ability, 'minute' );
        $ip_hour_key   = RateLimitCalculator::buildIpKey( $ip, $ability, 'hour' );
        $this->store->increment( $ip_minute_key, 60 );
        $this->store->increment( $ip_hour_key, 3600 );

        // Record user-based counters (skip for anonymous users).
        if ( $user_id > 0 ) {
            $user_minute_key = RateLimitCalculator::buildUserKey( $user_id, $ability, 'minute' );
            $user_hour_key   = RateLimitCalculator::buildUserKey( $user_id, $ability, 'hour' );
            $this->store->increment( $user_minute_key, 60 );
            $this->store->increment( $user_hour_key, 3600 );
        }
    }
}
