<?php

/**
 * Tests for ListWidgetsAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Widgets
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Widgets;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Widgets\ListWidgetsAbility;
use FAWpmcp\Exceptions\SidebarNotFoundException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;

/**
 * Test ListWidgetsAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Widgets
 */
class ListWidgetsAbilityTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get an instance of the ability being tested.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new ListWidgetsAbility();
    }

    /**
     * Get expected metadata for the ability.
     *
     * @return array<string, string>
     */
    protected function getExpectedMetadata(): array
    {
        return array(
            'name'                 => 'fa-wpmcp/list-widgets',
            'category'             => 'widgets',
            'label'                => 'List Widgets',
            'description_contains' => 'widgets',
            'operation_type'       => 'read',
            'required_capability'  => 'edit_theme_options',
        );
    }

    /**
     * Test ability returns input schema with required sidebar_id field.
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
        $this->assertArrayHasKey('sidebar_id', $schema['properties']);
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
        $this->assertArrayHasKey('widgets', $schema['properties']);
        $this->assertArrayHasKey('total', $schema['properties']);
    }

    /**
     * Test execute returns list of widgets.
     *
     * @return void
     */
    public function testExecuteReturnsListOfWidgets(): void
    {
        $ability = $this->getAbilityInstance();

        $GLOBALS['wp_registered_sidebars'] = array(
            'sidebar-1' => array( 'name' => 'Primary Sidebar' ),
        );

        $GLOBALS['wp_registered_widgets'] = array(
            'text-2' => array( 'name' => 'Text' ),
            'text-3' => array( 'name' => 'Text' ),
        );

        Functions\when('wp_get_sidebars_widgets')->justReturn(
            array(
                'sidebar-1' => array( 'text-2', 'text-3' ),
            )
        );

        Functions\when('get_option')->alias(
            function ($option) {
                if ('widget_text' === $option) {
                    return array(
                        2 => array( 'title' => 'Widget 1' ),
                        3 => array( 'title' => 'Widget 2' ),
                    );
                }
                return false;
            }
        );

        $result = $ability->doExecute(array( 'sidebar_id' => 'sidebar-1' ));

        $this->assertIsArray($result);
        $this->assertArrayHasKey('widgets', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertCount(2, $result['widgets']);
        $this->assertEquals(2, $result['total']);

        unset($GLOBALS['wp_registered_sidebars']);
        unset($GLOBALS['wp_registered_widgets']);
    }

    /**
     * Test execute returns widget structure with expected fields.
     *
     * @return void
     */
    public function testExecuteReturnsWidgetStructureWithExpectedFields(): void
    {
        $ability = $this->getAbilityInstance();

        $GLOBALS['wp_registered_sidebars'] = array(
            'sidebar-1' => array( 'name' => 'Primary Sidebar' ),
        );

        $GLOBALS['wp_registered_widgets'] = array(
            'text-2' => array( 'name' => 'Text' ),
        );

        Functions\when('wp_get_sidebars_widgets')->justReturn(
            array(
                'sidebar-1' => array( 'text-2' ),
            )
        );

        Functions\when('get_option')->justReturn(
            array(
                2 => array( 'title' => 'Widget 1', 'text' => 'Hello' ),
            )
        );

        $result = $ability->doExecute(array( 'sidebar_id' => 'sidebar-1' ));

        $widget_data = $result['widgets'][0];
        $this->assertArrayHasKey('id', $widget_data);
        $this->assertArrayHasKey('name', $widget_data);
        $this->assertArrayHasKey('id_base', $widget_data);
        $this->assertArrayHasKey('position', $widget_data);
        $this->assertArrayHasKey('settings', $widget_data);
        $this->assertEquals('text-2', $widget_data['id']);
        $this->assertEquals('Text', $widget_data['name']);
        $this->assertEquals('text', $widget_data['id_base']);
        $this->assertEquals(0, $widget_data['position']);

        unset($GLOBALS['wp_registered_sidebars']);
        unset($GLOBALS['wp_registered_widgets']);
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

        $ability->doExecute(array( 'sidebar_id' => 'nonexistent' ));

        unset($GLOBALS['wp_registered_sidebars']);
    }

    /**
     * Test execute returns empty array when no widgets in sidebar.
     *
     * @return void
     */
    public function testExecuteReturnsEmptyArrayWhenNoWidgetsInSidebar(): void
    {
        $ability = $this->getAbilityInstance();

        $GLOBALS['wp_registered_sidebars'] = array(
            'sidebar-1' => array( 'name' => 'Primary Sidebar' ),
        );

        Functions\when('wp_get_sidebars_widgets')->justReturn(
            array(
                'sidebar-1' => array(),
            )
        );

        $result = $ability->doExecute(array( 'sidebar_id' => 'sidebar-1' ));

        $this->assertIsArray($result['widgets']);
        $this->assertEmpty($result['widgets']);
        $this->assertEquals(0, $result['total']);

        unset($GLOBALS['wp_registered_sidebars']);
    }

    /**
     * Test annotations are correct for read-only ability.
     *
     * @return void
     */
    public function testGetAnnotations(): void
    {
        $ability     = new ListWidgetsAbility();
        $annotations = $ability->getAnnotations();

        $this->assertTrue($annotations['readonly']);
        $this->assertFalse($annotations['destructive']);
        $this->assertTrue($annotations['idempotent']);
    }
}
