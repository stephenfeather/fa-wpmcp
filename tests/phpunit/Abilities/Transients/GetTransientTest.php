<?php
/**
 * Tests for GetTransient ability.
 *
 * @package FAWpmcp\Tests\Abilities\Transients
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Transients;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Transients\GetTransient;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;

/**
 * Test GetTransient ability functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Transients
 */
class GetTransientTest extends BrainMonkeyTestCase {
	use AbilityTestTrait;

	/**
	 * Get an instance of the ability being tested.
	 *
	 * @return AbstractAbility
	 */
	protected function getAbilityInstance(): AbstractAbility {
		return new GetTransient();
	}

	/**
	 * Get expected metadata for the ability.
	 *
	 * @return array{
	 *     name: string,
	 *     category: string,
	 *     label: string,
	 *     description_contains: string,
	 *     operation_type: string,
	 *     required_capability: string
	 * }
	 */
	protected function getExpectedMetadata(): array {
		return array(
			'name'                 => 'fa-wpmcp/get-transient',
			'category'             => 'transients',
			'label'                => 'Get Transient',
			'description_contains' => 'retrieve',
			'operation_type'       => 'read',
			'required_capability'  => 'manage_options',
		);
	}

	/**
	 * Test input schema includes required key field.
	 *
	 * @return void
	 */
	public function testInputSchemaHasKeyField(): void {
		$ability = $this->getAbilityInstance();
		$schema  = $ability->getInputSchema();

		$this->assertArrayHasKey( 'key', $schema['properties'] );
		$this->assertContains( 'key', $schema['required'] );
	}

	/**
	 * Test input schema includes optional network parameter.
	 *
	 * @return void
	 */
	public function testInputSchemaHasNetworkParameter(): void {
		$ability = $this->getAbilityInstance();
		$schema  = $ability->getInputSchema();

		$this->assertArrayHasKey( 'network', $schema['properties'] );
		$this->assertEquals( 'boolean', $schema['properties']['network']['type'] );
	}

	/**
	 * Test output schema has expected fields.
	 *
	 * @return void
	 */
	public function testOutputSchemaHasExpectedFields(): void {
		$ability = $this->getAbilityInstance();
		$schema  = $ability->getOutputSchema();

		$this->assertArrayHasKey( 'value', $schema['properties'] );
		$this->assertArrayHasKey( 'exists', $schema['properties'] );
	}

	/**
	 * Test execute returns transient value when it exists.
	 *
	 * @return void
	 */
	public function testExecuteReturnsTransientValue(): void {
		$ability = $this->getAbilityInstance();

		Functions\when( 'get_transient' )->justReturn( 'cached_value' );

		$result = $ability->doExecute( array( 'key' => 'my_transient' ) );

		$this->assertIsArray( $result );
		$this->assertTrue( $result['exists'] );
		$this->assertEquals( 'cached_value', $result['value'] );
	}

	/**
	 * Test execute returns exists false when transient does not exist.
	 *
	 * @return void
	 */
	public function testExecuteReturnsExistsFalseWhenNotFound(): void {
		$ability = $this->getAbilityInstance();

		Functions\when( 'get_transient' )->justReturn( false );

		$result = $ability->doExecute( array( 'key' => 'nonexistent_transient' ) );

		$this->assertIsArray( $result );
		$this->assertFalse( $result['exists'] );
		$this->assertNull( $result['value'] );
	}

	/**
	 * Test execute uses get_site_transient for network transients.
	 *
	 * @return void
	 */
	public function testExecuteUsesGetSiteTransientForNetwork(): void {
		$ability = $this->getAbilityInstance();

		Functions\expect( 'get_site_transient' )
			->once()
			->with( 'network_transient' )
			->andReturn( 'network_value' );

		$result = $ability->doExecute(
			array(
				'key'     => 'network_transient',
				'network' => true,
			)
		);

		$this->assertTrue( $result['exists'] );
		$this->assertEquals( 'network_value', $result['value'] );
	}

	/**
	 * Test execute handles array transient values.
	 *
	 * @return void
	 */
	public function testExecuteHandlesArrayValues(): void {
		$ability = $this->getAbilityInstance();

		$array_value = array(
			'key1' => 'value1',
			'key2' => 'value2',
		);
		Functions\when( 'get_transient' )->justReturn( $array_value );

		$result = $ability->doExecute( array( 'key' => 'array_transient' ) );

		$this->assertTrue( $result['exists'] );
		$this->assertEquals( $array_value, $result['value'] );
	}

	/**
	 * Test execute handles object transient values.
	 *
	 * @return void
	 */
	public function testExecuteHandlesObjectValues(): void {
		$ability = $this->getAbilityInstance();

		$object_value = (object) array( 'prop' => 'value' );
		Functions\when( 'get_transient' )->justReturn( $object_value );

		$result = $ability->doExecute( array( 'key' => 'object_transient' ) );

		$this->assertTrue( $result['exists'] );
		$this->assertEquals( $object_value, $result['value'] );
	}

	/**
	 * Test annotations are correct for read-only ability.
	 *
	 * @return void
	 */
	public function testGetAnnotations(): void {
		$ability     = $this->getAbilityInstance();
		$annotations = $ability->getAnnotations();

		$this->assertTrue( $annotations['readonly'] );
		$this->assertFalse( $annotations['destructive'] );
		$this->assertTrue( $annotations['idempotent'] );
	}
}
