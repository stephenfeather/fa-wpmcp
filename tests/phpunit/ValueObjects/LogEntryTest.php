<?php
/**
 * Tests for LogEntry value object.
 *
 * @package FAWpmcp\Tests\ValueObjects
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\ValueObjects;

use FAWpmcp\ValueObjects\LogEntry;
use PHPUnit\Framework\TestCase;

/**
 * Test LogEntry immutability and construction.
 *
 * @package FAWpmcp\Tests\ValueObjects
 */
class LogEntryTest extends TestCase {
	/**
	 * Test that LogEntry is immutable via readonly properties.
	 *
	 * @return void
	 */
	public function test_log_entry_is_immutable(): void {
		$entry = new LogEntry(
			correlation_id: 'test-123',
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
			execution_time_ms: 100,
		);

		// Readonly properties cannot be modified.
		$this->assertEquals( 'test-123', $entry->correlation_id );
		$this->assertTrue( $entry->success );
		$this->assertEquals( 100, $entry->execution_time_ms );
	}

	/**
	 * Test all properties are accessible.
	 *
	 * @return void
	 */
	public function test_all_properties_accessible(): void {
		$input_data  = array(
			'status'   => 'publish',
			'per_page' => 10,
		);
		$output_data = array(
			'posts' => array(
				array( 'id' => 1 ),
				array( 'id' => 2 ),
			),
			'total' => 2,
		);

		$entry = new LogEntry(
			correlation_id: 'uuid-456',
			user_id: 42,
			user_login: 'testuser',
			ip_address: '192.168.1.100',
			ability_name: 'fa-wpmcp/create-post',
			ability_category: 'posts-pages',
			operation_type: 'write',
			input_data: $input_data,
			output_data: $output_data,
			success: true,
			error_message: null,
			execution_time_ms: 250,
		);

		$this->assertEquals( 'uuid-456', $entry->correlation_id );
		$this->assertEquals( 42, $entry->user_id );
		$this->assertEquals( 'testuser', $entry->user_login );
		$this->assertEquals( '192.168.1.100', $entry->ip_address );
		$this->assertEquals( 'fa-wpmcp/create-post', $entry->ability_name );
		$this->assertEquals( 'posts-pages', $entry->ability_category );
		$this->assertEquals( 'write', $entry->operation_type );
		$this->assertEquals( $input_data, $entry->input_data );
		$this->assertEquals( $output_data, $entry->output_data );
		$this->assertTrue( $entry->success );
		$this->assertNull( $entry->error_message );
		$this->assertEquals( 250, $entry->execution_time_ms );
	}

	/**
	 * Test failed execution with error message.
	 *
	 * @return void
	 */
	public function test_log_entry_with_error(): void {
		$entry = new LogEntry(
			correlation_id: 'error-789',
			user_id: 1,
			user_login: 'admin',
			ip_address: '127.0.0.1',
			ability_name: 'fa-wpmcp/update-post',
			ability_category: 'posts-pages',
			operation_type: 'write',
			input_data: array( 'post_id' => 999 ),
			output_data: null,
			success: false,
			error_message: 'Post not found',
			execution_time_ms: 50,
		);

		$this->assertFalse( $entry->success );
		$this->assertEquals( 'Post not found', $entry->error_message );
		$this->assertNull( $entry->output_data );
	}

	/**
	 * Test with null input/output data.
	 *
	 * @return void
	 */
	public function test_log_entry_with_null_data(): void {
		$entry = new LogEntry(
			correlation_id: 'null-data',
			user_id: 1,
			user_login: 'admin',
			ip_address: '127.0.0.1',
			ability_name: 'fa-wpmcp/test-ability',
			ability_category: 'test',
			operation_type: 'read',
			input_data: null,
			output_data: null,
			success: true,
			error_message: null,
			execution_time_ms: 10,
		);

		$this->assertNull( $entry->input_data );
		$this->assertNull( $entry->output_data );
		$this->assertNull( $entry->error_message );
	}
}
