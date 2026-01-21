<?php
/**
 * Pure functions for rate limit calculations.
 *
 * @package FAWpmcp\RateLimiting
 */

declare(strict_types=1);

namespace FAWpmcp\RateLimiting;

use FAWpmcp\ValueObjects\RateLimit;
use FAWpmcp\ValueObjects\RateLimitResult;

/**
 * Pure functions for rate limit calculations.
 *
 * No side effects, no external dependencies.
 * All methods are static and deterministic.
 *
 * @package FAWpmcp\RateLimiting
 */
final class RateLimitCalculator {
	/**
	 * Check if request is within rate limits.
	 *
	 * Pure function: same inputs always produce same result.
	 *
	 * @param RateLimit $limit               Rate limit configuration.
	 * @param int       $current_minute_count Current count in minute window.
	 * @param int       $current_hour_count   Current count in hour window.
	 * @return RateLimitResult Result indicating if request is allowed.
	 */
	public static function check(
		RateLimit $limit,
		int $current_minute_count,
		int $current_hour_count,
	): RateLimitResult {
		// Zero limit means unlimited - always allow.
		if ( 0 === $limit->requests_per_minute && 0 === $limit->requests_per_hour ) {
			return RateLimitResult::allowed();
		}

		// Check minute limit first (takes precedence over hour).
		$minute_exceeded = $limit->requests_per_minute > 0 && $current_minute_count >= $limit->requests_per_minute;
		$hour_exceeded   = $limit->requests_per_hour > 0 && $current_hour_count >= $limit->requests_per_hour;

		if ( $minute_exceeded || $hour_exceeded ) {
			$limit_type  = $minute_exceeded ? 'minute' : 'hour';
			$retry_after = self::calculate_retry_after( $limit_type, time() );
			return RateLimitResult::denied( $retry_after, $limit_type );
		}

		return RateLimitResult::allowed();
	}

	/**
	 * Calculate seconds until limit window resets.
	 *
	 * Pure function: deterministic based on current time.
	 *
	 * @param string $limit_type   Type of limit ('minute' or 'hour').
	 * @param int    $current_time Current Unix timestamp.
	 * @return int Seconds until window resets.
	 */
	public static function calculate_retry_after( string $limit_type, int $current_time ): int {
		$remainder = match ( $limit_type ) {
			'minute' => $current_time % 60,
			'hour' => $current_time % 3600,
			default => 0,
		};

		$window_size = match ( $limit_type ) {
			'minute' => 60,
			'hour' => 3600,
			default => 60,
		};

		// If at exact boundary (remainder is 0), return full window.
		if ( 0 === $remainder ) {
			return $window_size;
		}

		return $window_size - $remainder;
	}

	/**
	 * Build transient key for rate limit tracking.
	 *
	 * Pure function: same inputs produce same key.
	 * IP is hashed for privacy.
	 *
	 * @param int    $user_id User ID (0 for anonymous).
	 * @param string $ip      IP address.
	 * @param string $ability Ability name.
	 * @param string $window  Time window ('minute' or 'hour').
	 * @return string Transient key.
	 */
	public static function build_key( int $user_id, string $ip, string $ability, string $window ): string {
		$ip_hash      = substr( md5( $ip ), 0, 8 );
		$ability_slug = str_replace( '/', '-', $ability );
		return "fa_wpmcp_ratelimit_{$user_id}_{$ip_hash}_{$ability_slug}_{$window}";
	}

	/**
	 * Determine which limits apply to an ability.
	 *
	 * Pure function: returns limit configuration.
	 *
	 * @param string               $ability Ability name.
	 * @param array<string, array> $config  Configuration array keyed by ability name.
	 * @return RateLimit Rate limit configuration for the ability.
	 */
	public static function get_limits_for_ability( string $ability, array $config ): RateLimit {
		$defaults       = array(
			'requests_per_minute' => 60,
			'requests_per_hour'   => 500,
		);
		$ability_config = $config[ $ability ] ?? $defaults;

		return new RateLimit(
			requests_per_minute: $ability_config['requests_per_minute'] ?? 60,
			requests_per_hour: $ability_config['requests_per_hour'] ?? 500,
			ability: $ability,
		);
	}
}
