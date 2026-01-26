<?php
/**
 * Tests for TransientRateLimitStore.
 *
 * @package FAWpmcp\Tests\RateLimiting
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\RateLimiting;

use Brain\Monkey\Functions;
use FAWpmcp\RateLimiting\TransientRateLimitStore;
use PHPUnit\Framework\TestCase;

/**
 * Test TransientRateLimitStore behavior.
 */
final class TransientRateLimitStoreTest extends TestCase {

	use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

	protected function setUp(): void {
		parent::setUp();
		\Brain\Monkey\setUp();
	}

	protected function tearDown(): void {
		\Brain\Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Test get returns 0 when transient missing or non-numeric.
	 *
	 * @return void
	 */
	public function test_get_returns_zero_when_missing_or_non_numeric(): void {
		Functions\expect( 'get_transient' )
			->once()
			->with( 'rate-key' )
			->andReturn( false );

		$store = new TransientRateLimitStore();
		$this->assertSame( 0, $store->get( 'rate-key' ) );
	}

	/**
	 * Test get returns integer value.
	 *
	 * @return void
	 */
	public function test_get_returns_integer_value(): void {
		Functions\expect( 'get_transient' )
			->once()
			->with( 'rate-key' )
			->andReturn( '3' );

		$store = new TransientRateLimitStore();
		$this->assertSame( 3, $store->get( 'rate-key' ) );
	}

	/**
	 * Test increment sets transient and returns incremented value.
	 *
	 * @return void
	 */
	public function test_increment_sets_transient(): void {
		Functions\expect( 'get_transient' )
			->once()
			->with( 'rate-key' )
			->andReturn( 2 );

		Functions\expect( 'set_transient' )
			->once()
			->with( 'rate-key', 3, 60 )
			->andReturn( true );

		$store = new TransientRateLimitStore();
		$this->assertSame( 3, $store->increment( 'rate-key', 60 ) );
	}

	/**
	 * Test delete removes transient.
	 *
	 * @return void
	 */
	public function test_delete_removes_transient(): void {
		Functions\expect( 'delete_transient' )
			->once()
			->with( 'rate-key' )
			->andReturn( true );

		$store = new TransientRateLimitStore();
		$this->assertTrue( $store->delete( 'rate-key' ) );
	}
}
