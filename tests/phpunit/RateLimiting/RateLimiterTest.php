<?php
/**
 * Tests for RateLimiter orchestration.
 *
 * @package FAWpmcp\Tests\RateLimiting
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\RateLimiting;

use FAWpmcp\RateLimiting\RateLimiter;
use FAWpmcp\RateLimiting\RateLimitStore;
use FAWpmcp\RateLimiting\RateLimitConfig;
use FAWpmcp\ValueObjects\RateLimitResult;
use PHPUnit\Framework\TestCase;
use Mockery;

/**
 * Test RateLimiter orchestration.
 *
 * @package FAWpmcp\Tests\RateLimiting
 */
class RateLimiterTest extends TestCase {
	/**
	 * Tear down Mockery after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * Test check returns allowed when under limits.
	 *
	 * @return void
	 */
	public function test_check_returns_allowed_when_under_limits(): void {
		$store = Mockery::mock( RateLimitStore::class );
		$store->shouldReceive( 'get' )
			->andReturn( 10 ); // Low count.

		$config = Mockery::mock( RateLimitConfig::class );
		$config->shouldReceive( 'get_all' )
			->andReturn( array() ); // Use defaults.

		$limiter = new RateLimiter( $store, $config );

		$result = $limiter->check( 'fa-wpmcp/list-posts', 1, '127.0.0.1' );

		$this->assertTrue( $result->allowed );
		$this->assertInstanceOf( RateLimitResult::class, $result );
	}

	/**
	 * Test check returns denied when over minute limit.
	 *
	 * @return void
	 */
	public function test_check_returns_denied_when_over_minute_limit(): void {
		$store = Mockery::mock( RateLimitStore::class );
		$store->shouldReceive( 'get' )
			->andReturnUsing(
				function ( $key ) {
					// Return high count for minute key, low for hour.
					if ( str_contains( $key, '_minute' ) ) {
						return 100; // Over limit.
					}
					return 10;
				}
			);

		$config = Mockery::mock( RateLimitConfig::class );
		$config->shouldReceive( 'get_all' )
			->andReturn( array() ); // Use defaults (60/min).

		$limiter = new RateLimiter( $store, $config );

		$result = $limiter->check( 'fa-wpmcp/list-posts', 1, '127.0.0.1' );

		$this->assertFalse( $result->allowed );
		$this->assertEquals( 'minute', $result->limit_type );
	}

	/**
	 * Test check returns denied when over hour limit.
	 *
	 * @return void
	 */
	public function test_check_returns_denied_when_over_hour_limit(): void {
		$store = Mockery::mock( RateLimitStore::class );
		$store->shouldReceive( 'get' )
			->andReturnUsing(
				function ( $key ) {
					// Return low count for minute key, high for hour.
					if ( str_contains( $key, '_hour' ) ) {
						return 600; // Over limit (default 500).
					}
					return 10;
				}
			);

		$config = Mockery::mock( RateLimitConfig::class );
		$config->shouldReceive( 'get_all' )
			->andReturn( array() ); // Use defaults (500/hour).

		$limiter = new RateLimiter( $store, $config );

		$result = $limiter->check( 'fa-wpmcp/list-posts', 1, '127.0.0.1' );

		$this->assertFalse( $result->allowed );
		$this->assertEquals( 'hour', $result->limit_type );
	}

	/**
	 * Test check uses custom config for ability.
	 *
	 * @return void
	 */
	public function test_check_uses_custom_config(): void {
		$store = Mockery::mock( RateLimitStore::class );
		$store->shouldReceive( 'get' )
			->andReturn( 5 ); // Under custom limit of 10.

		$config = Mockery::mock( RateLimitConfig::class );
		$config->shouldReceive( 'get_all' )
			->andReturn(
				array(
					'fa-wpmcp/create-post' => array(
						'requests_per_minute' => 10,
						'requests_per_hour'   => 100,
					),
				)
			);

		$limiter = new RateLimiter( $store, $config );

		$result = $limiter->check( 'fa-wpmcp/create-post', 1, '127.0.0.1' );

		$this->assertTrue( $result->allowed );
	}

	/**
	 * Test record increments both minute and hour counters.
	 *
	 * @return void
	 */
	public function test_record_increments_both_counters(): void {
		$store = Mockery::mock( RateLimitStore::class );
		$store->shouldReceive( 'increment' )
			->twice(); // Once for minute, once for hour.

		$config = Mockery::mock( RateLimitConfig::class );

		$limiter = new RateLimiter( $store, $config );

		$limiter->record( 'fa-wpmcp/list-posts', 1, '127.0.0.1' );

		// Verify increment was called twice via Mockery.
		$this->assertTrue( true ); // Mockery expectations verify this.
	}

	/**
	 * Test record uses correct TTL for minute counter.
	 *
	 * @return void
	 */
	public function test_record_uses_correct_minute_ttl(): void {
		$store = Mockery::mock( RateLimitStore::class );
		$store->shouldReceive( 'increment' )
			->with( Mockery::on( fn( $key ) => str_contains( $key, '_minute' ) ), 60 )
			->once();
		$store->shouldReceive( 'increment' )
			->with( Mockery::on( fn( $key ) => str_contains( $key, '_hour' ) ), 3600 )
			->once();

		$config = Mockery::mock( RateLimitConfig::class );

		$limiter = new RateLimiter( $store, $config );

		$limiter->record( 'fa-wpmcp/list-posts', 1, '127.0.0.1' );

		$this->assertTrue( true ); // Mockery expectations verify TTLs.
	}

	/**
	 * Test check and record workflow.
	 *
	 * @return void
	 */
	public function test_check_and_record_workflow(): void {
		$store = Mockery::mock( RateLimitStore::class );
		$store->shouldReceive( 'get' )
			->andReturn( 10 ); // Under limit.
		$store->shouldReceive( 'increment' )
			->twice(); // Record increments both.

		$config = Mockery::mock( RateLimitConfig::class );
		$config->shouldReceive( 'get_all' )
			->andReturn( array() );

		$limiter = new RateLimiter( $store, $config );

		// Check first.
		$result = $limiter->check( 'fa-wpmcp/list-posts', 1, '127.0.0.1' );
		$this->assertTrue( $result->allowed );

		// Then record.
		$limiter->record( 'fa-wpmcp/list-posts', 1, '127.0.0.1' );

		$this->assertTrue( true ); // Workflow completed.
	}

	/**
	 * Test check with zero user ID (anonymous).
	 *
	 * @return void
	 */
	public function test_check_with_zero_user_id(): void {
		$store = Mockery::mock( RateLimitStore::class );
		$store->shouldReceive( 'get' )
			->andReturn( 10 );

		$config = Mockery::mock( RateLimitConfig::class );
		$config->shouldReceive( 'get_all' )
			->andReturn( array() );

		$limiter = new RateLimiter( $store, $config );

		$result = $limiter->check( 'fa-wpmcp/list-posts', 0, '192.168.1.1' );

		$this->assertTrue( $result->allowed );
	}

	/**
	 * Test result contains retry after when denied.
	 *
	 * @return void
	 */
	public function test_result_contains_retry_after_when_denied(): void {
		$store = Mockery::mock( RateLimitStore::class );
		$store->shouldReceive( 'get' )
			->andReturnUsing(
				function ( $key ) {
					if ( str_contains( $key, '_minute' ) ) {
						return 100; // Over limit.
					}
					return 10;
				}
			);

		$config = Mockery::mock( RateLimitConfig::class );
		$config->shouldReceive( 'get_all' )
			->andReturn( array() );

		$limiter = new RateLimiter( $store, $config );

		$result = $limiter->check( 'fa-wpmcp/list-posts', 1, '127.0.0.1' );

		$this->assertFalse( $result->allowed );
		$this->assertGreaterThan( 0, $result->retry_after );
		$this->assertLessThanOrEqual( 60, $result->retry_after );
	}
}
