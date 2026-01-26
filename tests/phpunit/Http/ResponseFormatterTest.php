<?php

/**
 * Tests for ResponseFormatter HTTP utility.
 *
 * TDD RED Phase: These tests define the expected behavior of ResponseFormatter
 * before implementation. All tests should fail with "Class not found" initially.
 *
 * @package FAWpmcp\Tests\Http
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Http;

use FAWpmcp\Http\ResponseFormatter;
use PHPUnit\Framework\TestCase;

/**
 * Test ResponseFormatter pure functions.
 *
 * Tests cover:
 * - Success response format with required fields
 * - Error response format with error details
 * - HTTP status code mapping
 * - Optional field handling (details, retry_after)
 * - Timestamp format validation
 */
class ResponseFormatterTest extends TestCase {

	// =========================================================================
	// Success Response Format Tests
	// =========================================================================

	/**
	 * Test success format includes all required fields.
	 *
	 * Verifies: 'success' => true, 'data', 'meta' with correlation_id,
	 * execution_time_ms, and timestamp.
	 *
	 * @return void
	 */
	public function test_success_format_includes_required_fields(): void {
		$response = ResponseFormatter::success(
			array( 'post_id' => 42 ),
			'correlation-123',
			150
		);

		$this->assertTrue( $response['success'] );
		$this->assertEquals( array( 'post_id' => 42 ), $response['data'] );
		$this->assertEquals( 'correlation-123', $response['meta']['correlation_id'] );
		$this->assertEquals( 150, $response['meta']['execution_time_ms'] );
		$this->assertArrayHasKey( 'timestamp', $response['meta'] );
	}

	/**
	 * Test success response has proper structure.
	 *
	 * Verifies the exact array structure matches the API contract.
	 *
	 * @return void
	 */
	public function test_success_response_has_proper_structure(): void {
		$response = ResponseFormatter::success(
			array( 'title' => 'Test Post' ),
			'corr-456',
			75
		);

		// Verify top-level keys.
		$this->assertArrayHasKey( 'success', $response );
		$this->assertArrayHasKey( 'data', $response );
		$this->assertArrayHasKey( 'meta', $response );

		// Verify meta keys.
		$this->assertArrayHasKey( 'correlation_id', $response['meta'] );
		$this->assertArrayHasKey( 'execution_time_ms', $response['meta'] );
		$this->assertArrayHasKey( 'timestamp', $response['meta'] );

		// Verify only expected keys exist (no extra keys).
		$this->assertCount( 3, $response );
		$this->assertCount( 3, $response['meta'] );
	}

	/**
	 * Test success timestamp is ISO 8601 format.
	 *
	 * Verifies timestamp uses gmdate('c') format (e.g., 2025-01-21T12:00:00+00:00).
	 *
	 * @return void
	 */
	public function test_success_timestamp_is_iso8601_format(): void {
		$response = ResponseFormatter::success(
			array(),
			'corr-789',
			100
		);

		$timestamp = $response['meta']['timestamp'];

		// ISO 8601 format should be parseable and match the pattern.
		$this->assertMatchesRegularExpression(
			'/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+00:00$/',
			$timestamp,
			'Timestamp should be ISO 8601 format'
		);

		// Should be parseable as a date.
		$parsed = \DateTime::createFromFormat( \DateTime::ATOM, $timestamp );
		$this->assertNotFalse( $parsed, 'Timestamp should be parseable as ATOM format' );
	}

	/**
	 * Test success with empty data array.
	 *
	 * Verifies empty data arrays are handled correctly.
	 *
	 * @return void
	 */
	public function test_success_with_empty_data(): void {
		$response = ResponseFormatter::success(
			array(),
			'empty-data-test',
			50
		);

		$this->assertTrue( $response['success'] );
		$this->assertEquals( array(), $response['data'] );
		$this->assertIsArray( $response['data'] );
	}

	/**
	 * Test success with complex nested data.
	 *
	 * Verifies nested data structures are preserved.
	 *
	 * @return void
	 */
	public function test_success_with_nested_data(): void {
		$complex_data = array(
			'post'     => array(
				'id'     => 42,
				'title'  => 'Test',
				'meta'   => array(
					'author' => 'John',
					'tags'   => array( 'php', 'wordpress' ),
				),
			),
			'comments' => array(
				array(
					'id'   => 1,
					'text' => 'Great post!',
				),
				array(
					'id'   => 2,
					'text' => 'Thanks!',
				),
			),
		);

		$response = ResponseFormatter::success(
			$complex_data,
			'nested-test',
			200
		);

		$this->assertEquals( $complex_data, $response['data'] );
		$this->assertEquals( 42, $response['data']['post']['id'] );
		$this->assertEquals( array( 'php', 'wordpress' ), $response['data']['post']['meta']['tags'] );
	}

	/**
	 * Test success preserves data types.
	 *
	 * Verifies integers, strings, booleans, nulls, and floats are preserved.
	 *
	 * @return void
	 */
	public function test_success_preserves_data_types(): void {
		$data = array(
			'integer' => 42,
			'float'   => 3.14,
			'string'  => 'hello',
			'boolean' => true,
			'null'    => null,
		);

		$response = ResponseFormatter::success( $data, 'types-test', 100 );

		$this->assertIsInt( $response['data']['integer'] );
		$this->assertIsFloat( $response['data']['float'] );
		$this->assertIsString( $response['data']['string'] );
		$this->assertIsBool( $response['data']['boolean'] );
		$this->assertNull( $response['data']['null'] );
	}

	// =========================================================================
	// Error Response Format Tests
	// =========================================================================

	/**
	 * Test error format includes error details.
	 *
	 * Verifies: 'success' => false, 'error' with code/message.
	 *
	 * @return void
	 */
	public function test_error_format_includes_error_details(): void {
		$response = ResponseFormatter::error(
			'validation_error',
			'Invalid input'
		);

		$this->assertFalse( $response['success'] );
		$this->assertEquals( 'validation_error', $response['error']['code'] );
		$this->assertEquals( 'Invalid input', $response['error']['message'] );
	}

	/**
	 * Test error with details array.
	 *
	 * Verifies optional 'details' field is included when provided.
	 *
	 * @return void
	 */
	public function test_error_with_details_array(): void {
		$details = array(
			'field' => 'title',
			'error' => 'Required',
		);

		$response = ResponseFormatter::error(
			'validation_error',
			'Invalid input',
			$details
		);

		$this->assertFalse( $response['success'] );
		$this->assertArrayHasKey( 'details', $response['error'] );
		$this->assertEquals( $details, $response['error']['details'] );
		$this->assertEquals( 'title', $response['error']['details']['field'] );
	}

	/**
	 * Test error with retry_after in meta.
	 *
	 * Verifies optional 'retry_after' is included in meta when provided.
	 *
	 * @return void
	 */
	public function test_error_with_retry_after(): void {
		$response = ResponseFormatter::error(
			'rate_limit_exceeded',
			'Too many requests',
			null,
			60
		);

		$this->assertFalse( $response['success'] );
		$this->assertArrayHasKey( 'meta', $response );
		$this->assertArrayHasKey( 'retry_after', $response['meta'] );
		$this->assertEquals( 60, $response['meta']['retry_after'] );
	}

	/**
	 * Test error without optional fields.
	 *
	 * Verifies optional fields are omitted when not provided.
	 *
	 * @return void
	 */
	public function test_error_without_optional_fields(): void {
		$response = ResponseFormatter::error(
			'internal_error',
			'Something went wrong'
		);

		$this->assertFalse( $response['success'] );
		$this->assertArrayNotHasKey( 'details', $response['error'] );

		// Meta should still have timestamp.
		$this->assertArrayHasKey( 'meta', $response );
		$this->assertArrayHasKey( 'timestamp', $response['meta'] );

		// But not retry_after if not provided.
		$this->assertArrayNotHasKey( 'retry_after', $response['meta'] );
	}

	/**
	 * Test error response has proper structure.
	 *
	 * Verifies the exact array structure for errors.
	 *
	 * @return void
	 */
	public function test_error_response_has_proper_structure(): void {
		$response = ResponseFormatter::error(
			'test_error',
			'Test message'
		);

		// Verify top-level keys.
		$this->assertArrayHasKey( 'success', $response );
		$this->assertArrayHasKey( 'error', $response );
		$this->assertArrayHasKey( 'meta', $response );

		// Verify error keys.
		$this->assertArrayHasKey( 'code', $response['error'] );
		$this->assertArrayHasKey( 'message', $response['error'] );
	}

	/**
	 * Test error timestamp is ISO 8601 format.
	 *
	 * @return void
	 */
	public function test_error_timestamp_is_iso8601_format(): void {
		$response = ResponseFormatter::error(
			'test_error',
			'Test message'
		);

		$timestamp = $response['meta']['timestamp'];

		$this->assertMatchesRegularExpression(
			'/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+00:00$/',
			$timestamp
		);
	}

	/**
	 * Test error with complex details array.
	 *
	 * @return void
	 */
	public function test_error_with_complex_details(): void {
		$details = array(
			'fields' => array(
				array(
					'name'   => 'title',
					'errors' => array( 'Required', 'Too short' ),
				),
				array(
					'name'   => 'content',
					'errors' => array( 'Required' ),
				),
			),
			'count'  => 2,
		);

		$response = ResponseFormatter::error(
			'validation_error',
			'Multiple validation errors',
			$details
		);

		$this->assertEquals( $details, $response['error']['details'] );
		$this->assertCount( 2, $response['error']['details']['fields'] );
	}

	// =========================================================================
	// HTTP Status Mapping Tests
	// =========================================================================

	/**
	 * Test error maps authentication_required to 401.
	 *
	 * @return void
	 */
	public function test_error_maps_authentication_required_to_401(): void {
		$status = ResponseFormatter::getHttpStatus( 'authentication_required' );

		$this->assertEquals( 401, $status );
	}

	/**
	 * Test error maps insufficient_permissions to 403.
	 *
	 * @return void
	 */
	public function test_error_maps_insufficient_permissions_to_403(): void {
		$status = ResponseFormatter::getHttpStatus( 'insufficient_permissions' );

		$this->assertEquals( 403, $status );
	}

	/**
	 * Test error maps rate_limit_exceeded to 429.
	 *
	 * @return void
	 */
	public function test_error_maps_rate_limit_exceeded_to_429(): void {
		$status = ResponseFormatter::getHttpStatus( 'rate_limit_exceeded' );

		$this->assertEquals( 429, $status );
	}

	/**
	 * Test error maps internal_error to 500.
	 *
	 * @return void
	 */
	public function test_error_maps_internal_error_to_500(): void {
		$status = ResponseFormatter::getHttpStatus( 'internal_error' );

		$this->assertEquals( 500, $status );
	}

	/**
	 * Test error maps validation_error to 400.
	 *
	 * @return void
	 */
	public function test_error_maps_validation_error_to_400(): void {
		$status = ResponseFormatter::getHttpStatus( 'validation_error' );

		$this->assertEquals( 400, $status );
	}

	/**
	 * Test error maps not_found to 404.
	 *
	 * @return void
	 */
	public function test_error_maps_not_found_to_404(): void {
		$status = ResponseFormatter::getHttpStatus( 'not_found' );

		$this->assertEquals( 404, $status );
	}

	/**
	 * Test unknown error code returns 500.
	 *
	 * Verifies default fallback for unmapped error codes.
	 *
	 * @return void
	 */
	public function test_unknown_error_code_returns_500(): void {
		$status = ResponseFormatter::getHttpStatus( 'some_unknown_error_code' );

		$this->assertEquals( 500, $status );
	}

	/**
	 * Test error maps to correct HTTP status (comprehensive).
	 *
	 * @return void
	 */
	public function test_error_maps_to_correct_http_status(): void {
		$mappings = array(
			'authentication_required'  => 401,
			'insufficient_permissions' => 403,
			'rate_limit_exceeded'      => 429,
			'internal_error'           => 500,
			'validation_error'         => 400,
			'not_found'                => 404,
			'method_not_allowed'       => 405,
			'conflict'                 => 409,
		);

		foreach ( $mappings as $code => $expected_status ) {
			$actual_status = ResponseFormatter::getHttpStatus( $code );
			$this->assertEquals(
				$expected_status,
				$actual_status,
				"Error code '$code' should map to HTTP status $expected_status"
			);
		}
	}

	// =========================================================================
	// Pure Function Behavior Tests
	// =========================================================================

	/**
	 * Test success is a pure function (same inputs produce same output).
	 *
	 * Note: Timestamp will differ slightly, so we test the deterministic parts.
	 *
	 * @return void
	 */
	public function test_success_is_deterministic_for_same_inputs(): void {
		$data           = array( 'test' => 'value' );
		$correlation_id = 'same-id';
		$execution_time = 100;

		$response1 = ResponseFormatter::success( $data, $correlation_id, $execution_time );
		$response2 = ResponseFormatter::success( $data, $correlation_id, $execution_time );

		// Deterministic parts should be identical.
		$this->assertEquals( $response1['success'], $response2['success'] );
		$this->assertEquals( $response1['data'], $response2['data'] );
		$this->assertEquals( $response1['meta']['correlation_id'], $response2['meta']['correlation_id'] );
		$this->assertEquals( $response1['meta']['execution_time_ms'], $response2['meta']['execution_time_ms'] );
	}

	/**
	 * Test error is a pure function.
	 *
	 * @return void
	 */
	public function test_error_is_deterministic_for_same_inputs(): void {
		$response1 = ResponseFormatter::error( 'test_error', 'message' );
		$response2 = ResponseFormatter::error( 'test_error', 'message' );

		$this->assertEquals( $response1['success'], $response2['success'] );
		$this->assertEquals( $response1['error'], $response2['error'] );
	}

	/**
	 * Test getHttpStatus is a pure function.
	 *
	 * @return void
	 */
	public function test_get_http_status_is_pure(): void {
		$status1 = ResponseFormatter::getHttpStatus( 'validation_error' );
		$status2 = ResponseFormatter::getHttpStatus( 'validation_error' );

		$this->assertSame( $status1, $status2 );
	}
}
