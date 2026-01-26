<?php

/**
 * Trait for common ability metadata tests.
 *
 * @package FAWpmcp\Tests\TestCase
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\TestCase;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Trait providing common ability metadata tests.
 *
 * Classes using this trait must implement getAbilityInstance() and
 * getExpectedMetadata() methods.
 *
 * @package FAWpmcp\Tests\TestCase
 */
trait AbilityTestTrait {

	/**
	 * Get an instance of the ability being tested.
	 *
	 * @return AbstractAbility
	 */
	abstract protected function getAbilityInstance(): AbstractAbility;

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
	abstract protected function getExpectedMetadata(): array;

	/**
	 * Test ability metadata in a single consolidated test.
	 *
	 * @return void
	 */
	public function testAbilityMetadata(): void {
		$ability  = $this->getAbilityInstance();
		$expected = $this->getExpectedMetadata();

		$this->assertEquals( $expected['name'], $ability->getName(), 'Name mismatch' );
		$this->assertEquals( $expected['category'], $ability->getCategory(), 'Category mismatch' );
		$this->assertEquals( $expected['label'], $ability->getLabel(), 'Label mismatch' );
		$this->assertStringContainsString(
			$expected['description_contains'],
			strtolower( $ability->getDescription() ),
			'Description should contain expected keyword'
		);
		$this->assertEquals( $expected['operation_type'], $ability->getOperationType(), 'Operation type mismatch' );
		$this->assertEquals(
			$expected['required_capability'],
			$ability->getRequiredCapability(),
			'Required capability mismatch'
		);
	}

	/**
	 * Test that input schema is valid.
	 *
	 * @return void
	 */
	public function testInputSchemaIsValid(): void {
		$ability = $this->getAbilityInstance();
		$schema  = $ability->getInputSchema();

		$this->assertIsArray( $schema, 'Input schema should be an array' );
		$this->assertArrayHasKey( 'type', $schema, 'Input schema should have type' );
		$this->assertEquals( 'object', $schema['type'], 'Input schema type should be object' );
		$this->assertArrayHasKey( 'properties', $schema, 'Input schema should have properties' );
	}

	/**
	 * Test that output schema is valid.
	 *
	 * @return void
	 */
	public function testOutputSchemaIsValid(): void {
		$ability = $this->getAbilityInstance();
		$schema  = $ability->getOutputSchema();

		$this->assertIsArray( $schema, 'Output schema should be an array' );
		$this->assertArrayHasKey( 'type', $schema, 'Output schema should have type' );
	}
}
