<?php

/**
 * Tests for ListMenusAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Menu
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Menu;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Menu\ListMenusAbility;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test ListMenusAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Menu
 */
class ListMenusAbilityTest extends BrainMonkeyTestCase {

	use AbilityTestTrait;

	/**
	 * Get an instance of the ability being tested.
	 *
	 * @return AbstractAbility
	 */
	protected function getAbilityInstance(): AbstractAbility {
		return new ListMenusAbility();
	}

	/**
	 * Get expected metadata for the ability.
	 *
	 * @return array<string, string>
	 */
	protected function getExpectedMetadata(): array {
		return array(
			'name'                 => 'fa-wpmcp/list-menus',
			'category'             => 'menu',
			'label'                => 'List Menus',
			'description_contains' => 'list',
			'operation_type'       => 'read',
			'required_capability'  => 'edit_theme_options',
		);
	}

	/**
	 * Test ability returns input schema.
	 *
	 * @return void
	 */
	public function testGetInputSchema(): void {
		$ability = $this->getAbilityInstance();
		$schema  = $ability->getInputSchema();

		$this->assertIsArray( $schema );
		$this->assertArrayHasKey( 'type', $schema );
		$this->assertEquals( 'object', $schema['type'] );
	}

	/**
	 * Test ability returns output schema.
	 *
	 * @return void
	 */
	public function testGetOutputSchema(): void {
		$ability = $this->getAbilityInstance();
		$schema  = $ability->getOutputSchema();

		$this->assertIsArray( $schema );
		$this->assertArrayHasKey( 'type', $schema );
		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertArrayHasKey( 'menus', $schema['properties'] );
		$this->assertArrayHasKey( 'total', $schema['properties'] );
	}

	/**
	 * Test execute returns list of menus.
	 *
	 * @return void
	 */
	public function testExecuteReturnsListOfMenus(): void {
		$ability = $this->getAbilityInstance();

		// Create mock WP_Term objects for menus.
		$menu1 = Mockery::mock( 'WP_Term' );
		$menu1->term_id = 10;
		$menu1->name = 'Main Menu';
		$menu1->slug = 'main-menu';
		$menu1->count = 5;

		$menu2 = Mockery::mock( 'WP_Term' );
		$menu2->term_id = 11;
		$menu2->name = 'Footer Menu';
		$menu2->slug = 'footer-menu';
		$menu2->count = 3;

		Functions\when( 'wp_get_nav_menus' )->justReturn( array( $menu1, $menu2 ) );
		Functions\when( 'get_nav_menu_locations' )->justReturn(
			array(
				'primary'   => 10,
				'secondary' => 11,
			)
		);

		$result = $ability->doExecute( array() );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'menus', $result );
		$this->assertArrayHasKey( 'total', $result );
		$this->assertCount( 2, $result['menus'] );
		$this->assertEquals( 2, $result['total'] );
	}

	/**
	 * Test execute returns menu structure with expected fields.
	 *
	 * @return void
	 */
	public function testExecuteReturnsMenuStructureWithExpectedFields(): void {
		$ability = $this->getAbilityInstance();

		$menu = Mockery::mock( 'WP_Term' );
		$menu->term_id = 10;
		$menu->name = 'Main Menu';
		$menu->slug = 'main-menu';
		$menu->count = 5;

		Functions\when( 'wp_get_nav_menus' )->justReturn( array( $menu ) );
		Functions\when( 'get_nav_menu_locations' )->justReturn(
			array(
				'primary' => 10,
			)
		);

		$result = $ability->doExecute( array() );

		$menu_data = $result['menus'][0];
		$this->assertArrayHasKey( 'term_id', $menu_data );
		$this->assertArrayHasKey( 'name', $menu_data );
		$this->assertArrayHasKey( 'slug', $menu_data );
		$this->assertArrayHasKey( 'locations', $menu_data );
		$this->assertArrayHasKey( 'item_count', $menu_data );
		$this->assertEquals( 10, $menu_data['term_id'] );
		$this->assertEquals( 'Main Menu', $menu_data['name'] );
		$this->assertEquals( 'main-menu', $menu_data['slug'] );
		$this->assertEquals( 5, $menu_data['item_count'] );
	}

	/**
	 * Test execute returns locations for menu.
	 *
	 * @return void
	 */
	public function testExecuteReturnsLocationsForMenu(): void {
		$ability = $this->getAbilityInstance();

		$menu = Mockery::mock( 'WP_Term' );
		$menu->term_id = 10;
		$menu->name = 'Main Menu';
		$menu->slug = 'main-menu';
		$menu->count = 5;

		Functions\when( 'wp_get_nav_menus' )->justReturn( array( $menu ) );
		Functions\when( 'get_nav_menu_locations' )->justReturn(
			array(
				'primary'   => 10,
				'secondary' => 10,
			)
		);

		$result = $ability->doExecute( array() );

		$menu_data = $result['menus'][0];
		$this->assertIsArray( $menu_data['locations'] );
		$this->assertContains( 'primary', $menu_data['locations'] );
		$this->assertContains( 'secondary', $menu_data['locations'] );
	}

	/**
	 * Test execute returns empty array when no menus exist.
	 *
	 * @return void
	 */
	public function testExecuteReturnsEmptyArrayWhenNoMenus(): void {
		$ability = $this->getAbilityInstance();

		Functions\when( 'wp_get_nav_menus' )->justReturn( array() );
		Functions\when( 'get_nav_menu_locations' )->justReturn( array() );

		$result = $ability->doExecute( array() );

		$this->assertIsArray( $result['menus'] );
		$this->assertEmpty( $result['menus'] );
		$this->assertEquals( 0, $result['total'] );
	}

	/**
	 * Test annotations are correct for read-only ability.
	 *
	 * @return void
	 */
	public function testGetAnnotations(): void {
		$ability     = new ListMenusAbility();
		$annotations = $ability->getAnnotations();

		$this->assertTrue( $annotations['readonly'] );
		$this->assertFalse( $annotations['destructive'] );
		$this->assertTrue( $annotations['idempotent'] );
	}
}
