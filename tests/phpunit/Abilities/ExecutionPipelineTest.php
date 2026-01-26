<?php
/**
 * Tests for ExecutionPipeline.
 *
 * @package FAWpmcp\Tests\Abilities
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities;

use FAWpmcp\Abilities\ExecutionPipeline;
use FAWpmcp\ValueObjects\Result;
use PHPUnit\Framework\TestCase;

/**
 * Test ExecutionPipeline composable pipeline.
 *
 * Tests the functional pipeline pattern where:
 * - Each step is a callable that returns a Result
 * - Steps execute in order
 * - Pipeline stops on first failure
 * - Data flows between steps via Result::value
 * - Pipeline is immutable (pipe() returns new instance)
 *
 * @package FAWpmcp\Tests\Abilities
 */
class ExecutionPipelineTest extends TestCase {
	/**
	 * Test pipeline executes steps in correct order.
	 *
	 * @return void
	 */
	public function test_pipeline_executes_steps_in_order(): void {
		$execution_order = array();

		$pipeline = ExecutionPipeline::create()
			->pipe(
				function ( $input ) use ( &$execution_order ) {
					$execution_order[] = 'step1';
					return Result::success( $input );
				}
			)
			->pipe(
				function ( $input ) use ( &$execution_order ) {
					$execution_order[] = 'step2';
					return Result::success( $input );
				}
			)
			->pipe(
				function ( $input ) use ( &$execution_order ) {
					$execution_order[] = 'step3';
					return Result::success( $input );
				}
			);

		$result = $pipeline->execute( array( 'test' => 'data' ) );

		$this->assertEquals( array( 'step1', 'step2', 'step3' ), $execution_order );
		$this->assertTrue( $result->is_success );
	}

	/**
	 * Test pipeline stops execution on first failure.
	 *
	 * When a step returns Result::failure(), subsequent steps should not execute.
	 *
	 * @return void
	 */
	public function test_pipeline_stops_on_failure(): void {
		$execution_order = array();

		$pipeline = ExecutionPipeline::create()
			->pipe(
				function ( $input ) use ( &$execution_order ) {
					$execution_order[] = 'step1';
					return Result::success( $input );
				}
			)
			->pipe(
				function ( $input ) use ( &$execution_order ) {
					$execution_order[] = 'step2';
					return Result::failure( 'step2_error', 'Step 2 failed' );
				}
			)
			->pipe(
				function ( $input ) use ( &$execution_order ) {
					$execution_order[] = 'step3';
					return Result::success( $input );
				}
			);

		$result = $pipeline->execute( array( 'test' => 'data' ) );

		$this->assertEquals( array( 'step1', 'step2' ), $execution_order );
		$this->assertFalse( $result->is_success );
		$this->assertEquals( 'step2_error', $result->error_code );
		$this->assertEquals( 'Step 2 failed', $result->error_message );
	}

	/**
	 * Test pipeline passes transformed data between steps.
	 *
	 * Each step receives the Result::value from the previous step.
	 *
	 * @return void
	 */
	public function test_pipeline_passes_transformed_data(): void {
		$pipeline = ExecutionPipeline::create()
			->pipe(
				fn( $input ) => Result::success( array_merge( $input, array( 'added1' => 'value1' ) ) )
			)
			->pipe(
				fn( $input ) => Result::success( array_merge( $input, array( 'added2' => 'value2' ) ) )
			);

		$result = $pipeline->execute( array( 'original' => 'data' ) );

		$this->assertTrue( $result->is_success );
		$this->assertEquals( 'data', $result->value['original'] );
		$this->assertEquals( 'value1', $result->value['added1'] );
		$this->assertEquals( 'value2', $result->value['added2'] );
	}

	/**
	 * Test pipeline is immutable (pipe returns new instance).
	 *
	 * Calling pipe() should not modify the original pipeline.
	 *
	 * @return void
	 */
	public function test_pipeline_immutability(): void {
		$step1_calls = 0;
		$step2_calls = 0;

		$pipeline1 = ExecutionPipeline::create()
			->pipe(
				function ( $input ) use ( &$step1_calls ) {
					++$step1_calls;
					return Result::success( $input );
				}
			);

		// Create new pipeline with additional step.
		$pipeline2 = $pipeline1->pipe(
			function ( $input ) use ( &$step2_calls ) {
				++$step2_calls;
				return Result::success( $input );
			}
		);

		// Execute original pipeline (should only run step1).
		$pipeline1->execute( array( 'test' => 'data' ) );
		$this->assertEquals( 1, $step1_calls );
		$this->assertEquals( 0, $step2_calls );

		// Reset counters.
		$step1_calls = 0;
		$step2_calls = 0;

		// Execute extended pipeline (should run both steps).
		$pipeline2->execute( array( 'test' => 'data' ) );
		$this->assertEquals( 1, $step1_calls );
		$this->assertEquals( 1, $step2_calls );
	}

	/**
	 * Test empty pipeline returns success with original input.
	 *
	 * @return void
	 */
	public function test_empty_pipeline_returns_success(): void {
		$pipeline = ExecutionPipeline::create();

		$result = $pipeline->execute( array( 'original' => 'data' ) );

		$this->assertTrue( $result->is_success );
		$this->assertEquals( array( 'original' => 'data' ), $result->value );
	}

	/**
	 * Test pipeline with single step.
	 *
	 * @return void
	 */
	public function test_single_step_pipeline(): void {
		$pipeline = ExecutionPipeline::create()
			->pipe( fn( $input ) => Result::success( array( 'transformed' => true ) ) );

		$result = $pipeline->execute( array( 'original' => 'data' ) );

		$this->assertTrue( $result->is_success );
		$this->assertEquals( array( 'transformed' => true ), $result->value );
	}

	/**
	 * Test pipeline returns first failure encountered.
	 *
	 * @return void
	 */
	public function test_pipeline_returns_first_failure(): void {
		$pipeline = ExecutionPipeline::create()
			->pipe( fn( $input ) => Result::failure( 'first_error', 'First failure' ) )
			->pipe( fn( $input ) => Result::failure( 'second_error', 'Second failure' ) );

		$result = $pipeline->execute( array( 'test' => 'data' ) );

		$this->assertFalse( $result->is_success );
		$this->assertEquals( 'first_error', $result->error_code );
		$this->assertEquals( 'First failure', $result->error_message );
	}

	/**
	 * Test pipeline handles null input.
	 *
	 * @return void
	 */
	public function test_pipeline_handles_null_input(): void {
		$received_input = 'not_null';

		$pipeline = ExecutionPipeline::create()
			->pipe(
				function ( $input ) use ( &$received_input ) {
					$received_input = $input;
					return Result::success( array( 'processed' => true ) );
				}
			);

		$result = $pipeline->execute( null );

		$this->assertTrue( $result->is_success );
		$this->assertNull( $received_input );
	}

	/**
	 * Test pipeline preserves Result value type.
	 *
	 * @return void
	 */
	public function test_pipeline_preserves_value_type(): void {
		$pipeline = ExecutionPipeline::create()
			->pipe( fn( $input ) => Result::success( 42 ) )
			->pipe( fn( $input ) => Result::success( $input * 2 ) );

		$result = $pipeline->execute( 0 );

		$this->assertTrue( $result->is_success );
		$this->assertSame( 84, $result->value );
	}

	/**
	 * Test pipeline with many steps.
	 *
	 * @return void
	 */
	public function test_pipeline_with_many_steps(): void {
		$pipeline = ExecutionPipeline::create();

		for ( $i = 0; $i < 10; $i++ ) {
			$step     = $i;
			$pipeline = $pipeline->pipe(
				fn( $input ) => Result::success( $input + 1 )
			);
		}

		$result = $pipeline->execute( 0 );

		$this->assertTrue( $result->is_success );
		$this->assertEquals( 10, $result->value );
	}
}
