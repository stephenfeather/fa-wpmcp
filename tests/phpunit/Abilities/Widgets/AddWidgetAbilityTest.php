<?php

/**
 * Tests for AddWidgetAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Widgets
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Widgets;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Widgets\AddWidgetAbility;
use FAWpmcp\Exceptions\SidebarNotFoundException;
use FAWpmcp\Exceptions\WidgetTypeNotFoundException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;
use stdClass;

/**
 * Test AddWidgetAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Widgets
 */
class AddWidgetAbilityTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get an instance of the ability being tested.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new AddWidgetAbility();
    }

    /**
     * Get expected metadata for the ability.
     *
     * @return array<string, string>
     */
    protected function getExpectedMetadata(): array
    {
        return array(
            'name'                 => 'fa-wpmcp/add-widget',
            'category'             => 'widgets',
            'label'                => 'Add Widget',
            'description_contains' => 'add',
            'operation_type'       => 'write',
            'required_capability'  => 'edit_theme_options',
        );
    }

    /**
     * Test ability returns input schema with required fields.
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
        $this->assertArrayHasKey('widget_type', $schema['properties']);
        $this->assertArrayHasKey('sidebar_id', $schema['properties']);
        $this->assertArrayHasKey('settings', $schema['properties']);
        $this->assertArrayHasKey('position', $schema['properties']);
        $this->assertContains('widget_type', $schema['required']);
        $this->assertContains('sidebar_id', $schema['required']);
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
        $this->assertArrayHasKey('widget_id', $schema['properties']);
        $this->assertArrayHasKey('sidebar_id', $schema['properties']);
        $this->assertArrayHasKey('position', $schema['properties']);
    }

    /**
     * Test execute adds widget to sidebar.
     *
     * @return void
     */
    public function testExecuteAddsWidgetToSidebar(): void
    {
        $ability = $this->getAbilityInstance();

        $GLOBALS['wp_registered_sidebars'] = array(
            'sidebar-1' => array( 'name' => 'Primary Sidebar' ),
        );

        $widget          = new stdClass();
        $widget->id_base = 'text';
        $widget->name    = 'Text';

        $GLOBALS['wp_widget_factory']          = new stdClass();
        $GLOBALS['wp_widget_factory']->widgets = array( $widget );

        Functions\when('get_option')->justReturn(array());
        Functions\when('update_option')->justReturn(true);
        Functions\when('wp_get_sidebars_widgets')->justReturn(
            array(
                'sidebar-1' => array(),
            )
        );
        Functions\when('wp_set_sidebars_widgets')->justReturn(true);

        $result = $ability->doExecute(
            array(
                'widget_type' => 'text',
                'sidebar_id'  => 'sidebar-1',
                'settings'    => array( 'title' => 'Test' ),
            )
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('widget_id', $result);
        $this->assertArrayHasKey('sidebar_id', $result);
        $this->assertArrayHasKey('position', $result);
        $this->assertEquals('text-2', $result['widget_id']);
        $this->assertEquals('sidebar-1', $result['sidebar_id']);
        $this->assertEquals(0, $result['position']);

        unset($GLOBALS['wp_registered_sidebars']);
        unset($GLOBALS['wp_widget_factory']);
    }

    /**
     * Test execute throws exception when sidebar not found.
     *
     * @return void
     */
    public function testExecuteThrowsExceptionWhenSidebarNotFound(): void
    {
        $ability = $this->getAbilityInstance();

        $GLOBALS['wp_registered_sidebars'] = array();

        $this->expectException(SidebarNotFoundException::class);
        $this->expectExceptionMessage('Sidebar "nonexistent" not found.');

        $ability->doExecute(
            array(
                'widget_type' => 'text',
                'sidebar_id'  => 'nonexistent',
            )
        );

        unset($GLOBALS['wp_registered_sidebars']);
    }

    /**
     * Test execute throws exception when widget type not found.
     *
     * @return void
     */
    public function testExecuteThrowsExceptionWhenWidgetTypeNotFound(): void
    {
        $ability = $this->getAbilityInstance();

        $GLOBALS['wp_registered_sidebars'] = array(
            'sidebar-1' => array( 'name' => 'Primary Sidebar' ),
        );

        $GLOBALS['wp_widget_factory']          = new stdClass();
        $GLOBALS['wp_widget_factory']->widgets = array();

        $this->expectException(WidgetTypeNotFoundException::class);
        $this->expectExceptionMessage('Widget type "nonexistent" not found.');

        $ability->doExecute(
            array(
                'widget_type' => 'nonexistent',
                'sidebar_id'  => 'sidebar-1',
            )
        );

        unset($GLOBALS['wp_registered_sidebars']);
        unset($GLOBALS['wp_widget_factory']);
    }

    /**
     * Test execute inserts widget at specific position.
     *
     * @return void
     */
    public function testExecuteInsertsWidgetAtSpecificPosition(): void
    {
        $ability = $this->getAbilityInstance();

        $GLOBALS['wp_registered_sidebars'] = array(
            'sidebar-1' => array( 'name' => 'Primary Sidebar' ),
        );

        $widget          = new stdClass();
        $widget->id_base = 'text';
        $widget->name    = 'Text';

        $GLOBALS['wp_widget_factory']          = new stdClass();
        $GLOBALS['wp_widget_factory']->widgets = array( $widget );

        Functions\when('get_option')->justReturn(array());
        Functions\when('update_option')->justReturn(true);
        Functions\when('wp_get_sidebars_widgets')->justReturn(
            array(
                'sidebar-1' => array( 'search-1', 'archives-1' ),
            )
        );
        Functions\when('wp_set_sidebars_widgets')->justReturn(true);

        $result = $ability->doExecute(
            array(
                'widget_type' => 'text',
                'sidebar_id'  => 'sidebar-1',
                'settings'    => array( 'title' => 'Test' ),
                'position'    => 1,
            )
        );

        $this->assertEquals(1, $result['position']);

        unset($GLOBALS['wp_registered_sidebars']);
        unset($GLOBALS['wp_widget_factory']);
    }

    /**
     * Test annotations are correct for write ability.
     *
     * @return void
     */
    public function testGetAnnotations(): void
    {
        $ability     = new AddWidgetAbility();
        $annotations = $ability->getAnnotations();

        $this->assertFalse($annotations['readonly']);
        $this->assertFalse($annotations['destructive']);
        $this->assertFalse($annotations['idempotent']);
    }
}
