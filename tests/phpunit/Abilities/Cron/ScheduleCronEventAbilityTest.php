<?php

/**
 * Tests for ScheduleCronEventAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Cron
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Cron;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Cron\ScheduleCronEventAbility;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test ScheduleCronEventAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Cron
 */
final class ScheduleCronEventAbilityTest extends BrainMonkeyTestCase {

	use AbilityTestTrait;

	/**
	 * Get the ability instance for testing.
	 *
	 * @return AbstractAbility
	 */
	protected function getAbilityInstance(): AbstractAbility {
		return new ScheduleCronEventAbility();
	}

	/**
	 * Get expected metadata for the ability.
	 *
	 * @return array<string, mixed>
	 */
	protected function getExpectedMetadata(): array {
		return [
			'name'                 => 'fa-wpmcp/schedule-cron-event',
			'category'             => 'cron',
			'label'                => 'Schedule Cron Event',
			'description_contains' => 'cron',
			'operation_type'       => 'write',
			'required_capability'  => 'manage_options',
		];
	}

	/**
	 * Test execute schedules recurring cron event successfully.
	 *
	 * @return void
	 */
	public function testExecuteSchedulesRecurringEventSuccessfully(): void {
		$ability = $this->getAbilityInstance();

		Functions\expect( 'wp_schedule_event' )
			->once()
			->with( 1706200000, 'hourly', 'my_custom_hook', array() )
			->andReturn( true );

		$result = $ability->doExecute(
			array(
				'hook'       => 'my_custom_hook',
				'timestamp'  => 1706200000,
				'recurrence' => 'hourly',
			)
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );
	}

	/**
	 * Test execute schedules single event when no recurrence provided.
	 *
	 * @return void
	 */
	public function testExecuteSchedulesSingleEventWhenNoRecurrence(): void {
		$ability = $this->getAbilityInstance();

		Functions\expect( 'wp_schedule_single_event' )
			->once()
			->with( 1706200000, 'my_single_event', array() )
			->andReturn( true );

		$result = $ability->doExecute(
			array(
				'hook'      => 'my_single_event',
				'timestamp' => 1706200000,
			)
		);

		$this->assertTrue( $result['success'] );
	}

	/**
	 * Test execute returns failure when wp_schedule_event fails.
	 *
	 * @return void
	 */
	public function testExecuteReturnsFailureWhenScheduleFails(): void {
		$ability = $this->getAbilityInstance();

		$wp_error = Mockery::mock( 'WP_Error' );
		$wp_error->shouldReceive( 'get_error_message' )->andReturn( 'Schedule failed' );

		Functions\expect( 'wp_schedule_event' )
			->once()
			->andReturn( $wp_error );

		Functions\when( 'is_wp_error' )->alias(
			function ( $thing ) use ( $wp_error ) {
				return $thing === $wp_error;
			}
		);

		$result = $ability->doExecute(
			array(
				'hook'       => 'my_custom_hook',
				'timestamp'  => 1706200000,
				'recurrence' => 'hourly',
			)
		);

		$this->assertFalse( $result['success'] );
		$this->assertArrayHasKey( 'error', $result );
	}

	/**
	 * Test execute passes args to wp_schedule_event.
	 *
	 * @return void
	 */
	public function testExecutePassesArgsToScheduleEvent(): void {
		$ability = $this->getAbilityInstance();

		$args = array( 'param1', 'param2' );

		Functions\expect( 'wp_schedule_event' )
			->once()
			->with( 1706200000, 'daily', 'my_custom_hook', $args )
			->andReturn( true );

		$result = $ability->doExecute(
			array(
				'hook'       => 'my_custom_hook',
				'timestamp'  => 1706200000,
				'recurrence' => 'daily',
				'args'       => $args,
			)
		);

		$this->assertTrue( $result['success'] );
	}

	/**
	 * Test annotations indicate write operation.
	 *
	 * @return void
	 */
	public function testGetAnnotations(): void {
		$ability     = new ScheduleCronEventAbility();
		$annotations = $ability->getAnnotations();

		$this->assertFalse( $annotations['readonly'] );
		$this->assertFalse( $annotations['destructive'] );
		$this->assertTrue( $annotations['idempotent'] );
	}

	/**
	 * Test execute returns failure when wp_schedule_single_event fails.
	 *
	 * @return void
	 */
	public function testExecuteReturnsFailureWhenSingleScheduleFails(): void {
		$ability = $this->getAbilityInstance();

		$wp_error = Mockery::mock( 'WP_Error' );
		$wp_error->shouldReceive( 'get_error_message' )->andReturn( 'Single event schedule failed' );

		Functions\expect( 'wp_schedule_single_event' )
			->once()
			->andReturn( $wp_error );

		Functions\when( 'is_wp_error' )->alias(
			function ( $thing ) use ( $wp_error ) {
				return $thing === $wp_error;
			}
		);

		$result = $ability->doExecute(
			array(
				'hook'      => 'my_single_event',
				'timestamp' => 1706200000,
			)
		);

		$this->assertFalse( $result['success'] );
		$this->assertArrayHasKey( 'error', $result );
	}
}
