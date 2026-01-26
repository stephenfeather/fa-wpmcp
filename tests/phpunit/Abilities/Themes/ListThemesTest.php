<?php

/**
 * Tests for ListThemes ability.
 *
 * @package FAWpmcp\Tests\Abilities\Themes
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Themes;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Themes\ListThemes;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test ListThemes ability functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Themes
 */
class ListThemesTest extends BrainMonkeyTestCase {

	use AbilityTestTrait;

	/**
	 * Get an instance of the ability being tested.
	 *
	 * @return AbstractAbility
	 */
	protected function getAbilityInstance(): AbstractAbility {
		return new ListThemes();
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
			'name'                  => 'fa-wpmcp/list-themes',
			'category'              => 'themes',
			'label'                 => 'List Themes',
			'description_contains'  => 'list installed wordpress themes',
			'operation_type'        => 'read',
			'required_capability'   => 'switch_themes',
		);
	}

	public function testExecuteListsAllThemes(): void {
		$ability = $this->getAbilityInstance();

		$theme1 = Mockery::mock( 'WP_Theme' );
		$theme1->shouldReceive( 'get_stylesheet' )->andReturn( 'twentytwentyfour' );
		$theme1->shouldReceive( 'get' )->with( 'Name' )->andReturn( 'Twenty Twenty-Four' );
		$theme1->shouldReceive( 'get' )->with( 'Version' )->andReturn( '1.0' );

		$theme2 = Mockery::mock( 'WP_Theme' );
		$theme2->shouldReceive( 'get_stylesheet' )->andReturn( 'twentytwentythree' );
		$theme2->shouldReceive( 'get' )->with( 'Name' )->andReturn( 'Twenty Twenty-Three' );
		$theme2->shouldReceive( 'get' )->with( 'Version' )->andReturn( '1.1' );

		Functions\expect( 'wp_get_themes' )
			->once()
			->andReturn(
				array(
					'twentytwentyfour'  => $theme1,
					'twentytwentythree' => $theme2,
				)
			);

		Functions\expect( 'get_option' )
			->with( 'stylesheet' )
			->once()
			->andReturn( 'twentytwentyfour' );

		$result = $ability->doExecute( array() );

		$this->assertCount( 2, $result['themes'] );
		$this->assertEquals( 'twentytwentyfour', $result['themes'][0]['stylesheet'] );
		$this->assertTrue( $result['themes'][0]['active'] );
		$this->assertEquals( 'twentytwentythree', $result['themes'][1]['stylesheet'] );
		$this->assertFalse( $result['themes'][1]['active'] );
		$this->assertEquals( 2, $result['total'] );
	}
}
