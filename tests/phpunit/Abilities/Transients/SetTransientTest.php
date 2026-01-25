<?php
/**
 * Tests for SetTransient ability.
 *
 * @package FAWpmcp\Tests\Abilities\Transients
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Transients;

use FAWpmcp\Abilities\Transients\SetTransient;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test SetTransient ability functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Transients
 */
class SetTransientTest extends TestCase {
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
		$ability = new SetTransient();
		$this->assertEquals( 'fa-wpmcp/set-transient', $ability->getName() );
	}

	/**
	 * Test ability returns correct category.
	 *
	 * @return void
	 */
	public function testGetCategory(): void {
		$ability = new SetTransient();
		$this->assertEquals( 'transients', $ability->getCategory() );
	}

	/**
	 * Test ability returns correct label.
	 *
	 * @return void
	 */
	public function testGetLabel(): void {
		$ability = new SetTransient();
		$this->assertEquals( 'Set Transient', $ability->getLabel() );
	}

	/**
	 * Test ability returns correct operation type.
	 *
	 * @return void
	 */
	public function testGetOperationType(): void {
		$ability = new SetTransient();
		$this->assertEquals( 'create', $ability->getOperationType() );
	}

	/**
	 * Test ability returns correct required capability.
	 *
	 * @return void
	 */
	public function testGetRequiredCapability(): void {
		$ability = new SetTransient();
		$this->assertEquals( 'manage_options', $ability->getRequiredCapability() );
	}

	/**
	 * Test ability returns input schema with required fields.
	 *
	 * @return void
	 */
	public function testGetInputSchema(): void {
		$ability = new SetTransient();
		$schema  = $ability->getInputSchema();

		$this->assertIsArray( $schema );
		$this->assertArrayHasKey( 'type', $schema );
		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertArrayHasKey( 'required', $schema );
		$this->assertArrayHasKey( 'key', $schema['properties'] );
		$this->assertArrayHasKey( 'value', $schema['properties'] );
		$this->assertContains( 'key', $schema['required'] );
		$this->assertContains( 'value', $schema['required'] );
	}

	/**
	 * Test input schema includes optional expiration and network parameters.
	 *
	 * @return void
	 */
	public function testGetInputSchemaHasOptionalParameters(): void {
		$ability = new SetTransient();
		$schema  = $ability->getInputSchema();

		$this->assertArrayHasKey( 'expiration', $schema['properties'] );
		$this->assertArrayHasKey( 'network', $schema['properties'] );
		$this->assertEquals( 'integer', $schema['properties']['expiration']['type'] );
		$this->assertEquals( 'boolean', $schema['properties']['network']['type'] );
	}

	/**
	 * Test ability returns output schema.
	 *
	 * @return void
	 */
	public function testGetOutputSchema(): void {
		$ability = new SetTransient();
		$schema  = $ability->getOutputSchema();

		$this->assertIsArray( $schema );
		$this->assertArrayHasKey( 'type', $schema );
		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertArrayHasKey( 'success', $schema['properties'] );
	}

	/**
	 * Test execute sets transient successfully.
	 *
	 * @return void
	 */
	public function testExecuteSetsTransientSuccessfully(): void {
		$ability = new SetTransient();

		Functions\expect( 'set_transient' )
			->once()
			->with( 'my_cache', 'cached_value', 0 )
			->andReturn( true );

		$result = $ability->doExecute(
			array(
				'key'   => 'my_cache',
				'value' => 'cached_value',
			)
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );
	}

	/**
	 * Test execute returns failure when set_transient fails.
	 *
	 * @return void
	 */
	public function testExecuteReturnsFailureWhenSetFails(): void {
		$ability = new SetTransient();

		Functions\when( 'set_transient' )->justReturn( false );

		$result = $ability->doExecute(
			array(
				'key'   => 'my_cache',
				'value' => 'cached_value',
			)
		);

		$this->assertIsArray( $result );
		$this->assertFalse( $result['success'] );
	}

	/**
	 * Test execute uses expiration when provided.
	 *
	 * @return void
	 */
	public function testExecuteUsesExpirationWhenProvided(): void {
		$ability = new SetTransient();

		Functions\expect( 'set_transient' )
			->once()
			->with( 'timed_cache', 'value', 3600 )
			->andReturn( true );

		$result = $ability->doExecute(
			array(
				'key'        => 'timed_cache',
				'value'      => 'value',
				'expiration' => 3600,
			)
		);

		$this->assertTrue( $result['success'] );
	}

	/**
	 * Test execute uses set_site_transient for network transients.
	 *
	 * @return void
	 */
	public function testExecuteUsesSetSiteTransientForNetwork(): void {
		$ability = new SetTransient();

		Functions\expect( 'set_site_transient' )
			->once()
			->with( 'network_cache', 'network_value', 0 )
			->andReturn( true );

		$result = $ability->doExecute(
			array(
				'key'     => 'network_cache',
				'value'   => 'network_value',
				'network' => true,
			)
		);

		$this->assertTrue( $result['success'] );
	}

	/**
	 * Test execute handles array values.
	 *
	 * @return void
	 */
	public function testExecuteHandlesArrayValues(): void {
		$ability = new SetTransient();

		$array_value = array( 'key1' => 'value1', 'key2' => 'value2' );

		Functions\expect( 'set_transient' )
			->once()
			->with( 'array_cache', $array_value, 0 )
			->andReturn( true );

		$result = $ability->doExecute(
			array(
				'key'   => 'array_cache',
				'value' => $array_value,
			)
		);

		$this->assertTrue( $result['success'] );
	}

	/**
	 * Test execute defaults expiration to 0 (no expiration).
	 *
	 * @return void
	 */
	public function testExecuteDefaultsExpirationToZero(): void {
		$ability = new SetTransient();

		Functions\expect( 'set_transient' )
			->once()
			->with( 'no_expiry_cache', 'value', 0 )
			->andReturn( true );

		$result = $ability->doExecute(
			array(
				'key'   => 'no_expiry_cache',
				'value' => 'value',
			)
		);

		$this->assertTrue( $result['success'] );
	}

	/**
	 * Test annotations are correct for create operation.
	 *
	 * @return void
	 */
	public function testGetAnnotations(): void {
		$ability     = new SetTransient();
		$annotations = $ability->getAnnotations();

		$this->assertFalse( $annotations['readonly'] );
		$this->assertFalse( $annotations['destructive'] );
		$this->assertTrue( $annotations['idempotent'] );
	}
}
