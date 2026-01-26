<?php

/**
 * Tests for GetMenuAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Menu
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Menu;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Menu\GetMenuAbility;
use FAWpmcp\Exceptions\MenuNotFoundException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test GetMenuAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Menu
 */
class GetMenuAbilityTest extends BrainMonkeyTestCase {

	use AbilityTestTrait;

	/**
	 * Get an instance of the ability being tested.
	 *
	 * @return AbstractAbility
	 */
	protected function getAbilityInstance(): AbstractAbility {
		return new GetMenuAbility();
	}

	/**
	 * Get expected metadata for the ability.
	 *
	 * @return array<string, string>
	 */
	protected function getExpectedMetadata(): array {
		return array(
			'name'                 => 'fa-wpmcp/get-menu',
			'category'             => 'menu',
			'label'                => 'Get Menu',
			'description_contains' => 'menu',
			'operation_type'       => 'read',
			'required_capability'  => 'edit_theme_options',
		);
	}

	/**
	 * Test ability returns input schema with required menu field.
	 *
	 * @return void
	 */
	public function testGetInputSchema(): void {
		$ability = $this->getAbilityInstance();
		$schema  = $ability->getInputSchema();

		$this->assertIsArray( $schema );
		$this->assertArrayHasKey( 'type', $schema );
		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertArrayHasKey( 'required', $schema );
		$this->assertArrayHasKey( 'menu', $schema['properties'] );
		$this->assertContains( 'menu', $schema['required'] );
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
		$this->assertArrayHasKey( 'term_id', $schema['properties'] );
		$this->assertArrayHasKey( 'name', $schema['properties'] );
		$this->assertArrayHasKey( 'slug', $schema['properties'] );
		$this->assertArrayHasKey( 'locations', $schema['properties'] );
		$this->assertArrayHasKey( 'items', $schema['properties'] );
	}

	/**
	 * Test execute returns menu details by ID.
	 *
	 * @return void
	 */
	public function testExecuteReturnsMenuDetailsByID(): void {
		$ability = $this->getAbilityInstance();

		$menu = Mockery::mock( 'WP_Term' );
		$menu->term_id = 10;
		$menu->name = 'Main Menu';
		$menu->slug = 'main-menu';
		$menu->count = 3;

		// Mock menu item.
		$menu_item = Mockery::mock( 'WP_Post' );
		$menu_item->ID = 100;
		$menu_item->menu_item_parent = 0;
		$menu_item->title = 'Home';
		$menu_item->url = 'http://example.com/';
		$menu_item->type = 'custom';
		$menu_item->object = 'custom';
		$menu_item->object_id = 100;
		$menu_item->menu_order = 1;

		Functions\when( 'wp_get_nav_menu_object' )->justReturn( $menu );
		Functions\when( 'wp_get_nav_menu_items' )->justReturn( array( $menu_item ) );
		Functions\when( 'get_nav_menu_locations' )->justReturn(
			array(
				'primary' => 10,
			)
		);

		$result = $ability->doExecute( array( 'menu' => 10 ) );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'term_id', $result );
		$this->assertArrayHasKey( 'name', $result );
		$this->assertArrayHasKey( 'slug', $result );
		$this->assertArrayHasKey( 'locations', $result );
		$this->assertArrayHasKey( 'items', $result );
		$this->assertEquals( 10, $result['term_id'] );
		$this->assertEquals( 'Main Menu', $result['name'] );
		$this->assertEquals( 'main-menu', $result['slug'] );
	}

	/**
	 * Test execute returns menu details by slug.
	 *
	 * @return void
	 */
	public function testExecuteReturnsMenuDetailsBySlug(): void {
		$ability = $this->getAbilityInstance();

		$menu = Mockery::mock( 'WP_Term' );
		$menu->term_id = 10;
		$menu->name = 'Main Menu';
		$menu->slug = 'main-menu';
		$menu->count = 3;

		Functions\when( 'wp_get_nav_menu_object' )->justReturn( $menu );
		Functions\when( 'wp_get_nav_menu_items' )->justReturn( array() );
		Functions\when( 'get_nav_menu_locations' )->justReturn( array() );

		$result = $ability->doExecute( array( 'menu' => 'main-menu' ) );

		$this->assertEquals( 10, $result['term_id'] );
		$this->assertEquals( 'Main Menu', $result['name'] );
	}

	/**
	 * Test execute returns menu items.
	 *
	 * @return void
	 */
	public function testExecuteReturnsMenuItems(): void {
		$ability = $this->getAbilityInstance();

		$menu = Mockery::mock( 'WP_Term' );
		$menu->term_id = 10;
		$menu->name = 'Main Menu';
		$menu->slug = 'main-menu';
		$menu->count = 2;

		$menu_item1 = Mockery::mock( 'WP_Post' );
		$menu_item1->ID = 100;
		$menu_item1->menu_item_parent = 0;
		$menu_item1->title = 'Home';
		$menu_item1->url = 'http://example.com/';
		$menu_item1->type = 'custom';
		$menu_item1->object = 'custom';
		$menu_item1->object_id = 100;
		$menu_item1->menu_order = 1;

		$menu_item2 = Mockery::mock( 'WP_Post' );
		$menu_item2->ID = 101;
		$menu_item2->menu_item_parent = 0;
		$menu_item2->title = 'About';
		$menu_item2->url = 'http://example.com/about';
		$menu_item2->type = 'post_type';
		$menu_item2->object = 'page';
		$menu_item2->object_id = 50;
		$menu_item2->menu_order = 2;

		Functions\when( 'wp_get_nav_menu_object' )->justReturn( $menu );
		Functions\when( 'wp_get_nav_menu_items' )->justReturn( array( $menu_item1, $menu_item2 ) );
		Functions\when( 'get_nav_menu_locations' )->justReturn( array() );

		$result = $ability->doExecute( array( 'menu' => 10 ) );

		$this->assertCount( 2, $result['items'] );
		$this->assertEquals( 'Home', $result['items'][0]['title'] );
		$this->assertEquals( 'About', $result['items'][1]['title'] );
	}

	/**
	 * Test execute returns menu item structure.
	 *
	 * @return void
	 */
	public function testExecuteReturnsMenuItemStructure(): void {
		$ability = $this->getAbilityInstance();

		$menu = Mockery::mock( 'WP_Term' );
		$menu->term_id = 10;
		$menu->name = 'Main Menu';
		$menu->slug = 'main-menu';
		$menu->count = 1;

		$menu_item = Mockery::mock( 'WP_Post' );
		$menu_item->ID = 100;
		$menu_item->menu_item_parent = 0;
		$menu_item->title = 'Home';
		$menu_item->url = 'http://example.com/';
		$menu_item->type = 'custom';
		$menu_item->object = 'custom';
		$menu_item->object_id = 100;
		$menu_item->menu_order = 1;

		Functions\when( 'wp_get_nav_menu_object' )->justReturn( $menu );
		Functions\when( 'wp_get_nav_menu_items' )->justReturn( array( $menu_item ) );
		Functions\when( 'get_nav_menu_locations' )->justReturn( array() );

		$result = $ability->doExecute( array( 'menu' => 10 ) );

		$item = $result['items'][0];
		$this->assertArrayHasKey( 'id', $item );
		$this->assertArrayHasKey( 'parent_id', $item );
		$this->assertArrayHasKey( 'title', $item );
		$this->assertArrayHasKey( 'url', $item );
		$this->assertArrayHasKey( 'type', $item );
		$this->assertArrayHasKey( 'object', $item );
		$this->assertArrayHasKey( 'object_id', $item );
		$this->assertArrayHasKey( 'menu_order', $item );
	}

	/**
	 * Test execute throws exception when menu not found.
	 *
	 * @return void
	 */
	public function testExecuteThrowsExceptionWhenMenuNotFound(): void {
		$ability = $this->getAbilityInstance();

		Functions\when( 'wp_get_nav_menu_object' )->justReturn( false );

		$this->expectException( MenuNotFoundException::class );
		$this->expectExceptionMessage( 'Menu "nonexistent" not found.' );

		$ability->doExecute( array( 'menu' => 'nonexistent' ) );
	}

	/**
	 * Test execute throws exception when menu ID not found.
	 *
	 * @return void
	 */
	public function testExecuteThrowsExceptionWhenMenuIDNotFound(): void {
		$ability = $this->getAbilityInstance();

		Functions\when( 'wp_get_nav_menu_object' )->justReturn( false );

		$this->expectException( MenuNotFoundException::class );
		$this->expectExceptionMessage( 'Menu "999" not found.' );

		$ability->doExecute( array( 'menu' => 999 ) );
	}

	/**
	 * Test annotations are correct for read-only ability.
	 *
	 * @return void
	 */
	public function testGetAnnotations(): void {
		$ability     = new GetMenuAbility();
		$annotations = $ability->getAnnotations();

		$this->assertTrue( $annotations['readonly'] );
		$this->assertFalse( $annotations['destructive'] );
		$this->assertTrue( $annotations['idempotent'] );
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
		$menu->count = 0;

		Functions\when( 'wp_get_nav_menu_object' )->justReturn( $menu );
		Functions\when( 'wp_get_nav_menu_items' )->justReturn( array() );
		Functions\when( 'get_nav_menu_locations' )->justReturn(
			array(
				'primary'   => 10,
				'secondary' => 10,
				'footer'    => 20,
			)
		);

		$result = $ability->doExecute( array( 'menu' => 10 ) );

		$this->assertIsArray( $result['locations'] );
		$this->assertContains( 'primary', $result['locations'] );
		$this->assertContains( 'secondary', $result['locations'] );
		$this->assertNotContains( 'footer', $result['locations'] );
	}
}
