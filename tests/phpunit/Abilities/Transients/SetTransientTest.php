<?php

/**
 * Tests for SetTransient ability.
 *
 * @package FAWpmcp\Tests\Abilities\Transients
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Transients;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Transients\SetTransient;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;

/**
 * Test SetTransient ability functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Transients
 */
class SetTransientTest extends BrainMonkeyTestCase {
	use AbilityTestTrait;

	/**
	 * Get an instance of the ability being tested.
	 *
	 * @return AbstractAbility
	 */
	protected function getAbilityInstance(): AbstractAbility {
		return new SetTransient();
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
			'name'                 => 'fa-wpmcp/set-transient',
			'category'             => 'transients',
			'label'                => 'Set Transient',
			'description_contains' => 'create',
			'operation_type'       => 'create',
			'required_capability'  => 'manage_options',
		);
	}

	/**
	 * Test input schema includes required fields.
	 *
	 * @return void
	 */
	public function testInputSchemaHasRequiredFields(): void {
		$ability = $this->getAbilityInstance();
		$schema  = $ability->getInputSchema();

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
	public function testInputSchemaHasOptionalParameters(): void {
		$ability = $this->getAbilityInstance();
		$schema  = $ability->getInputSchema();

		$this->assertArrayHasKey( 'expiration', $schema['properties'] );
		$this->assertArrayHasKey( 'network', $schema['properties'] );
		$this->assertEquals( 'integer', $schema['properties']['expiration']['type'] );
		$this->assertEquals( 'boolean', $schema['properties']['network']['type'] );
	}

	/**
	 * Test output schema has success field.
	 *
	 * @return void
	 */
	public function testOutputSchemaHasSuccessField(): void {
		$ability = $this->getAbilityInstance();
		$schema  = $ability->getOutputSchema();

		$this->assertArrayHasKey( 'success', $schema['properties'] );
	}

	/**
	 * Test execute sets transient successfully.
	 *
	 * @return void
	 */
	public function testExecuteSetsTransientSuccessfully(): void {
		$ability = $this->getAbilityInstance();

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
		$ability = $this->getAbilityInstance();

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
		$ability = $this->getAbilityInstance();

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
		$ability = $this->getAbilityInstance();

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
		$ability = $this->getAbilityInstance();

		$array_value = array(
			'key1' => 'value1',
			'key2' => 'value2',
		);

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
		$ability = $this->getAbilityInstance();

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
		$ability     = $this->getAbilityInstance();
		$annotations = $ability->getAnnotations();

		$this->assertFalse( $annotations['readonly'] );
		$this->assertFalse( $annotations['destructive'] );
		$this->assertTrue( $annotations['idempotent'] );
	}
}
