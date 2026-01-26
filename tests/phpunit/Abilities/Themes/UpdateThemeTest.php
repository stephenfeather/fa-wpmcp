<?php
/**
 * Tests for UpdateTheme.
 *
 * @package FAWpmcp\Tests\Abilities\Themes
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Themes;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Themes\UpdateTheme;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;

/**
 * Test UpdateTheme ability functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Themes
 */
class UpdateThemeTest extends BrainMonkeyTestCase {

	use AbilityTestTrait;

	/**
	 * Get an instance of the ability being tested.
	 *
	 * @return AbstractAbility
	 */
	protected function getAbilityInstance(): AbstractAbility {
		return new UpdateTheme();
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
			'name'                  => 'fa-wpmcp/update-theme',
			'category'              => 'themes',
			'label'                 => 'Update Theme',
			'description_contains'  => 'update a wordpress theme',
			'operation_type'        => 'write',
			'required_capability'   => 'update_themes',
		);
	}

	public function testExecuteReturnsSuccess(): void {
		$result = $this->getAbilityInstance()->doExecute( array( 'stylesheet' => 'twentytwentyfour' ) );
		$this->assertTrue( $result['success'] );
	}
}
