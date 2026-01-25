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

		Functions\expect( 'get_option' )
			->once()
			->with( 'fa_wpmcp_anonymize_ip', true )
			->andReturn( true );

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
						&& 1 === $data['success']
						&& '127.0.0.0' === $data['ip_address']; // Anonymized.
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
		Functions\expect( 'get_option' )
			->once()
			->with( 'fa_wpmcp_anonymize_ip', true )
			->andReturn( true );

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
		$this->assertFalse( $repo->updateByCorrelationId( 'corr-3', array() ) );
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
		$result = $repo->updateByCorrelationId(
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
		$result = $repo->getByCorrelationId( 'corr-5' );

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
		$this->assertNull( $repo->getByCorrelationId( 'missing' ) );
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
		$this->assertSame( 3, $repo->deleteOlderThan( 7 ) );
	}

	/**
	 * Test IP anonymization masks IPv4 last octet when enabled.
	 *
	 * @return void
	 */
	public function test_insert_anonymizes_ipv4_when_enabled(): void {
		Functions\expect( 'current_time' )->andReturn( '2026-01-25 10:00:00' );
		Functions\expect( 'get_option' )
			->once()
			->with( 'fa_wpmcp_anonymize_ip', true )
			->andReturn( true );

		$entry = new LogEntry(
			correlation_id: 'corr-ip-1',
			user_id: 1,
			user_login: 'admin',
			ip_address: '192.168.1.100',
			ability_name: 'fa-wpmcp/list-posts',
			ability_category: 'posts-pages',
			operation_type: 'read',
			input_data: null,
			output_data: null,
			success: true,
			error_message: null,
			execution_time_ms: 10,
		);

		$wpdb = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->insert_id = 99;
		$wpdb->shouldReceive( 'insert' )
			->once()
			->with(
				'wp_fa_wpmcp_activity_log',
				Mockery::on( function( $data ) {
					return '192.168.1.0' === $data['ip_address'];
				} ),
				Mockery::type( 'array' )
			)
			->andReturn( 1 );

		$repo = new LogRepository( $wpdb );
		$this->assertSame( 99, $repo->insert( $entry ) );
	}

	/**
	 * Test IP anonymization masks IPv6 last 80 bits when enabled.
	 *
	 * @return void
	 */
	public function test_insert_anonymizes_ipv6_when_enabled(): void {
		Functions\expect( 'current_time' )->andReturn( '2026-01-25 10:00:00' );
		Functions\expect( 'get_option' )
			->once()
			->with( 'fa_wpmcp_anonymize_ip', true )
			->andReturn( true );

		$entry = new LogEntry(
			correlation_id: 'corr-ip-2',
			user_id: 1,
			user_login: 'admin',
			ip_address: '2001:db8::1',
			ability_name: 'fa-wpmcp/list-posts',
			ability_category: 'posts-pages',
			operation_type: 'read',
			input_data: null,
			output_data: null,
			success: true,
			error_message: null,
			execution_time_ms: 10,
		);

		$wpdb = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->insert_id = 100;
		$wpdb->shouldReceive( 'insert' )
			->once()
			->with(
				'wp_fa_wpmcp_activity_log',
				Mockery::on( function( $data ) {
					return '2001:db8::' === $data['ip_address'];
				} ),
				Mockery::type( 'array' )
			)
			->andReturn( 1 );

		$repo = new LogRepository( $wpdb );
		$this->assertSame( 100, $repo->insert( $entry ) );
	}

	/**
	 * Test IP not anonymized when option is disabled.
	 *
	 * @return void
	 */
	public function test_insert_preserves_ip_when_anonymization_disabled(): void {
		Functions\expect( 'current_time' )->andReturn( '2026-01-25 10:00:00' );
		Functions\expect( 'get_option' )
			->once()
			->with( 'fa_wpmcp_anonymize_ip', true )
			->andReturn( false );

		$entry = new LogEntry(
			correlation_id: 'corr-ip-3',
			user_id: 1,
			user_login: 'admin',
			ip_address: '192.168.1.100',
			ability_name: 'fa-wpmcp/list-posts',
			ability_category: 'posts-pages',
			operation_type: 'read',
			input_data: null,
			output_data: null,
			success: true,
			error_message: null,
			execution_time_ms: 10,
		);

		$wpdb = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->insert_id = 101;
		$wpdb->shouldReceive( 'insert' )
			->once()
			->with(
				'wp_fa_wpmcp_activity_log',
				Mockery::on( function( $data ) {
					return '192.168.1.100' === $data['ip_address'];
				} ),
				Mockery::type( 'array' )
			)
			->andReturn( 1 );

		$repo = new LogRepository( $wpdb );
		$this->assertSame( 101, $repo->insert( $entry ) );
	}

	/**
	 * Test IPv4 anonymization handles various formats.
	 *
	 * @return void
	 */
	public function test_insert_anonymizes_various_ipv4_formats(): void {
		Functions\expect( 'current_time' )->andReturn( '2026-01-25 10:00:00' );
		Functions\expect( 'get_option' )
			->times( 3 )
			->with( 'fa_wpmcp_anonymize_ip', true )
			->andReturn( true );

		$wpdb = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->insert_id = 200;

		// Test 127.0.0.1 → 127.0.0.0.
		$wpdb->shouldReceive( 'insert' )
			->once()
			->with(
				Mockery::type( 'string' ),
				Mockery::on( function( $data ) {
					return '127.0.0.0' === $data['ip_address'];
				} ),
				Mockery::type( 'array' )
			)
			->andReturn( 1 );

		$entry1 = new LogEntry(
			correlation_id: 'c1',
			user_id: 1,
			user_login: 'admin',
			ip_address: '127.0.0.1',
			ability_name: 'test',
			ability_category: 'test',
			operation_type: 'read',
			input_data: null,
			output_data: null,
			success: true,
			error_message: null,
			execution_time_ms: 1,
		);

		$repo = new LogRepository( $wpdb );
		$repo->insert( $entry1 );

		// Test 10.0.0.255 → 10.0.0.0.
		$wpdb->shouldReceive( 'insert' )
			->once()
			->with(
				Mockery::type( 'string' ),
				Mockery::on( function( $data ) {
					return '10.0.0.0' === $data['ip_address'];
				} ),
				Mockery::type( 'array' )
			)
			->andReturn( 1 );

		$entry2 = new LogEntry(
			correlation_id: 'c2',
			user_id: 1,
			user_login: 'admin',
			ip_address: '10.0.0.255',
			ability_name: 'test',
			ability_category: 'test',
			operation_type: 'read',
			input_data: null,
			output_data: null,
			success: true,
			error_message: null,
			execution_time_ms: 1,
		);

		$repo->insert( $entry2 );

		// Test 172.16.254.1 → 172.16.254.0.
		$wpdb->shouldReceive( 'insert' )
			->once()
			->with(
				Mockery::type( 'string' ),
				Mockery::on( function( $data ) {
					return '172.16.254.0' === $data['ip_address'];
				} ),
				Mockery::type( 'array' )
			)
			->andReturn( 1 );

		$entry3 = new LogEntry(
			correlation_id: 'c3',
			user_id: 1,
			user_login: 'admin',
			ip_address: '172.16.254.1',
			ability_name: 'test',
			ability_category: 'test',
			operation_type: 'read',
			input_data: null,
			output_data: null,
			success: true,
			error_message: null,
			execution_time_ms: 1,
		);

		$repo->insert( $entry3 );
	}

	/**
	 * Test IPv6 anonymization handles various formats.
	 *
	 * @return void
	 */
	public function test_insert_anonymizes_various_ipv6_formats(): void {
		Functions\expect( 'current_time' )->andReturn( '2026-01-25 10:00:00' );
		Functions\expect( 'get_option' )
			->times( 2 )
			->with( 'fa_wpmcp_anonymize_ip', true )
			->andReturn( true );

		$wpdb = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->insert_id = 300;

		// Test full IPv6.
		$wpdb->shouldReceive( 'insert' )
			->once()
			->with(
				Mockery::type( 'string' ),
				Mockery::on( function( $data ) {
					return '2001:db8::' === $data['ip_address'];
				} ),
				Mockery::type( 'array' )
			)
			->andReturn( 1 );

		$entry1 = new LogEntry(
			correlation_id: 'c1',
			user_id: 1,
			user_login: 'admin',
			ip_address: '2001:0db8:0000:0000:0000:ff00:0042:8329',
			ability_name: 'test',
			ability_category: 'test',
			operation_type: 'read',
			input_data: null,
			output_data: null,
			success: true,
			error_message: null,
			execution_time_ms: 1,
		);

		$repo = new LogRepository( $wpdb );
		$repo->insert( $entry1 );

		// Test compressed IPv6.
		$wpdb->shouldReceive( 'insert' )
			->once()
			->with(
				Mockery::type( 'string' ),
				Mockery::on( function( $data ) {
					return 'fe80::' === $data['ip_address'];
				} ),
				Mockery::type( 'array' )
			)
			->andReturn( 1 );

		$entry2 = new LogEntry(
			correlation_id: 'c2',
			user_id: 1,
			user_login: 'admin',
			ip_address: 'fe80::1',
			ability_name: 'test',
			ability_category: 'test',
			operation_type: 'read',
			input_data: null,
			output_data: null,
			success: true,
			error_message: null,
			execution_time_ms: 1,
		);

		$repo->insert( $entry2 );
	}

	/**
	 * Test invalid IP addresses are preserved as-is.
	 *
	 * @return void
	 */
	public function test_insert_preserves_invalid_ip_addresses(): void {
		Functions\expect( 'current_time' )->andReturn( '2026-01-25 10:00:00' );
		Functions\expect( 'get_option' )
			->once()
			->with( 'fa_wpmcp_anonymize_ip', true )
			->andReturn( true );

		$entry = new LogEntry(
			correlation_id: 'corr-invalid',
			user_id: 1,
			user_login: 'admin',
			ip_address: 'not-an-ip',
			ability_name: 'test',
			ability_category: 'test',
			operation_type: 'read',
			input_data: null,
			output_data: null,
			success: true,
			error_message: null,
			execution_time_ms: 1,
		);

		$wpdb = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->insert_id = 400;
		$wpdb->shouldReceive( 'insert' )
			->once()
			->with(
				Mockery::type( 'string' ),
				Mockery::on( function( $data ) {
					return 'not-an-ip' === $data['ip_address'];
				} ),
				Mockery::type( 'array' )
			)
			->andReturn( 1 );

		$repo = new LogRepository( $wpdb );
		$this->assertSame( 400, $repo->insert( $entry ) );
	}
}
