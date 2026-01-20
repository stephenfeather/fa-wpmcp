<?php
/**
 * Tests for ActivityLogger.
 *
 * @package FAWpmcp\Tests\Logging
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Logging;

use FAWpmcp\Logging\ActivityLogger;
use FAWpmcp\Logging\LogRepository;
use FAWpmcp\ValueObjects\LogEntry;
use PHPUnit\Framework\TestCase;
use Mockery;

/**
 * Test ActivityLogger orchestration.
 *
 * @package FAWpmcp\Tests\Logging
 */
class ActivityLoggerTest extends TestCase {
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
	 * Test logBeforeExecute returns correlation ID.
	 *
	 * @return void
	 */
	public function test_log_before_execute_returns_correlation_id(): void {
		$repository = Mockery::mock( LogRepository::class );
		$repository->shouldReceive( 'insert' )->once();

		$uuid_generator = fn() => 'mock-uuid-123';
		$logger         = new ActivityLogger( $repository, $uuid_generator );

		$correlation_id = $logger->log_before_execute(
			'fa-wpmcp/list-posts',
			'posts-pages',
			'read',
			1,
			'admin',
			'127.0.0.1',
			array( 'page' => 1 )
		);

		$this->assertEquals( 'mock-uuid-123', $correlation_id );
	}

	/**
	 * Test logBeforeExecute inserts entry into repository.
	 *
	 * @return void
	 */
	public function test_log_before_execute_inserts_entry(): void {
		$repository = Mockery::mock( LogRepository::class );
		$repository->shouldReceive( 'insert' )
			->once()
			->with( Mockery::on( fn( $entry ) => $entry instanceof LogEntry && 'test-uuid' === $entry->correlation_id ) )
			->andReturn( 1 );

		$uuid_generator = fn() => 'test-uuid';
		$logger         = new ActivityLogger( $repository, $uuid_generator );

		$correlation_id = $logger->log_before_execute(
			'fa-wpmcp/create-post',
			'posts-pages',
			'write',
			1,
			'admin',
			'192.168.1.1',
			array( 'title' => 'Test Post' )
		);

		$this->assertEquals( 'test-uuid', $correlation_id );
	}

	/**
	 * Test logAfterExecute updates entry.
	 *
	 * @return void
	 */
	public function test_log_after_execute_updates_entry(): void {
		$repository = Mockery::mock( LogRepository::class );
		$repository->shouldReceive( 'insert' )->once()->andReturn( 1 );
		$repository->shouldReceive( 'update_by_correlation_id' )
			->once()
			->with(
				'mock-uuid',
				Mockery::on( fn( $data ) => true === $data['success'] && $data['execution_time_ms'] > 0 )
			)
			->andReturn( 1 );

		$uuid_generator = fn() => 'mock-uuid';
		$logger         = new ActivityLogger( $repository, $uuid_generator );

		$start_time     = microtime( true );
		$correlation_id = $logger->log_before_execute(
			'fa-wpmcp/list-posts',
			'posts-pages',
			'read',
			1,
			'admin',
			'127.0.0.1',
			array()
		);

		// Simulate some execution time.
		usleep( 1000 ); // 1ms.

		$logger->log_after_execute(
			$correlation_id,
			array( 'posts' => array() ),
			true,
			null,
			$start_time
		);

		// Verify correlation ID was returned correctly.
		$this->assertEquals( 'mock-uuid', $correlation_id );
	}

	/**
	 * Test logAfterExecute with failure.
	 *
	 * @return void
	 */
	public function test_log_after_execute_with_failure(): void {
		$repository = Mockery::mock( LogRepository::class );
		$repository->shouldReceive( 'insert' )->once()->andReturn( 1 );
		$repository->shouldReceive( 'update_by_correlation_id' )
			->once()
			->with(
				'fail-uuid',
				Mockery::on( fn( $data ) => false === $data['success'] && 'Permission denied' === $data['error_message'] )
			)
			->andReturn( 1 );

		$uuid_generator = fn() => 'fail-uuid';
		$logger         = new ActivityLogger( $repository, $uuid_generator );

		$start_time     = microtime( true );
		$correlation_id = $logger->log_before_execute(
			'fa-wpmcp/delete-post',
			'posts-pages',
			'write',
			1,
			'admin',
			'127.0.0.1',
			array( 'post_id' => 123 )
		);

		$logger->log_after_execute(
			$correlation_id,
			null,
			false,
			'Permission denied',
			$start_time
		);

		// Verify correlation ID was returned correctly.
		$this->assertEquals( 'fail-uuid', $correlation_id );
	}

	/**
	 * Test execution time is measured correctly.
	 *
	 * @return void
	 */
	public function test_execution_time_measured(): void {
		$repository = Mockery::mock( LogRepository::class );
		$repository->shouldReceive( 'insert' )->once()->andReturn( 1 );
		$repository->shouldReceive( 'update_by_correlation_id' )
			->once()
			->with(
				'time-uuid',
				Mockery::on( fn( $data ) => $data['execution_time_ms'] >= 10 ) // At least 10ms.
			)
			->andReturn( 1 );

		$uuid_generator = fn() => 'time-uuid';
		$logger         = new ActivityLogger( $repository, $uuid_generator );

		$start_time     = microtime( true );
		$correlation_id = $logger->log_before_execute(
			'fa-wpmcp/list-posts',
			'posts-pages',
			'read',
			1,
			'admin',
			'127.0.0.1',
			array()
		);

		// Simulate 10ms execution.
		usleep( 10000 );

		$logger->log_after_execute(
			$correlation_id,
			array( 'posts' => array() ),
			true,
			null,
			$start_time
		);

		// Verify correlation ID was returned correctly.
		$this->assertEquals( 'time-uuid', $correlation_id );
	}
}
