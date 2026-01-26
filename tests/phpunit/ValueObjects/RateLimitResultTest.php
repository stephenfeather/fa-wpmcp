<?php

/**
 * Tests for RateLimitResult value object.
 *
 * @package FAWpmcp\Tests\ValueObjects
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\ValueObjects;

use FAWpmcp\ValueObjects\RateLimitResult;
use PHPUnit\Framework\TestCase;

/**
 * Test RateLimitResult immutability and factory methods.
 *
 * @package FAWpmcp\Tests\ValueObjects
 */
class RateLimitResultTest extends TestCase {

	/**
	 * Test that RateLimitResult is immutable via readonly properties.
	 *
	 * @return void
	 */
	public function test_rate_limit_result_is_immutable(): void {
		$result = new RateLimitResult(
			allowed: true,
			limit_type: 'none',
			retry_after: 0,
		);

		// Readonly properties cannot be modified.
		$this->assertTrue( $result->allowed );
		$this->assertEquals( 'none', $result->limit_type );
		$this->assertEquals( 0, $result->retry_after );
	}

	/**
	 * Test allowed factory method creates allowed result.
	 *
	 * @return void
	 */
	public function test_allowed_factory_method(): void {
		$result = RateLimitResult::allowed();

		$this->assertTrue( $result->allowed );
		$this->assertEquals( 'none', $result->limit_type );
		$this->assertEquals( 0, $result->retry_after );
	}

	/**
	 * Test denied factory method creates denied result with minute limit.
	 *
	 * @return void
	 */
	public function test_denied_factory_method_minute_limit(): void {
		$result = RateLimitResult::denied( 45, 'minute' );

		$this->assertFalse( $result->allowed );
		$this->assertEquals( 'minute', $result->limit_type );
		$this->assertEquals( 45, $result->retry_after );
	}

	/**
	 * Test denied factory method creates denied result with hour limit.
	 *
	 * @return void
	 */
	public function test_denied_factory_method_hour_limit(): void {
		$result = RateLimitResult::denied( 1800, 'hour' );

		$this->assertFalse( $result->allowed );
		$this->assertEquals( 'hour', $result->limit_type );
		$this->assertEquals( 1800, $result->retry_after );
	}

	/**
	 * Test all properties accessible.
	 *
	 * @return void
	 */
	public function test_all_properties_accessible(): void {
		$result = new RateLimitResult(
			allowed: false,
			limit_type: 'hour',
			retry_after: 3000,
		);

		$this->assertFalse( $result->allowed );
		$this->assertEquals( 'hour', $result->limit_type );
		$this->assertEquals( 3000, $result->retry_after );
	}
}
