<?php
/**
 * Test Result value object.
 *
 * @package FAWpmcp\Tests\ValueObjects
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\ValueObjects;

use FAWpmcp\ValueObjects\Result;
use FAWpmcp\Http\ResponseFormatter;
use FAWpmcp\Http\ErrorCodes;
use PHPUnit\Framework\TestCase;

/**
 * Test Result value object.
 */
class ResultTest extends TestCase {

	/**
	 * Test creating a success result.
	 */
	public function test_success_creates_successful_result(): void {
		$result = Result::success( 'test_value' );

		$this->assertTrue( $result->is_success );
		$this->assertSame( 'test_value', $result->value );
		$this->assertNull( $result->error_code );
		$this->assertNull( $result->error_message );
	}

	/**
	 * Test creating a failure result.
	 */
	public function test_failure_creates_failed_result(): void {
		$result = Result::failure( 'ERROR_CODE', 'Error message' );

		$this->assertFalse( $result->is_success );
		$this->assertNull( $result->value );
		$this->assertSame( 'ERROR_CODE', $result->error_code );
		$this->assertSame( 'Error message', $result->error_message );
	}

	/**
	 * Test map on success result.
	 */
	public function test_map_transforms_success_value(): void {
		$result = Result::success( 5 );

		$mapped = $result->map( fn( $x ) => $x * 2 );

		$this->assertTrue( $mapped->is_success );
		$this->assertSame( 10, $mapped->value );
	}

	/**
	 * Test map on failure result.
	 */
	public function test_map_preserves_failure(): void {
		$result = Result::failure( 'ERROR', 'Failed' );

		$mapped = $result->map( fn( $x ) => $x * 2 );

		$this->assertFalse( $mapped->is_success );
		$this->assertSame( 'ERROR', $mapped->error_code );
	}

	/**
	 * Test flatMap on success result.
	 */
	public function test_flat_map_chains_success(): void {
		$result = Result::success( 5 );

		$flat_mapped = $result->flatMap( fn( $x ) => Result::success( $x * 2 ) );

		$this->assertTrue( $flat_mapped->is_success );
		$this->assertSame( 10, $flat_mapped->value );
	}

	/**
	 * Test flatMap on failure result.
	 */
	public function test_flat_map_preserves_failure(): void {
		$result = Result::failure( 'ERROR', 'Failed' );

		$flat_mapped = $result->flatMap( fn( $x ) => Result::success( $x * 2 ) );

		$this->assertFalse( $flat_mapped->is_success );
		$this->assertSame( 'ERROR', $flat_mapped->error_code );
	}

	/**
	 * Test flatMap with chained failure.
	 */
	public function test_flat_map_chains_failure(): void {
		$result = Result::success( 5 );

		$flat_mapped = $result->flatMap( fn( $x ) => Result::failure( 'CHAINED_ERROR', 'Failed in chain' ) );

		$this->assertFalse( $flat_mapped->is_success );
		$this->assertSame( 'CHAINED_ERROR', $flat_mapped->error_code );
	}

	/**
	 * Test Result is readonly (immutable).
	 */
	public function test_result_is_immutable(): void {
		$result = Result::success( 'original' );

		$this->expectException( \Error::class );
		// @phpstan-ignore-next-line - Intentionally testing immutability.
		$result->value = 'modified';
	}

	/**
	 * Test success result toResponse format.
	 */
	public function test_success_result_to_response_format(): void {
		$result = Result::success(
			array(
				'post_id' => 42,
				'title'   => 'Test Post',
			)
		);

		$response = $result->toResponse( 'corr-123', 150 );

		$this->assertTrue( $response['success'] );
		$this->assertArrayHasKey( 'data', $response );
		$this->assertSame( 42, $response['data']['post_id'] );
		$this->assertSame( 'Test Post', $response['data']['title'] );
	}

	/**
	 * Test failure result toResponse format.
	 */
	public function test_failure_result_to_response_format(): void {
		$result = Result::failure( ErrorCodes::NOT_FOUND, 'Post not found' );

		$response = $result->toResponse( 'corr-456', 50 );

		$this->assertFalse( $response['success'] );
		$this->assertArrayHasKey( 'error', $response );
		$this->assertSame( ErrorCodes::NOT_FOUND, $response['error']['code'] );
		$this->assertSame( 'Post not found', $response['error']['message'] );
	}

	/**
	 * Test response includes correlation_id in meta.
	 */
	public function test_response_includes_correlation_id(): void {
		$result = Result::success( array( 'status' => 'ok' ) );

		$response = $result->toResponse( 'unique-corr-id-789', 100 );

		$this->assertArrayHasKey( 'meta', $response );
		$this->assertSame( 'unique-corr-id-789', $response['meta']['correlation_id'] );
	}

	/**
	 * Test response includes execution_time_ms in meta.
	 */
	public function test_response_includes_execution_time(): void {
		$result = Result::success( array( 'data' => 'test' ) );

		$response = $result->toResponse( 'corr-time-test', 250 );

		$this->assertArrayHasKey( 'meta', $response );
		$this->assertSame( 250, $response['meta']['execution_time_ms'] );
	}
}
