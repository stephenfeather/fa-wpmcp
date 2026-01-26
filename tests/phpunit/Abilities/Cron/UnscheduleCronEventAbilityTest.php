<?php
/**
 * Tests for UnscheduleCronEventAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Cron
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Cron;

use FAWpmcp\Abilities\Cron\UnscheduleCronEventAbility;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test UnscheduleCronEventAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Cron
 */
class UnscheduleCronEventAbilityTest extends TestCase {
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
		$ability = new UnscheduleCronEventAbility();
		$this->assertEquals( 'fa-wpmcp/unschedule-cron-event', $ability->getName() );
	}

	/**
	 * Test ability returns correct category.
	 *
	 * @return void
	 */
	public function testGetCategory(): void {
		$ability = new UnscheduleCronEventAbility();
		$this->assertEquals( 'cron', $ability->getCategory() );
	}

	/**
	 * Test ability returns correct label.
	 *
	 * @return void
	 */
	public function testGetLabel(): void {
		$ability = new UnscheduleCronEventAbility();
		$this->assertEquals( 'Unschedule Cron Event', $ability->getLabel() );
	}

	/**
	 * Test ability returns correct operation type.
	 *
	 * @return void
	 */
	public function testGetOperationType(): void {
		$ability = new UnscheduleCronEventAbility();
		$this->assertEquals( 'write', $ability->getOperationType() );
	}

	/**
	 * Test ability returns correct required capability.
	 *
	 * @return void
	 */
	public function testGetRequiredCapability(): void {
		$ability = new UnscheduleCronEventAbility();
		$this->assertEquals( 'manage_options', $ability->getRequiredCapability() );
	}

	/**
	 * Test ability returns input schema with required hook field.
	 *
	 * @return void
	 */
	public function testGetInputSchema(): void {
		$ability = new UnscheduleCronEventAbility();
		$schema  = $ability->getInputSchema();

		$this->assertIsArray( $schema );
		$this->assertArrayHasKey( 'type', $schema );
		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertArrayHasKey( 'required', $schema );
		$this->assertArrayHasKey( 'hook', $schema['properties'] );
		$this->assertArrayHasKey( 'timestamp', $schema['properties'] );
		$this->assertContains( 'hook', $schema['required'] );
	}

	/**
	 * Test ability returns output schema.
	 *
	 * @return void
	 */
	public function testGetOutputSchema(): void {
		$ability = new UnscheduleCronEventAbility();
		$schema  = $ability->getOutputSchema();

		$this->assertIsArray( $schema );
		$this->assertArrayHasKey( 'type', $schema );
		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertArrayHasKey( 'success', $schema['properties'] );
		$this->assertArrayHasKey( 'removed_count', $schema['properties'] );
	}

	/**
	 * Test execute unschedules specific event by hook and timestamp.
	 *
	 * @return void
	 */
	public function testExecuteUnschedulesSpecificEventByTimestamp(): void {
		$ability = new UnscheduleCronEventAbility();

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

		Functions\expect( 'wp_unschedule_event' )
			->once()
			->with( 1706200000, 'my_custom_hook', array() )
			->andReturn( true );

		$result = $ability->doExecute(
			array(
				'hook'      => 'my_custom_hook',
				'timestamp' => 1706200000,
			)
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );
		$this->assertEquals( 1, $result['removed_count'] );
	}

	/**
	 * Test execute clears all events for hook when no timestamp provided.
	 *
	 * @return void
	 */
	public function testExecuteClearsAllEventsForHookWhenNoTimestamp(): void {
		$ability = new UnscheduleCronEventAbility();

		Functions\expect( 'wp_clear_scheduled_hook' )
			->once()
			->with( 'my_custom_hook', array() )
			->andReturn( 3 );

		$result = $ability->doExecute( array( 'hook' => 'my_custom_hook' ) );

		$this->assertTrue( $result['success'] );
		$this->assertEquals( 3, $result['removed_count'] );
	}

	/**
	 * Test execute returns failure when wp_unschedule_event fails.
	 *
	 * @return void
	 */
	public function testExecuteReturnsFailureWhenUnscheduleFails(): void {
		$ability = new UnscheduleCronEventAbility();

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

		$wp_error = Mockery::mock( 'WP_Error' );
		$wp_error->shouldReceive( 'get_error_message' )->andReturn( 'Unschedule failed' );

		Functions\expect( 'wp_unschedule_event' )
			->once()
			->andReturn( $wp_error );

		Functions\when( 'is_wp_error' )->alias(
			function ( $thing ) use ( $wp_error ) {
				return $thing === $wp_error;
			}
		);

		$result = $ability->doExecute(
			array(
				'hook'      => 'my_custom_hook',
				'timestamp' => 1706200000,
			)
		);

		$this->assertFalse( $result['success'] );
		$this->assertArrayHasKey( 'error', $result );
	}

	/**
	 * Test execute returns zero removed when hook not found.
	 *
	 * @return void
	 */
	public function testExecuteReturnsZeroRemovedWhenHookNotFound(): void {
		$ability = new UnscheduleCronEventAbility();

		Functions\expect( 'wp_clear_scheduled_hook' )
			->once()
			->with( 'nonexistent_hook', array() )
			->andReturn( 0 );

		$result = $ability->doExecute( array( 'hook' => 'nonexistent_hook' ) );

		$this->assertTrue( $result['success'] );
		$this->assertEquals( 0, $result['removed_count'] );
	}

	/**
	 * Test annotations indicate destructive write operation.
	 *
	 * @return void
	 */
	public function testGetAnnotations(): void {
		$ability     = new UnscheduleCronEventAbility();
		$annotations = $ability->getAnnotations();

		$this->assertFalse( $annotations['readonly'] );
		$this->assertTrue( $annotations['destructive'] );
		$this->assertTrue( $annotations['idempotent'] );
	}

	/**
	 * Test execute returns failure when wp_clear_scheduled_hook fails.
	 *
	 * @return void
	 */
	public function testExecuteReturnsFailureWhenClearHookFails(): void {
		$ability = new UnscheduleCronEventAbility();

		$wp_error = Mockery::mock( 'WP_Error' );
		$wp_error->shouldReceive( 'get_error_message' )->andReturn( 'Clear hook failed' );

		Functions\expect( 'wp_clear_scheduled_hook' )
			->once()
			->andReturn( $wp_error );

		Functions\when( 'is_wp_error' )->alias(
			function ( $thing ) use ( $wp_error ) {
				return $thing === $wp_error;
			}
		);

		$result = $ability->doExecute( array( 'hook' => 'my_custom_hook' ) );

		$this->assertFalse( $result['success'] );
		$this->assertArrayHasKey( 'error', $result );
	}

	/**
	 * Test execute handles timestamp for event not found.
	 *
	 * @return void
	 */
	public function testExecuteHandlesTimestampForEventNotFound(): void {
		$ability = new UnscheduleCronEventAbility();

		$cron_array = array(
			1706200000 => array(
				'other_hook' => array(
					'40cd750bba9870f18aada2478b24840a' => array(
						'schedule' => 'hourly',
						'args'     => array(),
					),
				),
			),
		);

		Functions\when( '_get_cron_array' )->justReturn( $cron_array );

		$result = $ability->doExecute(
			array(
				'hook'      => 'my_custom_hook',
				'timestamp' => 1706200000,
			)
		);

		$this->assertTrue( $result['success'] );
		$this->assertEquals( 0, $result['removed_count'] );
	}
}
