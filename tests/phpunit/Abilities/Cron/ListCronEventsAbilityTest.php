<?php

/**
 * Tests for ListCronEventsAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Cron
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Cron;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Cron\ListCronEventsAbility;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;

/**
 * Test ListCronEventsAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Cron
 */
final class ListCronEventsAbilityTest extends BrainMonkeyTestCase {

	use AbilityTestTrait;

	/**
	 * Get the ability instance for testing.
	 *
	 * @return AbstractAbility
	 */
	protected function getAbilityInstance(): AbstractAbility {
		return new ListCronEventsAbility();
	}

	/**
	 * Get expected metadata for the ability.
	 *
	 * @return array<string, mixed>
	 */
	protected function getExpectedMetadata(): array {
		return [
			'name'                 => 'fa-wpmcp/list-cron-events',
			'category'             => 'cron',
			'label'                => 'List Cron Events',
			'description_contains' => 'cron',
			'operation_type'       => 'read',
			'required_capability'  => 'manage_options',
		];
	}

	/**
	 * Test execute returns list of cron events.
	 *
	 * @return void
	 */
	public function testExecuteReturnsListOfCronEvents(): void {
		$ability = $this->getAbilityInstance();

		$cron_array = array(
			1706200000 => array(
				'wp_scheduled_delete' => array(
					'40cd750bba9870f18aada2478b24840a' => array(
						'schedule' => 'daily',
						'args'     => array(),
					),
				),
			),
			1706210000 => array(
				'wp_update_plugins' => array(
					'40cd750bba9870f18aada2478b24840a' => array(
						'schedule' => 'twicedaily',
						'args'     => array(),
					),
				),
			),
		);

		Functions\when( '_get_cron_array' )->justReturn( $cron_array );

		$result = $ability->doExecute( array() );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'events', $result );
		$this->assertArrayHasKey( 'total', $result );
		$this->assertCount( 2, $result['events'] );
		$this->assertEquals( 2, $result['total'] );
	}

	/**
	 * Test execute filters events by hook name pattern.
	 *
	 * @return void
	 */
	public function testExecuteFiltersEventsByHookPattern(): void {
		$ability = $this->getAbilityInstance();

		$cron_array = array(
			1706200000 => array(
				'wp_scheduled_delete' => array(
					'40cd750bba9870f18aada2478b24840a' => array(
						'schedule' => 'daily',
						'args'     => array(),
					),
				),
			),
			1706210000 => array(
				'wp_update_plugins' => array(
					'40cd750bba9870f18aada2478b24840a' => array(
						'schedule' => 'twicedaily',
						'args'     => array(),
					),
				),
			),
		);

		Functions\when( '_get_cron_array' )->justReturn( $cron_array );

		$result = $ability->doExecute( array( 'hook' => 'wp_scheduled' ) );

		$this->assertCount( 1, $result['events'] );
		$this->assertEquals( 'wp_scheduled_delete', $result['events'][0]['hook'] );
	}

	/**
	 * Test execute returns empty array when no cron events exist.
	 *
	 * @return void
	 */
	public function testExecuteReturnsEmptyArrayWhenNoCronEvents(): void {
		$ability = $this->getAbilityInstance();

		Functions\when( '_get_cron_array' )->justReturn( array() );

		$result = $ability->doExecute( array() );

		$this->assertIsArray( $result['events'] );
		$this->assertEmpty( $result['events'] );
		$this->assertEquals( 0, $result['total'] );
	}

	/**
	 * Test execute returns event details with correct structure.
	 *
	 * @return void
	 */
	public function testExecuteReturnsEventDetailsWithCorrectStructure(): void {
		$ability = $this->getAbilityInstance();

		$cron_array = array(
			1706200000 => array(
				'my_custom_hook' => array(
					'40cd750bba9870f18aada2478b24840a' => array(
						'schedule' => 'hourly',
						'args'     => array( 'param1', 'param2' ),
					),
				),
			),
		);

		Functions\when( '_get_cron_array' )->justReturn( $cron_array );

		$result = $ability->doExecute( array() );

		$this->assertArrayHasKey( 'hook', $result['events'][0] );
		$this->assertArrayHasKey( 'timestamp', $result['events'][0] );
		$this->assertArrayHasKey( 'schedule', $result['events'][0] );
		$this->assertArrayHasKey( 'args', $result['events'][0] );
		$this->assertEquals( 'my_custom_hook', $result['events'][0]['hook'] );
		$this->assertEquals( 1706200000, $result['events'][0]['timestamp'] );
		$this->assertEquals( 'hourly', $result['events'][0]['schedule'] );
		$this->assertEquals( array( 'param1', 'param2' ), $result['events'][0]['args'] );
	}

	/**
	 * Test annotations are correct for read-only ability.
	 *
	 * @return void
	 */
	public function testGetAnnotations(): void {
		$ability     = new ListCronEventsAbility();
		$annotations = $ability->getAnnotations();

		$this->assertTrue( $annotations['readonly'] );
		$this->assertFalse( $annotations['destructive'] );
		$this->assertTrue( $annotations['idempotent'] );
	}

	/**
	 * Test execute handles null cron array.
	 *
	 * @return void
	 */
	public function testExecuteHandlesNullCronArray(): void {
		$ability = $this->getAbilityInstance();

		Functions\when( '_get_cron_array' )->justReturn( false );

		$result = $ability->doExecute( array() );

		$this->assertIsArray( $result['events'] );
		$this->assertEmpty( $result['events'] );
		$this->assertEquals( 0, $result['total'] );
	}
}
