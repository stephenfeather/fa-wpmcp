<?php

/**
 * Tests for MoveWidgetAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Widgets
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Widgets;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Widgets\MoveWidgetAbility;
use FAWpmcp\Exceptions\SidebarNotFoundException;
use FAWpmcp\Exceptions\WidgetNotFoundException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;

/**
 * Test MoveWidgetAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Widgets
 */
class MoveWidgetAbilityTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get an instance of the ability being tested.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new MoveWidgetAbility();
    }

    /**
     * Get expected metadata for the ability.
     *
     * @return array<string, string>
     */
    protected function getExpectedMetadata(): array
    {
        return array(
            'name'                 => 'fa-wpmcp/move-widget',
            'category'             => 'widgets',
            'label'                => 'Move Widget',
            'description_contains' => 'move',
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
        $this->assertArrayHasKey('widget_id', $schema['properties']);
        $this->assertArrayHasKey('target_sidebar_id', $schema['properties']);
        $this->assertArrayHasKey('position', $schema['properties']);
        $this->assertContains('widget_id', $schema['required']);
        $this->assertContains('target_sidebar_id', $schema['required']);
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
        $this->assertArrayHasKey('from_sidebar', $schema['properties']);
        $this->assertArrayHasKey('to_sidebar', $schema['properties']);
        $this->assertArrayHasKey('position', $schema['properties']);
    }

    /**
     * Test execute moves widget to new sidebar.
     *
     * @return void
     */
    public function testExecuteMovesWidgetToNewSidebar(): void
    {
        $ability = $this->getAbilityInstance();

        $GLOBALS['wp_registered_sidebars'] = array(
            'sidebar-1' => array( 'name' => 'Sidebar 1' ),
            'sidebar-2' => array( 'name' => 'Sidebar 2' ),
        );

        $GLOBALS['wp_registered_widgets'] = array(
            'text-2' => array( 'name' => 'Text' ),
        );

        Functions\when('wp_get_sidebars_widgets')->justReturn(
            array(
                'sidebar-1' => array( 'text-2' ),
                'sidebar-2' => array(),
            )
        );
        Functions\when('wp_set_sidebars_widgets')->justReturn(true);

        $result = $ability->doExecute(
            array(
                'widget_id'         => 'text-2',
                'target_sidebar_id' => 'sidebar-2',
            )
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('widget_id', $result);
        $this->assertArrayHasKey('from_sidebar', $result);
        $this->assertArrayHasKey('to_sidebar', $result);
        $this->assertArrayHasKey('position', $result);
        $this->assertEquals('text-2', $result['widget_id']);
        $this->assertEquals('sidebar-1', $result['from_sidebar']);
        $this->assertEquals('sidebar-2', $result['to_sidebar']);
        $this->assertEquals(0, $result['position']);

        unset($GLOBALS['wp_registered_sidebars']);
        unset($GLOBALS['wp_registered_widgets']);
    }

    /**
     * Test execute throws exception when widget not found.
     *
     * @return void
     */
    public function testExecuteThrowsExceptionWhenWidgetNotFound(): void
    {
        $ability = $this->getAbilityInstance();

        $GLOBALS['wp_registered_sidebars'] = array(
            'sidebar-1' => array( 'name' => 'Sidebar 1' ),
        );

        $GLOBALS['wp_registered_widgets'] = array();

        $this->expectException(WidgetNotFoundException::class);
        $this->expectExceptionMessage('Widget "nonexistent" not found.');

        $ability->doExecute(
            array(
                'widget_id'         => 'nonexistent',
                'target_sidebar_id' => 'sidebar-1',
            )
        );

        unset($GLOBALS['wp_registered_sidebars']);
        unset($GLOBALS['wp_registered_widgets']);
    }

    /**
     * Test execute throws exception when target sidebar not found.
     *
     * @return void
     */
    public function testExecuteThrowsExceptionWhenTargetSidebarNotFound(): void
    {
        $ability = $this->getAbilityInstance();

        $GLOBALS['wp_registered_sidebars'] = array();

        $GLOBALS['wp_registered_widgets'] = array(
            'text-2' => array( 'name' => 'Text' ),
        );

        $this->expectException(SidebarNotFoundException::class);
        $this->expectExceptionMessage('Sidebar "nonexistent" not found.');

        $ability->doExecute(
            array(
                'widget_id'         => 'text-2',
                'target_sidebar_id' => 'nonexistent',
            )
        );

        unset($GLOBALS['wp_registered_sidebars']);
        unset($GLOBALS['wp_registered_widgets']);
    }

    /**
     * Test execute moves widget to specific position.
     *
     * @return void
     */
    public function testExecuteMovesWidgetToSpecificPosition(): void
    {
        $ability = $this->getAbilityInstance();

        $GLOBALS['wp_registered_sidebars'] = array(
            'sidebar-1' => array( 'name' => 'Sidebar 1' ),
            'sidebar-2' => array( 'name' => 'Sidebar 2' ),
        );

        $GLOBALS['wp_registered_widgets'] = array(
            'text-2' => array( 'name' => 'Text' ),
        );

        Functions\when('wp_get_sidebars_widgets')->justReturn(
            array(
                'sidebar-1' => array( 'text-2' ),
                'sidebar-2' => array( 'search-1', 'archives-1' ),
            )
        );
        Functions\when('wp_set_sidebars_widgets')->justReturn(true);

        $result = $ability->doExecute(
            array(
                'widget_id'         => 'text-2',
                'target_sidebar_id' => 'sidebar-2',
                'position'          => 1,
            )
        );

        $this->assertEquals(1, $result['position']);

        unset($GLOBALS['wp_registered_sidebars']);
        unset($GLOBALS['wp_registered_widgets']);
    }

    /**
     * Test annotations are correct for write ability.
     *
     * @return void
     */
    public function testGetAnnotations(): void
    {
        $ability     = new MoveWidgetAbility();
        $annotations = $ability->getAnnotations();

        $this->assertFalse($annotations['readonly']);
        $this->assertFalse($annotations['destructive']);
        $this->assertTrue($annotations['idempotent']);
    }
}
