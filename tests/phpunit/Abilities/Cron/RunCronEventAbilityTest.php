<?php
/**
 * Tests for RunCronEventAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Cron
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Cron;

use FAWpmcp\Abilities\Cron\RunCronEventAbility;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Brain\Monkey\Actions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test RunCronEventAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Cron
 */
class RunCronEventAbilityTest extends TestCase {
	/**
	 * Set up Brain\Monkey before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	/**
	 * Tear down Brain\Monkey after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * Test ability returns correct name.
	 *
	 * @return void
	 */
	public function testGetName(): void {
		$ability = new RunCronEventAbility();
		$this->assertEquals( 'fa-wpmcp/run-cron-event', $ability->getName() );
	}

	/**
	 * Test ability returns correct category.
	 *
	 * @return void
	 */
	public function testGetCategory(): void {
		$ability = new RunCronEventAbility();
		$this->assertEquals( 'cron', $ability->getCategory() );
	}

	/**
	 * Test ability returns correct label.
	 *
	 * @return void
	 */
	public function testGetLabel(): void {
		$ability = new RunCronEventAbility();
		$this->assertEquals( 'Run Cron Event', $ability->getLabel() );
	}

	/**
	 * Test ability returns correct operation type.
	 *
	 * @return void
	 */
	public function testGetOperationType(): void {
		$ability = new RunCronEventAbility();
		$this->assertEquals( 'write', $ability->getOperationType() );
	}

	/**
	 * Test ability returns correct required capability.
	 *
	 * @return void
	 */
	public function testGetRequiredCapability(): void {
		$ability = new RunCronEventAbility();
		$this->assertEquals( 'manage_options', $ability->getRequiredCapability() );
	}

	/**
	 * Test ability returns input schema with required hook field.
	 *
	 * @return void
	 */
	public function testGetInputSchema(): void {
		$ability = new RunCronEventAbility();
		$schema  = $ability->getInputSchema();

		$this->assertIsArray( $schema );
		$this->assertArrayHasKey( 'type', $schema );
		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertArrayHasKey( 'required', $schema );
		$this->assertArrayHasKey( 'hook', $schema['properties'] );
		$this->assertContains( 'hook', $schema['required'] );
	}

	/**
	 * Test ability returns output schema.
	 *
	 * @return void
	 */
	public function testGetOutputSchema(): void {
		$ability = new RunCronEventAbility();
		$schema  = $ability->getOutputSchema();

		$this->assertIsArray( $schema );
		$this->assertArrayHasKey( 'type', $schema );
		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertArrayHasKey( 'success', $schema['properties'] );
		$this->assertArrayHasKey( 'executed', $schema['properties'] );
	}

	/**
	 * Test execute runs cron event by triggering hook.
	 *
	 * @return void
	 */
	public function testExecuteRunsCronEventByTriggeringHook(): void {
		$ability = new RunCronEventAbility();

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
		$ability = new RunCronEventAbility();

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
		$ability = new RunCronEventAbility();

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
		$ability = new RunCronEventAbility();

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
		$ability = new RunCronEventAbility();

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
