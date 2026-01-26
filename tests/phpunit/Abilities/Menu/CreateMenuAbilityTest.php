<?php

/**
 * Tests for CreateMenuAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Menu
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Menu;

use FAWpmcp\Abilities\Menu\CreateMenuAbility;
use FAWpmcp\Exceptions\MenuCreationException;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test CreateMenuAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Menu
 */
class CreateMenuAbilityTest extends TestCase
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
        $ability = new CreateMenuAbility();
        $this->assertEquals('fa-wpmcp/create-menu', $ability->getName());
    }

    /**
     * Test ability returns correct category.
     *
     * @return void
     */
    public function testGetCategory(): void
    {
        $ability = new CreateMenuAbility();
        $this->assertEquals('menu', $ability->getCategory());
    }

    /**
     * Test ability returns correct label.
     *
     * @return void
     */
    public function testGetLabel(): void
    {
        $ability = new CreateMenuAbility();
        $this->assertEquals('Create Menu', $ability->getLabel());
    }

    /**
     * Test ability returns correct operation type.
     *
     * @return void
     */
    public function testGetOperationType(): void
    {
        $ability = new CreateMenuAbility();
        $this->assertEquals('write', $ability->getOperationType());
    }

    /**
     * Test ability returns correct required capability.
     *
     * @return void
     */
    public function testGetRequiredCapability(): void
    {
        $ability = new CreateMenuAbility();
        $this->assertEquals('edit_theme_options', $ability->getRequiredCapability());
    }

    /**
     * Test ability returns input schema with required name field.
     *
     * @return void
     */
    public function testGetInputSchema(): void
    {
        $ability = new CreateMenuAbility();
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
        $ability = new CreateMenuAbility();
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
        $ability = new CreateMenuAbility();
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
        $ability = new CreateMenuAbility();

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
        $ability = new CreateMenuAbility();

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
        $ability = new CreateMenuAbility();

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
        $ability = new CreateMenuAbility();

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
        $ability = new CreateMenuAbility();

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
