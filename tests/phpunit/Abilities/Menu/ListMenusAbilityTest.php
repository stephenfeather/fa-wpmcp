<?php

/**
 * Tests for ListMenusAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Menu
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Menu;

use FAWpmcp\Abilities\Menu\ListMenusAbility;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test ListMenusAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Menu
 */
class ListMenusAbilityTest extends TestCase
{
    /**
     * Set up Brain\Monkey before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }

    /**
     * Tear down Brain\Monkey after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Monkey\tearDown();
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test ability returns correct name.
     *
     * @return void
     */
    public function testGetName(): void
    {
        $ability = new ListMenusAbility();
        $this->assertEquals('fa-wpmcp/list-menus', $ability->getName());
    }

    /**
     * Test ability returns correct category.
     *
     * @return void
     */
    public function testGetCategory(): void
    {
        $ability = new ListMenusAbility();
        $this->assertEquals('menu', $ability->getCategory());
    }

    /**
     * Test ability returns correct label.
     *
     * @return void
     */
    public function testGetLabel(): void
    {
        $ability = new ListMenusAbility();
        $this->assertEquals('List Menus', $ability->getLabel());
    }

    /**
     * Test ability returns correct operation type.
     *
     * @return void
     */
    public function testGetOperationType(): void
    {
        $ability = new ListMenusAbility();
        $this->assertEquals('read', $ability->getOperationType());
    }

    /**
     * Test ability returns correct required capability.
     *
     * @return void
     */
    public function testGetRequiredCapability(): void
    {
        $ability = new ListMenusAbility();
        $this->assertEquals('edit_theme_options', $ability->getRequiredCapability());
    }

    /**
     * Test ability returns input schema.
     *
     * @return void
     */
    public function testGetInputSchema(): void
    {
        $ability = new ListMenusAbility();
        $schema  = $ability->getInputSchema();

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('type', $schema);
        $this->assertEquals('object', $schema['type']);
    }

    /**
     * Test ability returns output schema.
     *
     * @return void
     */
    public function testGetOutputSchema(): void
    {
        $ability = new ListMenusAbility();
        $schema  = $ability->getOutputSchema();

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('type', $schema);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('menus', $schema['properties']);
        $this->assertArrayHasKey('total', $schema['properties']);
    }

    /**
     * Test execute returns list of menus.
     *
     * @return void
     */
    public function testExecuteReturnsListOfMenus(): void
    {
        $ability = new ListMenusAbility();

        // Create mock WP_Term objects for menus.
        $menu1 = Mockery::mock('WP_Term');
        $menu1->term_id = 10;
        $menu1->name = 'Main Menu';
        $menu1->slug = 'main-menu';
        $menu1->count = 5;

        $menu2 = Mockery::mock('WP_Term');
        $menu2->term_id = 11;
        $menu2->name = 'Footer Menu';
        $menu2->slug = 'footer-menu';
        $menu2->count = 3;

        Functions\when('wp_get_nav_menus')->justReturn(array( $menu1, $menu2 ));
        Functions\when('get_nav_menu_locations')->justReturn(
            array(
                'primary'   => 10,
                'secondary' => 11,
            )
        );

        $result = $ability->doExecute(array());

        $this->assertIsArray($result);
        $this->assertArrayHasKey('menus', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertCount(2, $result['menus']);
        $this->assertEquals(2, $result['total']);
    }

    /**
     * Test execute returns menu structure with expected fields.
     *
     * @return void
     */
    public function testExecuteReturnsMenuStructureWithExpectedFields(): void
    {
        $ability = new ListMenusAbility();

        $menu = Mockery::mock('WP_Term');
        $menu->term_id = 10;
        $menu->name = 'Main Menu';
        $menu->slug = 'main-menu';
        $menu->count = 5;

        Functions\when('wp_get_nav_menus')->justReturn(array( $menu ));
        Functions\when('get_nav_menu_locations')->justReturn(
            array(
                'primary' => 10,
            )
        );

        $result = $ability->doExecute(array());

        $menu_data = $result['menus'][0];
        $this->assertArrayHasKey('term_id', $menu_data);
        $this->assertArrayHasKey('name', $menu_data);
        $this->assertArrayHasKey('slug', $menu_data);
        $this->assertArrayHasKey('locations', $menu_data);
        $this->assertArrayHasKey('item_count', $menu_data);
        $this->assertEquals(10, $menu_data['term_id']);
        $this->assertEquals('Main Menu', $menu_data['name']);
        $this->assertEquals('main-menu', $menu_data['slug']);
        $this->assertEquals(5, $menu_data['item_count']);
    }

    /**
     * Test execute returns locations for menu.
     *
     * @return void
     */
    public function testExecuteReturnsLocationsForMenu(): void
    {
        $ability = new ListMenusAbility();

        $menu = Mockery::mock('WP_Term');
        $menu->term_id = 10;
        $menu->name = 'Main Menu';
        $menu->slug = 'main-menu';
        $menu->count = 5;

        Functions\when('wp_get_nav_menus')->justReturn(array( $menu ));
        Functions\when('get_nav_menu_locations')->justReturn(
            array(
                'primary'   => 10,
                'secondary' => 10,
            )
        );

        $result = $ability->doExecute(array());

        $menu_data = $result['menus'][0];
        $this->assertIsArray($menu_data['locations']);
        $this->assertContains('primary', $menu_data['locations']);
        $this->assertContains('secondary', $menu_data['locations']);
    }

    /**
     * Test execute returns empty array when no menus exist.
     *
     * @return void
     */
    public function testExecuteReturnsEmptyArrayWhenNoMenus(): void
    {
        $ability = new ListMenusAbility();

        Functions\when('wp_get_nav_menus')->justReturn(array());
        Functions\when('get_nav_menu_locations')->justReturn(array());

        $result = $ability->doExecute(array());

        $this->assertIsArray($result['menus']);
        $this->assertEmpty($result['menus']);
        $this->assertEquals(0, $result['total']);
    }

    /**
     * Test annotations are correct for read-only ability.
     *
     * @return void
     */
    public function testGetAnnotations(): void
    {
        $ability     = new ListMenusAbility();
        $annotations = $ability->getAnnotations();

        $this->assertTrue($annotations['readonly']);
        $this->assertFalse($annotations['destructive']);
        $this->assertTrue($annotations['idempotent']);
    }
}
