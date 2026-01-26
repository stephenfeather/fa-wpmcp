<?php

/**
 * Tests for CreateMenuAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Menu
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Menu;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Menu\CreateMenuAbility;
use FAWpmcp\Exceptions\MenuCreationException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test CreateMenuAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Menu
 */
class CreateMenuAbilityTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get an instance of the ability being tested.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new CreateMenuAbility();
    }

    /**
     * Get expected metadata for the ability.
     *
     * @return array<string, string>
     */
    protected function getExpectedMetadata(): array
    {
        return array(
            'name'                 => 'fa-wpmcp/create-menu',
            'category'             => 'menu',
            'label'                => 'Create Menu',
            'description_contains' => 'create',
            'operation_type'       => 'write',
            'required_capability'  => 'edit_theme_options',
        );
    }

    /**
     * Test ability returns input schema with required name field.
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
        $this->assertArrayHasKey('name', $schema['properties']);
        $this->assertContains('name', $schema['required']);
    }

    /**
     * Test input schema has optional location field.
     *
     * @return void
     */
    public function testGetInputSchemaHasLocationField(): void
    {
        $ability = $this->getAbilityInstance();
        $schema  = $ability->getInputSchema();

        $this->assertArrayHasKey('location', $schema['properties']);
        $this->assertNotContains('location', $schema['required']);
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
        $this->assertArrayHasKey('term_id', $schema['properties']);
        $this->assertArrayHasKey('name', $schema['properties']);
        $this->assertArrayHasKey('slug', $schema['properties']);
    }

    /**
     * Test execute creates menu successfully.
     *
     * @return void
     */
    public function testExecuteCreatesMenuSuccessfully(): void
    {
        $ability = $this->getAbilityInstance();

        // Mock the created menu.
        $menu = Mockery::mock('WP_Term');
        $menu->term_id = 10;
        $menu->name = 'New Menu';
        $menu->slug = 'new-menu';

        Functions\when('wp_create_nav_menu')->justReturn(10);
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('wp_get_nav_menu_object')->justReturn($menu);

        $result = $ability->doExecute(array( 'name' => 'New Menu' ));

        $this->assertIsArray($result);
        $this->assertArrayHasKey('term_id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('slug', $result);
        $this->assertEquals(10, $result['term_id']);
        $this->assertEquals('New Menu', $result['name']);
        $this->assertEquals('new-menu', $result['slug']);
    }

    /**
     * Test execute creates menu and assigns location.
     *
     * @return void
     */
    public function testExecuteCreatesMenuAndAssignsLocation(): void
    {
        $ability = $this->getAbilityInstance();

        $menu = Mockery::mock('WP_Term');
        $menu->term_id = 10;
        $menu->name = 'New Menu';
        $menu->slug = 'new-menu';

        Functions\when('wp_create_nav_menu')->justReturn(10);
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('wp_get_nav_menu_object')->justReturn($menu);
        Functions\when('get_nav_menu_locations')->justReturn(array());

        // Expect set_theme_mod to be called with the location.
        Functions\expect('set_theme_mod')
            ->once()
            ->with('nav_menu_locations', array( 'primary' => 10 ));

        $result = $ability->doExecute(
            array(
                'name'     => 'New Menu',
                'location' => 'primary',
            )
        );

        $this->assertArrayHasKey('location', $result);
        $this->assertEquals('primary', $result['location']);
    }

    /**
     * Test execute throws exception when menu creation fails.
     *
     * @return void
     */
    public function testExecuteThrowsExceptionWhenCreationFails(): void
    {
        $ability = $this->getAbilityInstance();

        $wp_error = Mockery::mock('WP_Error');
        $wp_error->shouldReceive('get_error_message')
            ->andReturn('A menu with that name already exists.');

        Functions\when('wp_create_nav_menu')->justReturn($wp_error);
        Functions\when('is_wp_error')->justReturn(true);

        $this->expectException(MenuCreationException::class);
        $this->expectExceptionMessage('Failed to create menu: A menu with that name already exists.');

        $ability->doExecute(array( 'name' => 'Existing Menu' ));
    }

    /**
     * Test annotations are correct for write ability.
     *
     * @return void
     */
    public function testGetAnnotations(): void
    {
        $ability     = new CreateMenuAbility();
        $annotations = $ability->getAnnotations();

        $this->assertFalse($annotations['readonly']);
        $this->assertFalse($annotations['destructive']);
        $this->assertFalse($annotations['idempotent']);
    }

    /**
     * Test execute does not assign location when not provided.
     *
     * @return void
     */
    public function testExecuteDoesNotAssignLocationWhenNotProvided(): void
    {
        $ability = $this->getAbilityInstance();

        $menu = Mockery::mock('WP_Term');
        $menu->term_id = 10;
        $menu->name = 'New Menu';
        $menu->slug = 'new-menu';

        Functions\when('wp_create_nav_menu')->justReturn(10);
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('wp_get_nav_menu_object')->justReturn($menu);

        // set_theme_mod should not be called.
        Functions\expect('set_theme_mod')->never();

        $result = $ability->doExecute(array( 'name' => 'New Menu' ));

        $this->assertArrayNotHasKey('location', $result);
    }

    /**
     * Test execute preserves existing menu locations when assigning new one.
     *
     * @return void
     */
    public function testExecutePreservesExistingLocationsWhenAssigningNew(): void
    {
        $ability = $this->getAbilityInstance();

        $menu = Mockery::mock('WP_Term');
        $menu->term_id = 10;
        $menu->name = 'New Menu';
        $menu->slug = 'new-menu';

        Functions\when('wp_create_nav_menu')->justReturn(10);
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('wp_get_nav_menu_object')->justReturn($menu);
        Functions\when('get_nav_menu_locations')->justReturn(
            array(
                'secondary' => 5,
                'footer'    => 6,
            )
        );

        // Expect set_theme_mod to preserve existing locations.
        Functions\expect('set_theme_mod')
            ->once()
            ->with(
                'nav_menu_locations',
                array(
                    'secondary' => 5,
                    'footer'    => 6,
                    'primary'   => 10,
                )
            );

        $result = $ability->doExecute(
            array(
                'name'     => 'New Menu',
                'location' => 'primary',
            )
        );

        $this->assertEquals('primary', $result['location']);
    }
}
