<?php

/**
 * Tests for DeleteMenuAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Menu
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Menu;

use FAWpmcp\Abilities\Menu\DeleteMenuAbility;
use FAWpmcp\Exceptions\MenuNotFoundException;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test DeleteMenuAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Menu
 */
class DeleteMenuAbilityTest extends TestCase
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
        $ability = new DeleteMenuAbility();
        $this->assertEquals('fa-wpmcp/delete-menu', $ability->getName());
    }

    /**
     * Test ability returns correct category.
     *
     * @return void
     */
    public function testGetCategory(): void
    {
        $ability = new DeleteMenuAbility();
        $this->assertEquals('menu', $ability->getCategory());
    }

    /**
     * Test ability returns correct label.
     *
     * @return void
     */
    public function testGetLabel(): void
    {
        $ability = new DeleteMenuAbility();
        $this->assertEquals('Delete Menu', $ability->getLabel());
    }

    /**
     * Test ability returns correct operation type.
     *
     * @return void
     */
    public function testGetOperationType(): void
    {
        $ability = new DeleteMenuAbility();
        $this->assertEquals('write', $ability->getOperationType());
    }

    /**
     * Test ability returns correct required capability.
     *
     * @return void
     */
    public function testGetRequiredCapability(): void
    {
        $ability = new DeleteMenuAbility();
        $this->assertEquals('edit_theme_options', $ability->getRequiredCapability());
    }

    /**
     * Test ability returns input schema with required menu field.
     *
     * @return void
     */
    public function testGetInputSchema(): void
    {
        $ability = new DeleteMenuAbility();
        $schema  = $ability->getInputSchema();

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('type', $schema);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('required', $schema);
        $this->assertArrayHasKey('menu', $schema['properties']);
        $this->assertContains('menu', $schema['required']);
    }

    /**
     * Test ability returns output schema.
     *
     * @return void
     */
    public function testGetOutputSchema(): void
    {
        $ability = new DeleteMenuAbility();
        $schema  = $ability->getOutputSchema();

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('type', $schema);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('deleted', $schema['properties']);
        $this->assertArrayHasKey('menu', $schema['properties']);
    }

    /**
     * Test execute deletes menu by ID successfully.
     *
     * @return void
     */
    public function testExecuteDeletesMenuByIDSuccessfully(): void
    {
        $ability = new DeleteMenuAbility();

        $menu = Mockery::mock('WP_Term');
        $menu->term_id = 10;
        $menu->name = 'Test Menu';

        Functions\when('wp_get_nav_menu_object')->justReturn($menu);
        Functions\when('wp_delete_nav_menu')->justReturn(true);

        $result = $ability->doExecute(array( 'menu' => 10 ));

        $this->assertIsArray($result);
        $this->assertArrayHasKey('deleted', $result);
        $this->assertArrayHasKey('menu', $result);
        $this->assertTrue($result['deleted']);
        $this->assertEquals(10, $result['menu']);
    }

    /**
     * Test execute deletes menu by slug successfully.
     *
     * @return void
     */
    public function testExecuteDeletesMenuBySlugSuccessfully(): void
    {
        $ability = new DeleteMenuAbility();

        $menu = Mockery::mock('WP_Term');
        $menu->term_id = 10;
        $menu->name = 'Test Menu';

        Functions\when('wp_get_nav_menu_object')->justReturn($menu);
        Functions\when('wp_delete_nav_menu')->justReturn(true);

        $result = $ability->doExecute(array( 'menu' => 'test-menu' ));

        $this->assertTrue($result['deleted']);
        $this->assertEquals(10, $result['menu']);
    }

    /**
     * Test execute throws exception when menu not found.
     *
     * @return void
     */
    public function testExecuteThrowsExceptionWhenMenuNotFound(): void
    {
        $ability = new DeleteMenuAbility();

        Functions\when('wp_get_nav_menu_object')->justReturn(false);

        $this->expectException(MenuNotFoundException::class);
        $this->expectExceptionMessage('Menu "nonexistent" not found.');

        $ability->doExecute(array( 'menu' => 'nonexistent' ));
    }

    /**
     * Test execute throws exception when menu ID not found.
     *
     * @return void
     */
    public function testExecuteThrowsExceptionWhenMenuIDNotFound(): void
    {
        $ability = new DeleteMenuAbility();

        Functions\when('wp_get_nav_menu_object')->justReturn(false);

        $this->expectException(MenuNotFoundException::class);
        $this->expectExceptionMessage('Menu "999" not found.');

        $ability->doExecute(array( 'menu' => 999 ));
    }

    /**
     * Test annotations are correct for destructive write ability.
     *
     * @return void
     */
    public function testGetAnnotations(): void
    {
        $ability     = new DeleteMenuAbility();
        $annotations = $ability->getAnnotations();

        $this->assertFalse($annotations['readonly']);
        $this->assertTrue($annotations['destructive']);
        $this->assertFalse($annotations['idempotent']);
    }

    /**
     * Test execute returns menu name in result.
     *
     * @return void
     */
    public function testExecuteReturnsMenuNameInResult(): void
    {
        $ability = new DeleteMenuAbility();

        $menu = Mockery::mock('WP_Term');
        $menu->term_id = 10;
        $menu->name = 'My Custom Menu';

        Functions\when('wp_get_nav_menu_object')->justReturn($menu);
        Functions\when('wp_delete_nav_menu')->justReturn(true);

        $result = $ability->doExecute(array( 'menu' => 10 ));

        $this->assertArrayHasKey('name', $result);
        $this->assertEquals('My Custom Menu', $result['name']);
    }

    /**
     * Test execute handles wp_delete_nav_menu returning WP_Error.
     *
     * @return void
     */
    public function testExecuteHandlesWpError(): void
    {
        $ability = new DeleteMenuAbility();

        $menu = Mockery::mock('WP_Term');
        $menu->term_id = 10;
        $menu->name = 'Test Menu';

        $wp_error = Mockery::mock('WP_Error');
        $wp_error->shouldReceive('get_error_message')
            ->andReturn('Failed to delete menu.');

        Functions\when('wp_get_nav_menu_object')->justReturn($menu);
        Functions\when('wp_delete_nav_menu')->justReturn($wp_error);
        Functions\when('is_wp_error')->justReturn(true);

        $this->expectException(MenuNotFoundException::class);
        $this->expectExceptionMessage('Failed to delete menu: Failed to delete menu.');

        $ability->doExecute(array( 'menu' => 10 ));
    }
}
