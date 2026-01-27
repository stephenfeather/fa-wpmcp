<?php

/**
 * Tests for ListSidebarsAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Widgets
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Widgets;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Widgets\ListSidebarsAbility;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;

/**
 * Test ListSidebarsAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Widgets
 */
class ListSidebarsAbilityTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get an instance of the ability being tested.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new ListSidebarsAbility();
    }

    /**
     * Get expected metadata for the ability.
     *
     * @return array<string, string>
     */
    protected function getExpectedMetadata(): array
    {
        return array(
            'name'                 => 'fa-wpmcp/list-sidebars',
            'category'             => 'widgets',
            'label'                => 'List Sidebars',
            'description_contains' => 'sidebars',
            'operation_type'       => 'read',
            'required_capability'  => 'edit_theme_options',
        );
    }

    /**
     * Test ability returns input schema.
     *
     * @return void
     */
    public function testGetInputSchema(): void
    {
        $ability = $this->getAbilityInstance();
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
        $ability = $this->getAbilityInstance();
        $schema  = $ability->getOutputSchema();

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('type', $schema);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('sidebars', $schema['properties']);
        $this->assertArrayHasKey('total', $schema['properties']);
    }

    /**
     * Test execute returns list of sidebars.
     *
     * @return void
     */
    public function testExecuteReturnsListOfSidebars(): void
    {
        $ability = $this->getAbilityInstance();

        // Set up global sidebars.
        $GLOBALS['wp_registered_sidebars'] = array(
            'sidebar-1' => array(
                'name'        => 'Primary Sidebar',
                'description' => 'Main sidebar area',
            ),
            'sidebar-2' => array(
                'name'        => 'Footer Widgets',
                'description' => 'Footer widget area',
            ),
        );

        $result = $ability->doExecute(array());

        $this->assertIsArray($result);
        $this->assertArrayHasKey('sidebars', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertCount(2, $result['sidebars']);
        $this->assertEquals(2, $result['total']);

        unset($GLOBALS['wp_registered_sidebars']);
    }

    /**
     * Test execute returns sidebar structure with expected fields.
     *
     * @return void
     */
    public function testExecuteReturnsSidebarStructureWithExpectedFields(): void
    {
        $ability = $this->getAbilityInstance();

        $GLOBALS['wp_registered_sidebars'] = array(
            'sidebar-1' => array(
                'name'        => 'Primary Sidebar',
                'description' => 'Main sidebar area',
            ),
        );

        $result = $ability->doExecute(array());

        $sidebar_data = $result['sidebars'][0];
        $this->assertArrayHasKey('id', $sidebar_data);
        $this->assertArrayHasKey('name', $sidebar_data);
        $this->assertArrayHasKey('description', $sidebar_data);
        $this->assertEquals('sidebar-1', $sidebar_data['id']);
        $this->assertEquals('Primary Sidebar', $sidebar_data['name']);
        $this->assertEquals('Main sidebar area', $sidebar_data['description']);

        unset($GLOBALS['wp_registered_sidebars']);
    }

    /**
     * Test execute returns empty array when no sidebars exist.
     *
     * @return void
     */
    public function testExecuteReturnsEmptyArrayWhenNoSidebars(): void
    {
        $ability = $this->getAbilityInstance();

        $GLOBALS['wp_registered_sidebars'] = array();

        $result = $ability->doExecute(array());

        $this->assertIsArray($result['sidebars']);
        $this->assertEmpty($result['sidebars']);
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
        $ability     = new ListSidebarsAbility();
        $annotations = $ability->getAnnotations();

        $this->assertTrue($annotations['readonly']);
        $this->assertFalse($annotations['destructive']);
        $this->assertTrue($annotations['idempotent']);
    }
}
