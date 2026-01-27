<?php

/**
 * Tests for ResetWidgetsAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Widgets
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Widgets;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Widgets\ResetWidgetsAbility;
use FAWpmcp\Exceptions\SidebarNotFoundException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;

/**
 * Test ResetWidgetsAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Widgets
 */
class ResetWidgetsAbilityTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get an instance of the ability being tested.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new ResetWidgetsAbility();
    }

    /**
     * Get expected metadata for the ability.
     *
     * @return array<string, string>
     */
    protected function getExpectedMetadata(): array
    {
        return array(
            'name'                 => 'fa-wpmcp/reset-widgets',
            'category'             => 'widgets',
            'label'                => 'Reset Widgets',
            'description_contains' => 'clear',
            'operation_type'       => 'write',
            'required_capability'  => 'edit_theme_options',
        );
    }

    /**
     * Test ability returns input schema with optional sidebar_id field.
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
        $this->assertArrayHasKey('sidebar_id', $schema['properties']);
        $this->assertArrayNotHasKey('required', $schema);
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
        $this->assertArrayHasKey('reset', $schema['properties']);
        $this->assertArrayHasKey('sidebar_id', $schema['properties']);
        $this->assertArrayHasKey('count', $schema['properties']);
    }

    /**
     * Test execute resets specific sidebar.
     *
     * @return void
     */
    public function testExecuteResetsSpecificSidebar(): void
    {
        $ability = $this->getAbilityInstance();

        $GLOBALS['wp_registered_sidebars'] = array(
            'sidebar-1' => array( 'name' => 'Sidebar 1' ),
        );

        Functions\when('wp_get_sidebars_widgets')->justReturn(
            array(
                'sidebar-1' => array( 'text-2', 'text-3' ),
            )
        );
        Functions\when('wp_set_sidebars_widgets')->justReturn(true);

        $result = $ability->doExecute(array( 'sidebar_id' => 'sidebar-1' ));

        $this->assertIsArray($result);
        $this->assertArrayHasKey('reset', $result);
        $this->assertArrayHasKey('sidebar_id', $result);
        $this->assertArrayHasKey('count', $result);
        $this->assertTrue($result['reset']);
        $this->assertEquals('sidebar-1', $result['sidebar_id']);
        $this->assertEquals(2, $result['count']);

        unset($GLOBALS['wp_registered_sidebars']);
    }

    /**
     * Test execute resets all sidebars when no sidebar_id provided.
     *
     * @return void
     */
    public function testExecuteResetsAllSidebars(): void
    {
        $ability = $this->getAbilityInstance();

        Functions\when('wp_get_sidebars_widgets')->justReturn(
            array(
                'sidebar-1'           => array( 'text-2', 'text-3' ),
                'sidebar-2'           => array( 'search-1' ),
                'wp_inactive_widgets' => array( 'old-widget-1' ),
            )
        );
        Functions\when('wp_set_sidebars_widgets')->justReturn(true);

        $result = $ability->doExecute(array());

        $this->assertIsArray($result);
        $this->assertTrue($result['reset']);
        $this->assertEquals('all', $result['sidebar_id']);
        $this->assertEquals(3, $result['count']); // Excludes wp_inactive_widgets.

        unset($GLOBALS['wp_registered_sidebars']);
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

        Functions\when('wp_get_sidebars_widgets')->justReturn(array());

        $this->expectException(SidebarNotFoundException::class);
        $this->expectExceptionMessage('Sidebar "nonexistent" not found.');

        $ability->doExecute(array( 'sidebar_id' => 'nonexistent' ));

        unset($GLOBALS['wp_registered_sidebars']);
    }

    /**
     * Test annotations are correct for destructive ability.
     *
     * @return void
     */
    public function testGetAnnotations(): void
    {
        $ability     = new ResetWidgetsAbility();
        $annotations = $ability->getAnnotations();

        $this->assertFalse($annotations['readonly']);
        $this->assertTrue($annotations['destructive']);
        $this->assertTrue($annotations['idempotent']);
    }
}
