<?php
/**
 * Tests for LogRepository.
 *
 * @package FAWpmcp\Tests\Logging
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Logging;

use Brain\Monkey\Functions;
use FAWpmcp\Logging\LogRepository;
use FAWpmcp\ValueObjects\LogEntry;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test LogRepository database operations.
 */
final class LogRepositoryTest extends TestCase {
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
	 * Test insert returns insert_id on success.
	 *
	 * @return void
	 */
	public function test_insert_returns_insert_id(): void {
		Functions\expect( 'current_time' )
			->once()
			->with( 'mysql' )
			->andReturn( '2026-01-21 10:00:00' );

		Functions\expect( 'wp_json_encode' )
			->twice()
			->andReturn( '{"json":true}' );

		$entry = new LogEntry(
			correlation_id: 'corr-1',
			user_id: 1,
			user_login: 'admin',
			ip_address: '127.0.0.1',
			ability_name: 'fa-wpmcp/list-posts',
			ability_category: 'posts-pages',
			operation_type: 'read',
			input_data: array( 'page' => 1 ),
			output_data: array( 'posts' => array() ),
			success: true,
			error_message: null,
			execution_time_ms: 12,
		);

		$wpdb = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->insert_id = 55;
		$wpdb->shouldReceive( 'insert' )
			->once()
			->with(
				'wp_fa_wpmcp_activity_log',
				Mockery::on( function( $data ) {
					return 'corr-1' === $data['correlation_id']
						&& '2026-01-21 10:00:00' === $data['timestamp']
						&& '{"json":true}' === $data['input_data']
						&& '{"json":true}' === $data['output_data']
						&& 1 === $data['success'];
				} ),
				Mockery::type( 'array' )
			)
			->andReturn( 1 );

		$repo = new LogRepository( $wpdb );
		$this->assertSame( 55, $repo->insert( $entry ) );
	}

	/**
	 * Test insert returns false on failure.
	 *
	 * @return void
	 */
	public function test_insert_returns_false_on_failure(): void {
		Functions\expect( 'current_time' )->andReturn( '2026-01-21 10:00:00' );

		$entry = new LogEntry(
			correlation_id: 'corr-2',
			user_id: 1,
			user_login: 'admin',
			ip_address: '127.0.0.1',
			ability_name: 'fa-wpmcp/list-posts',
			ability_category: 'posts-pages',
			operation_type: 'read',
			input_data: null,
			output_data: null,
			success: false,
			error_message: 'fail',
			execution_time_ms: 20,
		);

		$wpdb = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'insert' )
			->once()
			->andReturn( false );

		$repo = new LogRepository( $wpdb );
		$this->assertFalse( $repo->insert( $entry ) );
	}

	/**
	 * Test update returns false when no update data supplied.
	 *
	 * @return void
	 */
	public function test_update_by_correlation_id_returns_false_when_no_data(): void {
		$wpdb = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';

		$repo = new LogRepository( $wpdb );
		$this->assertFalse( $repo->update_by_correlation_id( 'corr-3', array() ) );
	}

	/**
	 * Test update_by_correlation_id updates fields.
	 *
	 * @return void
	 */
	public function test_update_by_correlation_id_updates_fields(): void {
		Functions\expect( 'wp_json_encode' )
			->once()
			->andReturn( '{"json":true}' );

		$wpdb = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'update' )
			->once()
			->with(
				'wp_fa_wpmcp_activity_log',
				Mockery::on( function( $data ) {
					return '{"json":true}' === $data['output_data']
						&& 1 === $data['success']
						&& 'done' === $data['error_message']
						&& 42 === $data['execution_time_ms'];
				} ),
				array( 'correlation_id' => 'corr-4' ),
				array( '%s', '%d', '%s', '%d' ),
				array( '%s' )
			)
			->andReturn( 1 );

		$repo = new LogRepository( $wpdb );
		$result = $repo->update_by_correlation_id(
			'corr-4',
			array(
				'output_data'       => array( 'status' => 'ok' ),
				'success'           => true,
				'error_message'     => 'done',
				'execution_time_ms' => 42,
			)
		);

		$this->assertSame( 1, $result );
	}

	/**
	 * Test get_by_correlation_id returns row when found.
	 *
	 * @return void
	 */
	public function test_get_by_correlation_id_returns_row(): void {
		$wpdb = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'prepare' )
			->once()
			->with( Mockery::type( 'string' ), 'corr-5' )
			->andReturn( 'prepared' );
		$wpdb->shouldReceive( 'get_row' )
			->once()
			->with( 'prepared' )
			->andReturn( (object) array( 'correlation_id' => 'corr-5' ) );

		$repo = new LogRepository( $wpdb );
		$result = $repo->get_by_correlation_id( 'corr-5' );

		$this->assertNotNull( $result );
	}

	/**
	 * Test get_by_correlation_id returns null when missing.
	 *
	 * @return void
	 */
	public function test_get_by_correlation_id_returns_null_when_missing(): void {
		$wpdb = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'prepare' )
			->once()
			->andReturn( 'prepared' );
		$wpdb->shouldReceive( 'get_row' )
			->once()
			->with( 'prepared' )
			->andReturn( false );

		$repo = new LogRepository( $wpdb );
		$this->assertNull( $repo->get_by_correlation_id( 'missing' ) );
	}

	/**
	 * Test delete_older_than issues delete query.
	 *
	 * @return void
	 */
	public function test_delete_older_than_issues_query(): void {
		$wpdb = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'prepare' )
			->once()
			->with( Mockery::type( 'string' ), 7 )
			->andReturn( 'prepared' );
		$wpdb->shouldReceive( 'query' )
			->once()
			->with( 'prepared' )
			->andReturn( 3 );

		$repo = new LogRepository( $wpdb );
		$this->assertSame( 3, $repo->delete_older_than( 7 ) );
	}
}
