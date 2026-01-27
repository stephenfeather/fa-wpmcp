<?php

/**
 * Tests for GetWidgetAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Widgets
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Widgets;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Widgets\GetWidgetAbility;
use FAWpmcp\Exceptions\WidgetNotFoundException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;

/**
 * Test GetWidgetAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Widgets
 */
class GetWidgetAbilityTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get an instance of the ability being tested.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new GetWidgetAbility();
    }

    /**
     * Get expected metadata for the ability.
     *
     * @return array<string, string>
     */
    protected function getExpectedMetadata(): array
    {
        return array(
            'name'                 => 'fa-wpmcp/get-widget',
            'category'             => 'widgets',
            'label'                => 'Get Widget',
            'description_contains' => 'widget',
            'operation_type'       => 'read',
            'required_capability'  => 'edit_theme_options',
        );
    }

    /**
     * Test ability returns input schema with required widget_id field.
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
        $this->assertContains('widget_id', $schema['required']);
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
        $this->assertArrayHasKey('id', $schema['properties']);
        $this->assertArrayHasKey('name', $schema['properties']);
        $this->assertArrayHasKey('id_base', $schema['properties']);
        $this->assertArrayHasKey('instance', $schema['properties']);
        $this->assertArrayHasKey('sidebar_id', $schema['properties']);
        $this->assertArrayHasKey('position', $schema['properties']);
        $this->assertArrayHasKey('settings', $schema['properties']);
    }

    /**
     * Test execute returns widget details.
     *
     * @return void
     */
    public function testExecuteReturnsWidgetDetails(): void
    {
        $ability = $this->getAbilityInstance();

        $GLOBALS['wp_registered_widgets'] = array(
            'text-2' => array( 'name' => 'Text' ),
        );

        Functions\when('get_option')->justReturn(
            array(
                2 => array( 'title' => 'Widget 1', 'text' => 'Hello World' ),
            )
        );

        Functions\when('wp_get_sidebars_widgets')->justReturn(
            array(
                'sidebar-1' => array( 'text-2' ),
            )
        );

        $result = $ability->doExecute(array( 'widget_id' => 'text-2' ));

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('id_base', $result);
        $this->assertArrayHasKey('instance', $result);
        $this->assertArrayHasKey('sidebar_id', $result);
        $this->assertArrayHasKey('position', $result);
        $this->assertArrayHasKey('settings', $result);
        $this->assertEquals('text-2', $result['id']);
        $this->assertEquals('Text', $result['name']);
        $this->assertEquals('text', $result['id_base']);
        $this->assertEquals(2, $result['instance']);
        $this->assertEquals('sidebar-1', $result['sidebar_id']);
        $this->assertEquals(0, $result['position']);

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

        $GLOBALS['wp_registered_widgets'] = array();

        $this->expectException(WidgetNotFoundException::class);
        $this->expectExceptionMessage('Widget "nonexistent" not found.');

        $ability->doExecute(array( 'widget_id' => 'nonexistent' ));

        unset($GLOBALS['wp_registered_widgets']);
    }

    /**
     * Test execute returns widget not in any sidebar.
     *
     * @return void
     */
    public function testExecuteReturnsWidgetNotInAnySidebar(): void
    {
        $ability = $this->getAbilityInstance();

        $GLOBALS['wp_registered_widgets'] = array(
            'text-2' => array( 'name' => 'Text' ),
        );

        Functions\when('get_option')->justReturn(
            array(
                2 => array( 'title' => 'Inactive Widget' ),
            )
        );

        Functions\when('wp_get_sidebars_widgets')->justReturn(
            array(
                'sidebar-1' => array(),
            )
        );

        $result = $ability->doExecute(array( 'widget_id' => 'text-2' ));

        $this->assertEquals('', $result['sidebar_id']);
        $this->assertEquals(-1, $result['position']);

        unset($GLOBALS['wp_registered_widgets']);
    }

    /**
     * Test annotations are correct for read-only ability.
     *
     * @return void
     */
    public function testGetAnnotations(): void
    {
        $ability     = new GetWidgetAbility();
        $annotations = $ability->getAnnotations();

        $this->assertTrue($annotations['readonly']);
        $this->assertFalse($annotations['destructive']);
        $this->assertTrue($annotations['idempotent']);
    }
}
