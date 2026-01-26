<?php

/**
 * Tests for DeleteMenuAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Menu
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Menu;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Menu\DeleteMenuAbility;
use FAWpmcp\Exceptions\MenuNotFoundException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test DeleteMenuAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Menu
 */
class DeleteMenuAbilityTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get an instance of the ability being tested.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new DeleteMenuAbility();
    }

    /**
     * Get expected metadata for the ability.
     *
     * @return array<string, string>
     */
    protected function getExpectedMetadata(): array
    {
        return array(
            'name'                 => 'fa-wpmcp/delete-menu',
            'category'             => 'menu',
            'label'                => 'Delete Menu',
            'description_contains' => 'delete',
            'operation_type'       => 'write',
            'required_capability'  => 'edit_theme_options',
        );
    }

    /**
     * Test ability returns input schema with required menu field.
     *
     * @return void
     */
    public function testGetInputSchema(): void
    {
        $ability = $this->getAbilityInstance();
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
        $ability = $this->getAbilityInstance();
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
        $ability = $this->getAbilityInstance();

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
        $ability = $this->getAbilityInstance();

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
        $ability = $this->getAbilityInstance();

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
        $ability = $this->getAbilityInstance();

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
        $ability = $this->getAbilityInstance();

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
        $ability = $this->getAbilityInstance();

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
