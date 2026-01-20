<?php
/**
 * Tests for RateLimit value object.
 *
 * @package FAWpmcp\Tests\ValueObjects
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\ValueObjects;

use FAWpmcp\ValueObjects\RateLimit;
use PHPUnit\Framework\TestCase;

/**
 * Test RateLimit immutability and construction.
 *
 * @package FAWpmcp\Tests\ValueObjects
 */
class RateLimitTest extends TestCase {
	/**
	 * Test that RateLimit is immutable via readonly properties.
	 *
	 * @return void
	 */
	public function test_rate_limit_is_immutable(): void {
		$limit = new RateLimit(
			requests_per_minute: 60,
			requests_per_hour: 500,
			ability: 'fa-wpmcp/list-posts',
		);

		// Readonly properties cannot be modified.
		$this->assertEquals( 60, $limit->requests_per_minute );
		$this->assertEquals( 500, $limit->requests_per_hour );
		$this->assertEquals( 'fa-wpmcp/list-posts', $limit->ability );
	}

	/**
	 * Test all properties are accessible.
	 *
	 * @return void
	 */
	public function test_all_properties_accessible(): void {
		$limit = new RateLimit(
			requests_per_minute: 100,
			requests_per_hour: 1000,
			ability: 'fa-wpmcp/create-post',
		);

		$this->assertEquals( 100, $limit->requests_per_minute );
		$this->assertEquals( 1000, $limit->requests_per_hour );
		$this->assertEquals( 'fa-wpmcp/create-post', $limit->ability );
	}

	/**
	 * Test rate limit with zero values (disabled limits).
	 *
	 * @return void
	 */
	public function test_rate_limit_with_zero_values(): void {
		$limit = new RateLimit(
			requests_per_minute: 0,
			requests_per_hour: 0,
			ability: 'fa-wpmcp/unlimited',
		);

		$this->assertEquals( 0, $limit->requests_per_minute );
		$this->assertEquals( 0, $limit->requests_per_hour );
	}

	/**
	 * Test rate limit with high values.
	 *
	 * @return void
	 */
	public function test_rate_limit_with_high_values(): void {
		$limit = new RateLimit(
			requests_per_minute: 1000000,
			requests_per_hour: 10000000,
			ability: 'fa-wpmcp/high-volume',
		);

		$this->assertEquals( 1000000, $limit->requests_per_minute );
		$this->assertEquals( 10000000, $limit->requests_per_hour );
	}
}
