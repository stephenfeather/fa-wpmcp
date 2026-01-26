<?php

/**
 * Tests for RunCronEventAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Cron
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Cron;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Cron\RunCronEventAbility;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;
use Brain\Monkey\Actions;

/**
 * Test RunCronEventAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Cron
 */
final class RunCronEventAbilityTest extends BrainMonkeyTestCase {

	use AbilityTestTrait;

	/**
	 * Get the ability instance for testing.
	 *
	 * @return AbstractAbility
	 */
	protected function getAbilityInstance(): AbstractAbility {
		return new RunCronEventAbility();
	}

	/**
	 * Get expected metadata for the ability.
	 *
	 * @return array<string, mixed>
	 */
	protected function getExpectedMetadata(): array {
		return [
			'name'                 => 'fa-wpmcp/run-cron-event',
			'category'             => 'cron',
			'label'                => 'Run Cron Event',
			'description_contains' => 'cron',
			'operation_type'       => 'write',
			'required_capability'  => 'manage_options',
		];
	}

	/**
	 * Test execute runs cron event by triggering hook.
	 *
	 * @return void
	 */
	public function testExecuteRunsCronEventByTriggeringHook(): void {
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
		Functions\when( 'has_action' )->justReturn( true );

		Actions\expectDone( 'my_custom_hook' )->once();

		$result = $ability->doExecute( array( 'hook' => 'my_custom_hook' ) );

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );
		$this->assertTrue( $result['executed'] );
	}

	/**
	 * Test execute returns failure when hook not scheduled.
	 *
	 * @return void
	 */
	public function testExecuteReturnsFailureWhenHookNotScheduled(): void {
		$ability = $this->getAbilityInstance();

		Functions\when( '_get_cron_array' )->justReturn( array() );

		$result = $ability->doExecute( array( 'hook' => 'nonexistent_hook' ) );

		$this->assertFalse( $result['success'] );
		$this->assertFalse( $result['executed'] );
		$this->assertArrayHasKey( 'error', $result );
	}

	/**
	 * Test execute runs hook with correct args.
	 *
	 * @return void
	 */
	public function testExecuteRunsHookWithCorrectArgs(): void {
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
		Functions\when( 'has_action' )->justReturn( true );

		Actions\expectDone( 'my_custom_hook' )
			->once()
			->with( 'param1', 'param2' );

		$result = $ability->doExecute( array( 'hook' => 'my_custom_hook' ) );

		$this->assertTrue( $result['success'] );
	}

	/**
	 * Test execute runs the first scheduled instance when multiple exist.
	 *
	 * @return void
	 */
	public function testExecuteRunsFirstScheduledInstanceWhenMultipleExist(): void {
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
		Functions\when( 'has_action' )->justReturn( true );

		// Should run with 'first' arg from earliest timestamp.
		Actions\expectDone( 'my_custom_hook' )
			->once()
			->with( 'first' );

		$result = $ability->doExecute( array( 'hook' => 'my_custom_hook' ) );

		$this->assertTrue( $result['success'] );
	}

	/**
	 * Test annotations indicate write operation but not destructive.
	 *
	 * @return void
	 */
	public function testGetAnnotations(): void {
		$ability     = new RunCronEventAbility();
		$annotations = $ability->getAnnotations();

		$this->assertFalse( $annotations['readonly'] );
		$this->assertFalse( $annotations['destructive'] );
		$this->assertFalse( $annotations['idempotent'] );
	}

	/**
	 * Test execute returns success even when no action handlers are attached.
	 *
	 * @return void
	 */
	public function testExecuteReturnsSuccessWhenNoActionHandlersAttached(): void {
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
		Functions\when( 'has_action' )->justReturn( false );

		Actions\expectDone( 'my_custom_hook' )->once();

		$result = $ability->doExecute( array( 'hook' => 'my_custom_hook' ) );

		// Still succeeds - do_action was called even if no handlers.
		$this->assertTrue( $result['success'] );
		$this->assertTrue( $result['executed'] );
	}
}
