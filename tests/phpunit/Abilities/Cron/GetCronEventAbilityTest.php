<?php

/**
 * Tests for GetCronEventAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Cron
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Cron;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Cron\GetCronEventAbility;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;

/**
 * Test GetCronEventAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Cron
 */
final class GetCronEventAbilityTest extends BrainMonkeyTestCase {

	use AbilityTestTrait;

	/**
	 * Get the ability instance for testing.
	 *
	 * @return AbstractAbility
	 */
	protected function getAbilityInstance(): AbstractAbility {
		return new GetCronEventAbility();
	}

	/**
	 * Get expected metadata for the ability.
	 *
	 * @return array<string, mixed>
	 */
	protected function getExpectedMetadata(): array {
		return [
			'name'                 => 'fa-wpmcp/get-cron-event',
			'category'             => 'cron',
			'label'                => 'Get Cron Event',
			'description_contains' => 'cron',
			'operation_type'       => 'read',
			'required_capability'  => 'manage_options',
		];
	}

	/**
	 * Test execute returns cron event when it exists.
	 *
	 * @return void
	 */
	public function testExecuteReturnsCronEventWhenExists(): void {
		$ability = $this->getAbilityInstance();

		$cron_array = array(
			1706200000 => array(
				'my_custom_hook' => array(
					'40cd750bba9870f18aada2478b24840a' => array(
						'schedule' => 'hourly',
						'args'     => array(),
					),
				),
			),
		);

		Functions\when( '_get_cron_array' )->justReturn( $cron_array );

		$result = $ability->doExecute( array( 'hook' => 'my_custom_hook' ) );

		$this->assertIsArray( $result );
		$this->assertTrue( $result['found'] );
		$this->assertCount( 1, $result['events'] );
		$this->assertEquals( 'my_custom_hook', $result['events'][0]['hook'] );
	}

	/**
	 * Test execute returns found false when event does not exist.
	 *
	 * @return void
	 */
	public function testExecuteReturnsFoundFalseWhenEventNotFound(): void {
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
		);

		Functions\when( '_get_cron_array' )->justReturn( $cron_array );

		$result = $ability->doExecute( array( 'hook' => 'nonexistent_hook' ) );

		$this->assertIsArray( $result );
		$this->assertFalse( $result['found'] );
		$this->assertEmpty( $result['events'] );
	}

	/**
	 * Test execute returns multiple instances of the same hook.
	 *
	 * @return void
	 */
	public function testExecuteReturnsMultipleInstancesOfSameHook(): void {
		$ability = $this->getAbilityInstance();

		$cron_array = array(
			1706200000 => array(
				'my_custom_hook' => array(
					'40cd750bba9870f18aada2478b24840a' => array(
						'schedule' => 'hourly',
						'args'     => array( 'first' ),
					),
				),
			),
			1706203600 => array(
				'my_custom_hook' => array(
					'9a0364b9e99bb480dd25e1f0284c8555' => array(
						'schedule' => 'hourly',
						'args'     => array( 'second' ),
					),
				),
			),
		);

		Functions\when( '_get_cron_array' )->justReturn( $cron_array );

		$result = $ability->doExecute( array( 'hook' => 'my_custom_hook' ) );

		$this->assertTrue( $result['found'] );
		$this->assertCount( 2, $result['events'] );
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

		$result = $ability->doExecute( array( 'hook' => 'my_custom_hook' ) );

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
		$ability     = new GetCronEventAbility();
		$annotations = $ability->getAnnotations();

		$this->assertTrue( $annotations['readonly'] );
		$this->assertFalse( $annotations['destructive'] );
		$this->assertTrue( $annotations['idempotent'] );
	}

	/**
	 * Test execute handles empty cron array.
	 *
	 * @return void
	 */
	public function testExecuteHandlesEmptyCronArray(): void {
		$ability = $this->getAbilityInstance();

		Functions\when( '_get_cron_array' )->justReturn( array() );

		$result = $ability->doExecute( array( 'hook' => 'any_hook' ) );

		$this->assertFalse( $result['found'] );
		$this->assertEmpty( $result['events'] );
	}
}
