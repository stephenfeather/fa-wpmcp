<?php
/**
 * Tests for RateLimitCalculator pure functions.
 *
 * @package FAWpmcp\Tests\RateLimiting
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\RateLimiting;

use FAWpmcp\RateLimiting\RateLimitCalculator;
use FAWpmcp\ValueObjects\RateLimit;
use FAWpmcp\ValueObjects\RateLimitResult;
use PHPUnit\Framework\TestCase;

/**
 * Test RateLimitCalculator pure functions.
 *
 * @package FAWpmcp\Tests\RateLimiting
 */
class RateLimitCalculatorTest extends TestCase {
	/**
	 * Test under limit returns allowed.
	 *
	 * @return void
	 */
	public function test_under_limit_returns_allowed(): void {
		$limit = new RateLimit(
			requests_per_minute: 60,
			requests_per_hour: 500,
			ability: 'fa-wpmcp/list-posts',
		);

		$result = RateLimitCalculator::check(
			limit: $limit,
			current_minute_count: 30,
			current_hour_count: 100,
		);

		$this->assertTrue( $result->allowed );
		$this->assertEquals( 'none', $result->limit_type );
	}

	/**
	 * Test at minute limit returns denied.
	 *
	 * @return void
	 */
	public function test_at_minute_limit_returns_denied(): void {
		$limit = new RateLimit(
			requests_per_minute: 60,
			requests_per_hour: 500,
			ability: 'fa-wpmcp/list-posts',
		);

		$result = RateLimitCalculator::check(
			limit: $limit,
			current_minute_count: 60,
			current_hour_count: 100,
		);

		$this->assertFalse( $result->allowed );
		$this->assertEquals( 'minute', $result->limit_type );
	}

	/**
	 * Test over minute limit returns denied.
	 *
	 * @return void
	 */
	public function test_over_minute_limit_returns_denied(): void {
		$limit = new RateLimit(
			requests_per_minute: 60,
			requests_per_hour: 500,
			ability: 'fa-wpmcp/list-posts',
		);

		$result = RateLimitCalculator::check(
			limit: $limit,
			current_minute_count: 100,
			current_hour_count: 100,
		);

		$this->assertFalse( $result->allowed );
		$this->assertEquals( 'minute', $result->limit_type );
	}

	/**
	 * Test at hour limit returns denied.
	 *
	 * @return void
	 */
	public function test_at_hour_limit_returns_denied(): void {
		$limit = new RateLimit(
			requests_per_minute: 60,
			requests_per_hour: 500,
			ability: 'fa-wpmcp/list-posts',
		);

		$result = RateLimitCalculator::check(
			limit: $limit,
			current_minute_count: 30,
			current_hour_count: 500,
		);

		$this->assertFalse( $result->allowed );
		$this->assertEquals( 'hour', $result->limit_type );
	}

	/**
	 * Test over hour limit returns denied.
	 *
	 * @return void
	 */
	public function test_over_hour_limit_returns_denied(): void {
		$limit = new RateLimit(
			requests_per_minute: 60,
			requests_per_hour: 500,
			ability: 'fa-wpmcp/list-posts',
		);

		$result = RateLimitCalculator::check(
			limit: $limit,
			current_minute_count: 30,
			current_hour_count: 600,
		);

		$this->assertFalse( $result->allowed );
		$this->assertEquals( 'hour', $result->limit_type );
	}

	/**
	 * Test minute limit takes precedence over hour limit.
	 *
	 * @return void
	 */
	public function test_minute_limit_takes_precedence(): void {
		$limit = new RateLimit(
			requests_per_minute: 60,
			requests_per_hour: 500,
			ability: 'fa-wpmcp/list-posts',
		);

		$result = RateLimitCalculator::check(
			limit: $limit,
			current_minute_count: 60,
			current_hour_count: 500,
		);

		// Minute limit should be checked first.
		$this->assertFalse( $result->allowed );
		$this->assertEquals( 'minute', $result->limit_type );
	}

	/**
	 * Test retry after calculation for minute.
	 *
	 * @return void
	 */
	public function test_retry_after_calculation_for_minute(): void {
		$retry_after = RateLimitCalculator::calculateRetryAfter( 'minute', 45 );

		// Should be seconds remaining in current minute window.
		$this->assertGreaterThan( 0, $retry_after );
		$this->assertLessThanOrEqual( 60, $retry_after );
	}

	/**
	 * Test retry after calculation for hour.
	 *
	 * @return void
	 */
	public function test_retry_after_calculation_for_hour(): void {
		$retry_after = RateLimitCalculator::calculateRetryAfter( 'hour', 1800 );

		// Should be seconds remaining in current hour window.
		$this->assertGreaterThan( 0, $retry_after );
		$this->assertLessThanOrEqual( 3600, $retry_after );
	}

	/**
	 * Test retry after at start of minute window.
	 *
	 * @return void
	 */
	public function test_retry_after_at_start_of_minute(): void {
		// At exactly a minute boundary (e.g., 0 seconds).
		$current_time = 1704067200; // A time with 0 seconds.
		$retry_after  = RateLimitCalculator::calculateRetryAfter( 'minute', $current_time );

		$this->assertEquals( 60, $retry_after );
	}

	/**
	 * Test retry after at start of hour window.
	 *
	 * @return void
	 */
	public function test_retry_after_at_start_of_hour(): void {
		// At exactly an hour boundary.
		$current_time = 1704067200; // A time at hour boundary.
		$retry_after  = RateLimitCalculator::calculateRetryAfter( 'hour', $current_time );

		$this->assertEquals( 3600, $retry_after );
	}

	/**
	 * Test retry after defaults for unknown type.
	 *
	 * @return void
	 */
	public function test_retry_after_unknown_type_defaults(): void {
		$retry_after = RateLimitCalculator::calculateRetryAfter( 'unknown', 100 );

		$this->assertEquals( 60, $retry_after );
	}

	/**
	 * Test build key produces consistent output.
	 *
	 * @return void
	 */
	public function test_buildKey_produces_consistent_output(): void {
		$key1 = RateLimitCalculator::buildKey( 1, '192.168.1.1', 'fa-wpmcp/list-posts', 'minute' );
		$key2 = RateLimitCalculator::buildKey( 1, '192.168.1.1', 'fa-wpmcp/list-posts', 'minute' );

		$this->assertEquals( $key1, $key2 );
	}

	/**
	 * Test IP is hashed for privacy.
	 *
	 * @return void
	 */
	public function test_ip_is_hashed_for_privacy(): void {
		$key = RateLimitCalculator::buildKey( 1, '192.168.1.1', 'fa-wpmcp/list-posts', 'minute' );

		$this->assertStringNotContainsString( '192.168.1.1', $key );
	}

	/**
	 * Test different users produce different keys.
	 *
	 * @return void
	 */
	public function test_different_users_produce_different_keys(): void {
		$key1 = RateLimitCalculator::buildKey( 1, '192.168.1.1', 'fa-wpmcp/list-posts', 'minute' );
		$key2 = RateLimitCalculator::buildKey( 2, '192.168.1.1', 'fa-wpmcp/list-posts', 'minute' );

		$this->assertNotEquals( $key1, $key2 );
	}

	/**
	 * Test different IPs produce different keys.
	 *
	 * @return void
	 */
	public function test_different_ips_produce_different_keys(): void {
		$key1 = RateLimitCalculator::buildKey( 1, '192.168.1.1', 'fa-wpmcp/list-posts', 'minute' );
		$key2 = RateLimitCalculator::buildKey( 1, '192.168.1.2', 'fa-wpmcp/list-posts', 'minute' );

		$this->assertNotEquals( $key1, $key2 );
	}

	/**
	 * Test different abilities produce different keys.
	 *
	 * @return void
	 */
	public function test_different_abilities_produce_different_keys(): void {
		$key1 = RateLimitCalculator::buildKey( 1, '192.168.1.1', 'fa-wpmcp/list-posts', 'minute' );
		$key2 = RateLimitCalculator::buildKey( 1, '192.168.1.1', 'fa-wpmcp/create-post', 'minute' );

		$this->assertNotEquals( $key1, $key2 );
	}

	/**
	 * Test different windows produce different keys.
	 *
	 * @return void
	 */
	public function test_different_windows_produce_different_keys(): void {
		$key1 = RateLimitCalculator::buildKey( 1, '192.168.1.1', 'fa-wpmcp/list-posts', 'minute' );
		$key2 = RateLimitCalculator::buildKey( 1, '192.168.1.1', 'fa-wpmcp/list-posts', 'hour' );

		$this->assertNotEquals( $key1, $key2 );
	}

	/**
	 * Test key contains expected prefix.
	 *
	 * @return void
	 */
	public function test_key_contains_expected_prefix(): void {
		$key = RateLimitCalculator::buildKey( 1, '192.168.1.1', 'fa-wpmcp/list-posts', 'minute' );

		$this->assertStringStartsWith( 'fa_wpmcp_ratelimit_', $key );
	}

	/**
	 * Test ability slug sanitization in key.
	 *
	 * @return void
	 */
	public function test_ability_slug_sanitization(): void {
		$key = RateLimitCalculator::buildKey( 1, '192.168.1.1', 'fa-wpmcp/list-posts', 'minute' );

		// Should replace / with - in ability name.
		$this->assertStringContainsString( 'fa-wpmcp-list-posts', $key );
	}

	/**
	 * Test get limits for ability returns default config.
	 *
	 * @return void
	 */
	public function test_getLimitsForAbility_returns_defaults(): void {
		$config = array();

		$limit = RateLimitCalculator::getLimitsForAbility( 'fa-wpmcp/list-posts', $config );

		$this->assertEquals( 60, $limit->requests_per_minute );
		$this->assertEquals( 500, $limit->requests_per_hour );
		$this->assertEquals( 'fa-wpmcp/list-posts', $limit->ability );
	}

	/**
	 * Test get limits for ability uses custom config.
	 *
	 * @return void
	 */
	public function test_getLimitsForAbility_uses_custom_config(): void {
		$config = array(
			'fa-wpmcp/create-post' => array(
				'requests_per_minute' => 10,
				'requests_per_hour'   => 100,
			),
		);

		$limit = RateLimitCalculator::getLimitsForAbility( 'fa-wpmcp/create-post', $config );

		$this->assertEquals( 10, $limit->requests_per_minute );
		$this->assertEquals( 100, $limit->requests_per_hour );
		$this->assertEquals( 'fa-wpmcp/create-post', $limit->ability );
	}

	/**
	 * Test get limits for ability falls back for unknown ability.
	 *
	 * @return void
	 */
	public function test_getLimitsForAbility_falls_back_for_unknown(): void {
		$config = array(
			'fa-wpmcp/create-post' => array(
				'requests_per_minute' => 10,
				'requests_per_hour'   => 100,
			),
		);

		$limit = RateLimitCalculator::getLimitsForAbility( 'fa-wpmcp/unknown-ability', $config );

		$this->assertEquals( 60, $limit->requests_per_minute );
		$this->assertEquals( 500, $limit->requests_per_hour );
	}

	/**
	 * Test zero limit allows unlimited requests.
	 *
	 * @return void
	 */
	public function test_zero_limit_allows_unlimited(): void {
		$limit = new RateLimit(
			requests_per_minute: 0,
			requests_per_hour: 0,
			ability: 'fa-wpmcp/unlimited',
		);

		$result = RateLimitCalculator::check(
			limit: $limit,
			current_minute_count: 1000,
			current_hour_count: 10000,
		);

		// Zero means no limit - should always be allowed.
		$this->assertTrue( $result->allowed );
	}

	/**
	 * Test check is pure function (same inputs produce same output).
	 *
	 * @return void
	 */
	public function test_check_is_pure_function(): void {
		$limit = new RateLimit(
			requests_per_minute: 60,
			requests_per_hour: 500,
			ability: 'fa-wpmcp/list-posts',
		);

		$result1 = RateLimitCalculator::check(
			limit: $limit,
			current_minute_count: 30,
			current_hour_count: 100,
		);

		$result2 = RateLimitCalculator::check(
			limit: $limit,
			current_minute_count: 30,
			current_hour_count: 100,
		);

		$this->assertEquals( $result1->allowed, $result2->allowed );
		$this->assertEquals( $result1->limit_type, $result2->limit_type );
	}

	/**
	 * Test buildIpKey produces consistent output.
	 *
	 * @return void
	 */
	public function test_buildIpKey_produces_consistent_output(): void {
		$key1 = RateLimitCalculator::buildIpKey( '192.168.1.1', 'fa-wpmcp/list-posts', 'minute' );
		$key2 = RateLimitCalculator::buildIpKey( '192.168.1.1', 'fa-wpmcp/list-posts', 'minute' );

		$this->assertEquals( $key1, $key2 );
	}

	/**
	 * Test buildIpKey hashes IP for privacy.
	 *
	 * @return void
	 */
	public function test_buildIpKey_hashes_ip(): void {
		$key = RateLimitCalculator::buildIpKey( '192.168.1.1', 'fa-wpmcp/list-posts', 'minute' );

		$this->assertStringNotContainsString( '192.168.1.1', $key );
		$this->assertStringContainsString( '_ip_', $key );
	}

	/**
	 * Test buildIpKey different IPs produce different keys.
	 *
	 * @return void
	 */
	public function test_buildIpKey_different_ips_different_keys(): void {
		$key1 = RateLimitCalculator::buildIpKey( '192.168.1.1', 'fa-wpmcp/list-posts', 'minute' );
		$key2 = RateLimitCalculator::buildIpKey( '192.168.1.2', 'fa-wpmcp/list-posts', 'minute' );

		$this->assertNotEquals( $key1, $key2 );
	}

	/**
	 * Test buildUserKey produces consistent output.
	 *
	 * @return void
	 */
	public function test_buildUserKey_produces_consistent_output(): void {
		$key1 = RateLimitCalculator::buildUserKey( 1, 'fa-wpmcp/list-posts', 'minute' );
		$key2 = RateLimitCalculator::buildUserKey( 1, 'fa-wpmcp/list-posts', 'minute' );

		$this->assertEquals( $key1, $key2 );
	}

	/**
	 * Test buildUserKey includes user ID.
	 *
	 * @return void
	 */
	public function test_buildUserKey_includes_user_id(): void {
		$key = RateLimitCalculator::buildUserKey( 123, 'fa-wpmcp/list-posts', 'minute' );

		$this->assertStringContainsString( '_user_123_', $key );
	}

	/**
	 * Test buildUserKey different users produce different keys.
	 *
	 * @return void
	 */
	public function test_buildUserKey_different_users_different_keys(): void {
		$key1 = RateLimitCalculator::buildUserKey( 1, 'fa-wpmcp/list-posts', 'minute' );
		$key2 = RateLimitCalculator::buildUserKey( 2, 'fa-wpmcp/list-posts', 'minute' );

		$this->assertNotEquals( $key1, $key2 );
	}

	/**
	 * Test buildIpKey and buildUserKey produce different keys.
	 *
	 * @return void
	 */
	public function test_ip_and_user_keys_are_different(): void {
		$ip_key   = RateLimitCalculator::buildIpKey( '192.168.1.1', 'fa-wpmcp/list-posts', 'minute' );
		$user_key = RateLimitCalculator::buildUserKey( 1, 'fa-wpmcp/list-posts', 'minute' );

		$this->assertNotEquals( $ip_key, $user_key );
		$this->assertStringContainsString( '_ip_', $ip_key );
		$this->assertStringContainsString( '_user_', $user_key );
	}
}
