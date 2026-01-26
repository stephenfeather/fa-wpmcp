<?php

/**
 * Tests for DeleteOption ability.
 *
 * @package FAWpmcp\Tests\Abilities\Settings
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Settings;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Settings\DeleteOption;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;

/**
 * Test DeleteOption ability functionality.
 *
 * Tests cover:
 * - Delete existing option
 * - Delete non-existent option
 *
 * @package FAWpmcp\Tests\Abilities\Settings
 */
class DeleteOptionTest extends BrainMonkeyTestCase {

	use AbilityTestTrait;

	/**
	 * Get an instance of the ability being tested.
	 *
	 * @return AbstractAbility
	 */
	protected function getAbilityInstance(): AbstractAbility {
		return new DeleteOption();
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
			'name'                  => 'fa-wpmcp/delete-option',
			'category'              => 'settings',
			'label'                 => 'Delete Option',
			'description_contains'  => 'delete',
			'operation_type'        => 'write',
			'required_capability'   => 'manage_options',
		);
	}

	/**
	 * Test execute deletes existing option successfully.
	 *
	 * @return void
	 */
	public function testExecuteDeletesOption(): void {
		$ability = $this->getAbilityInstance();

		Functions\when( 'sanitize_key' )->returnArg();

		Functions\expect( 'delete_option' )
			->once()
			->with( 'test_option' )
			->andReturn( true );

		$result = $ability->doExecute( array( 'option_name' => 'test_option' ) );

		$this->assertEquals( 'test_option', $result['option_name'] );
		$this->assertTrue( $result['deleted'] );
	}

	/**
	 * Test execute handles delete failure for non-existent option.
	 *
	 * @return void
	 */
	public function testExecuteHandlesDeleteFailure(): void {
		$ability = $this->getAbilityInstance();

		Functions\when( 'sanitize_key' )->returnArg();

		Functions\expect( 'delete_option' )
			->once()
			->with( 'missing_option' )
			->andReturn( false );

		$result = $ability->doExecute( array( 'option_name' => 'missing_option' ) );

		$this->assertEquals( 'missing_option', $result['option_name'] );
		$this->assertFalse( $result['deleted'] );
	}

	/**
	 * Test execute blocks protected options.
	 *
	 * @return void
	 */
	public function testExecuteBlocksProtectedOption(): void {
		$ability = $this->getAbilityInstance();

		Functions\when( 'sanitize_key' )->returnArg();

		Functions\expect( 'delete_option' )
			->never();

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'protected' );

		$ability->doExecute( array( 'option_name' => 'admin_email' ) );
	}
}
