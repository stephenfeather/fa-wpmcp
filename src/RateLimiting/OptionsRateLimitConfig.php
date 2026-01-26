<?php

/**
 * Options-based rate limit configuration.
 *
 * @package FAWpmcp\RateLimiting
 */

declare(strict_types=1);

namespace FAWpmcp\RateLimiting;

/**
 * WordPress options-based implementation of rate limit configuration.
 *
 * Stores rate limit settings in WordPress options table.
 * Falls back to default limits if not configured.
 *
 * @package FAWpmcp\RateLimiting
 */
final class OptionsRateLimitConfig implements RateLimitConfig {

	/**
	 * Option name for rate limit configuration.
	 *
	 * @var string
	 */
	private const OPTION_NAME = 'fa_wpmcp_rate_limits';

	/**
	 * Default rate limits.
	 *
	 * @var array<string, array<string, int>>
	 */
	private const DEFAULTS = array(
		'default' => array(
			'requests_per_minute' => 60,
			'requests_per_hour'   => 1000,
		),
	);

	/**
	 * Get all rate limit configuration.
	 *
	 * Returns array keyed by ability name with rate limit settings.
	 *
	 * @return array<string, array<string, int>> Rate limit configuration.
	 */
	public function getAll(): array {
		$config = get_option( self::OPTION_NAME, array() );

		// Merge with defaults.
		if ( ! is_array( $config ) ) {
			$config = array();
		}

		return array_merge( self::DEFAULTS, $config );
	}

	/**
	 * Get rate limits for a specific ability.
	 *
	 * Falls back to default limits if ability not configured.
	 *
	 * @param string $ability Ability name.
	 * @return array{requests_per_minute: int, requests_per_hour: int} Rate limit settings.
	 */
	public function get( string $ability ): array {
		$all = $this->getAll();
		return $all[ $ability ] ?? $all['default'];
	}

	/**
	 * Set rate limits for a specific ability.
	 *
	 * @param string $ability            Ability name.
	 * @param int    $requests_per_minute Requests per minute limit.
	 * @param int    $requests_per_hour   Requests per hour limit.
	 * @return void
	 */
	public function set( string $ability, int $requests_per_minute, int $requests_per_hour ): void {
		$config             = $this->getAll();
		$config[ $ability ] = array(
			'requests_per_minute' => $requests_per_minute,
			'requests_per_hour'   => $requests_per_hour,
		);
		update_option( self::OPTION_NAME, $config );
	}
}
