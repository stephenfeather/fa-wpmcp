<?php
/**
 * Tests for LogEntryBuilder.
 *
 * @package FAWpmcp\Tests\Logging
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Logging;

use FAWpmcp\Logging\LogEntryBuilder;
use FAWpmcp\ValueObjects\LogEntry;
use PHPUnit\Framework\TestCase;

/**
 * Test LogEntryBuilder immutable builder pattern.
 *
 * @package FAWpmcp\Tests\Logging
 */
class LogEntryBuilderTest extends TestCase {
	/**
	 * Test builder creates complete log entry.
	 *
	 * @return void
	 */
	public function test_builds_complete_log_entry(): void {
		$entry = LogEntryBuilder::create()
			->with_correlation_id( 'uuid-123' )
			->with_user( 1, 'admin' )
			->with_ip_address( '192.168.1.1' )
			->with_ability( 'fa-wpmcp/list-posts', 'posts-pages', 'read' )
			->with_input( array( 'page' => 1 ) )
			->with_output( array( 'posts' => array() ) )
			->with_success( true )
			->with_execution_time( 150 )
			->build();

		$this->assertInstanceOf( LogEntry::class, $entry );
		$this->assertEquals( 'uuid-123', $entry->correlation_id );
		$this->assertEquals( 1, $entry->user_id );
		$this->assertEquals( 'admin', $entry->user_login );
		$this->assertEquals( '192.168.1.1', $entry->ip_address );
		$this->assertEquals( 'fa-wpmcp/list-posts', $entry->ability_name );
		$this->assertEquals( 'posts-pages', $entry->ability_category );
		$this->assertEquals( 'read', $entry->operation_type );
		$this->assertEquals( array( 'page' => 1 ), $entry->input_data );
		$this->assertEquals( array( 'posts' => array() ), $entry->output_data );
		$this->assertTrue( $entry->success );
		$this->assertEquals( 150, $entry->execution_time_ms );
	}

	/**
	 * Test builder is immutable (returns new instance).
	 *
	 * @return void
	 */
	public function test_builder_is_immutable(): void {
		$builder1 = LogEntryBuilder::create()->with_correlation_id( 'id-1' );
		$builder2 = $builder1->with_correlation_id( 'id-2' );

		// Original builder unchanged.
		$this->assertNotSame( $builder1, $builder2 );

		$entry1 = $builder1->with_user( 1, 'admin' )->with_ability( 'test', 'test', 'read' )->build();
		$entry2 = $builder2->with_user( 1, 'admin' )->with_ability( 'test', 'test', 'read' )->build();

		$this->assertEquals( 'id-1', $entry1->correlation_id );
		$this->assertEquals( 'id-2', $entry2->correlation_id );
	}

	/**
	 * Test builder with error.
	 *
	 * @return void
	 */
	public function test_builds_entry_with_error(): void {
		$entry = LogEntryBuilder::create()
			->with_correlation_id( 'error-123' )
			->with_user( 1, 'admin' )
			->with_ip_address( '127.0.0.1' )
			->with_ability( 'fa-wpmcp/create-post', 'posts-pages', 'write' )
			->with_input( array( 'title' => 'Test' ) )
			->with_success( false )
			->with_error( 'Validation failed' )
			->with_execution_time( 75 )
			->build();

		$this->assertFalse( $entry->success );
		$this->assertEquals( 'Validation failed', $entry->error_message );
		$this->assertNull( $entry->output_data );
	}

	/**
	 * Test builder provides defaults for missing fields.
	 *
	 * @return void
	 */
	public function test_builder_provides_defaults(): void {
		$entry = LogEntryBuilder::create()->build();

		$this->assertInstanceOf( LogEntry::class, $entry );
		$this->assertEquals( '', $entry->correlation_id );
		$this->assertEquals( 0, $entry->user_id );
		$this->assertEquals( '', $entry->user_login );
		$this->assertEquals( '', $entry->ip_address );
		$this->assertEquals( '', $entry->ability_name );
		$this->assertEquals( '', $entry->ability_category );
		$this->assertEquals( 'read', $entry->operation_type ); // Default operation.
		$this->assertNull( $entry->input_data );
		$this->assertNull( $entry->output_data );
		$this->assertFalse( $entry->success ); // Default to false.
		$this->assertNull( $entry->error_message );
		$this->assertEquals( 0, $entry->execution_time_ms );
	}

	/**
	 * Test chaining multiple methods.
	 *
	 * @return void
	 */
	public function test_method_chaining(): void {
		$entry = LogEntryBuilder::create()
			->with_correlation_id( 'chain-123' )
			->with_user( 5, 'editor' )
			->with_ip_address( '10.0.0.1' )
			->with_ability( 'fa-wpmcp/update-post', 'posts-pages', 'write' )
			->with_input( array( 'post_id' => 42 ) )
			->with_output( array( 'updated' => true ) )
			->with_success( true )
			->with_execution_time( 200 )
			->build();

		$this->assertEquals( 'chain-123', $entry->correlation_id );
		$this->assertEquals( 5, $entry->user_id );
		$this->assertEquals( 'editor', $entry->user_login );
		$this->assertEquals( 200, $entry->execution_time_ms );
	}
}
