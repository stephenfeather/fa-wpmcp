<?php
/**
 * Tests for StatusTheme.
 *
 * @package FAWpmcp\Tests\Abilities\Themes
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Themes;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Themes\StatusTheme;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test StatusTheme ability functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Themes
 */
class StatusThemeTest extends BrainMonkeyTestCase {

	use AbilityTestTrait;

	/**
	 * Get an instance of the ability being tested.
	 *
	 * @return AbstractAbility
	 */
	protected function getAbilityInstance(): AbstractAbility {
		return new StatusTheme();
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
			'name'                  => 'fa-wpmcp/status-theme',
			'category'              => 'themes',
			'label'                 => 'Theme Status',
			'description_contains'  => 'get status details for a wordpress theme',
			'operation_type'        => 'read',
			'required_capability'   => 'switch_themes',
		);
	}

	public function testExecuteReturnsThemeStatus(): void {
		$theme = Mockery::mock( 'WP_Theme' );
		$theme->shouldReceive( 'get_stylesheet' )->andReturn( 'twentytwentyfour' );
		$theme->shouldReceive( 'get' )->with( 'Name' )->andReturn( 'Twenty Twenty-Four' );
		$theme->shouldReceive( 'get' )->with( 'Version' )->andReturn( '1.0' );
		$theme->shouldReceive( 'get' )->with( 'Author' )->andReturn( 'WordPress Team' );

		Functions\expect( 'wp_get_theme' )->once()->with( 'twentytwentyfour' )->andReturn( $theme );
		Functions\expect( 'get_option' )->with( 'stylesheet' )->once()->andReturn( 'twentytwentyfour' );

		$result = $this->getAbilityInstance()->doExecute( array( 'stylesheet' => 'twentytwentyfour' ) );

		$this->assertEquals( 'Twenty Twenty-Four', $result['name'] );
		$this->assertEquals( 'Active', $result['status'] );
	}
}
