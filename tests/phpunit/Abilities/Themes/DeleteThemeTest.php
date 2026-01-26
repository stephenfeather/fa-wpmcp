<?php
/**
 * Tests for DeleteTheme.
 *
 * @package FAWpmcp\Tests\Abilities\Themes
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Themes;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Themes\DeleteTheme;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;

/**
 * Test DeleteTheme ability functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Themes
 */
class DeleteThemeTest extends BrainMonkeyTestCase {

	use AbilityTestTrait;

	/**
	 * Get an instance of the ability being tested.
	 *
	 * @return AbstractAbility
	 */
	protected function getAbilityInstance(): AbstractAbility {
		return new DeleteTheme();
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
			'name'                  => 'fa-wpmcp/delete-theme',
			'category'              => 'themes',
			'label'                 => 'Delete Theme',
			'description_contains'  => 'delete a wordpress theme',
			'operation_type'        => 'write',
			'required_capability'   => 'delete_themes',
		);
	}

	public function testExecuteThrowsNotImplementedException(): void {
		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Theme deletion is not yet implemented' );

		$this->getAbilityInstance()->doExecute( array( 'stylesheet' => 'twentytwentythree' ) );
	}
}
