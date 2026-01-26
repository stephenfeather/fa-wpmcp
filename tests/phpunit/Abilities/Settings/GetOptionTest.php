<?php
/**
 * Tests for GetOption ability.
 *
 * @package FAWpmcp\Tests\Abilities\Settings
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Settings;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Settings\GetOption;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test GetOption ability functionality.
 *
 * Tests cover:
 * - Get option that exists
 * - Get option that doesn't exist (returns default)
 * - Result formatting
 *
 * @package FAWpmcp\Tests\Abilities\Settings
 */
class GetOptionTest extends BrainMonkeyTestCase {

	use AbilityTestTrait;

	/**
	 * Get an instance of the ability being tested.
	 *
	 * @return AbstractAbility
	 */
	protected function getAbilityInstance(): AbstractAbility {
		return new GetOption();
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
			'name'                  => 'fa-wpmcp/get-option',
			'category'              => 'settings',
			'label'                 => 'Get Option',
			'description_contains'  => 'retrieve',
			'operation_type'        => 'read',
			'required_capability'   => 'manage_options',
		);
	}

	/**
	 * Test execute retrieves existing option.
	 *
	 * @return void
	 */
	public function testExecuteRetrievesExistingOption(): void {
		$ability = $this->getAbilityInstance();

		Functions\when( 'sanitize_key' )->returnArg();

		Functions\expect( 'get_option' )
			->once()
			->with( 'test_option', Mockery::type( 'stdClass' ) )
			->andReturn( 'test_value' );

		$result = $ability->doExecute( array( 'option_name' => 'test_option' ) );

		$this->assertEquals( 'test_option', $result['option_name'] );
		$this->assertEquals( 'test_value', $result['value'] );
		$this->assertTrue( $result['exists'] );
	}

	/**
	 * Test execute returns default for non-existent option.
	 *
	 * @return void
	 */
	public function testExecuteReturnsDefaultForNonExistentOption(): void {
		$ability = $this->getAbilityInstance();

		Functions\when( 'sanitize_key' )->returnArg();

		Functions\expect( 'get_option' )
			->once()
			->with( 'missing_option', Mockery::type( 'stdClass' ) )
			->andReturnUsing(
				function ( $name, $sentinel ) {
					return $sentinel;
				}
			);

		$result = $ability->doExecute(
			array(
				'option_name' => 'missing_option',
				'default'     => 'default_value',
			)
		);

		$this->assertEquals( 'missing_option', $result['option_name'] );
		$this->assertEquals( 'default_value', $result['value'] );
		$this->assertFalse( $result['exists'] );
	}

	/**
	 * Test execute handles array option values.
	 *
	 * @return void
	 */
	public function testExecuteHandlesArrayOptionValues(): void {
		$ability      = $this->getAbilityInstance();
		$option_value = array(
			'key1' => 'value1',
			'key2' => 'value2',
		);

		Functions\when( 'sanitize_key' )->returnArg();

		Functions\expect( 'get_option' )
			->once()
			->with( 'array_option', Mockery::type( 'stdClass' ) )
			->andReturn( $option_value );

		$result = $ability->doExecute( array( 'option_name' => 'array_option' ) );

		$this->assertEquals( 'array_option', $result['option_name'] );
		$this->assertEquals( $option_value, $result['value'] );
		$this->assertTrue( $result['exists'] );
	}

	/**
	 * Test execute blocks protected options.
	 *
	 * @return void
	 */
	public function testExecuteBlocksProtectedOption(): void {
		$ability = $this->getAbilityInstance();

		Functions\when( 'sanitize_key' )->returnArg();

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'protected' );

		$ability->doExecute( array( 'option_name' => 'admin_email' ) );
	}
}
