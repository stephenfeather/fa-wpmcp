<?php

/**
 * Tests for ActivateTheme.
 *
 * @package FAWpmcp\Tests\Abilities\Themes
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Themes;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Themes\ActivateTheme;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;

/**
 * Test ActivateTheme ability functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Themes
 */
class ActivateThemeTest extends BrainMonkeyTestCase {

	use AbilityTestTrait;

	/**
	 * Get an instance of the ability being tested.
	 *
	 * @return AbstractAbility
	 */
	protected function getAbilityInstance(): AbstractAbility {
		return new ActivateTheme();
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
			'name'                  => 'fa-wpmcp/activate-theme',
			'category'              => 'themes',
			'label'                 => 'Activate Theme',
			'description_contains'  => 'activate a wordpress theme',
			'operation_type'        => 'write',
			'required_capability'   => 'switch_themes',
		);
	}

	public function testExecuteActivatesTheme(): void {
		Functions\expect( 'switch_theme' )->once()->with( 'twentytwentyfour' );
		$result = $this->getAbilityInstance()->doExecute( array( 'stylesheet' => 'twentytwentyfour' ) );
		$this->assertTrue( $result['success'] );
	}
}
