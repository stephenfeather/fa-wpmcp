<?php

/**
 * Tests for DeleteWidgetAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Widgets
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Widgets;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Widgets\DeleteWidgetAbility;
use FAWpmcp\Exceptions\WidgetNotFoundException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;

/**
 * Test DeleteWidgetAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Widgets
 */
class DeleteWidgetAbilityTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get an instance of the ability being tested.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new DeleteWidgetAbility();
    }

    /**
     * Get expected metadata for the ability.
     *
     * @return array<string, string>
     */
    protected function getExpectedMetadata(): array
    {
        return array(
            'name'                 => 'fa-wpmcp/delete-widget',
            'category'             => 'widgets',
            'label'                => 'Delete Widget',
            'description_contains' => 'remove',
            'operation_type'       => 'write',
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
        $this->assertArrayHasKey('deleted', $schema['properties']);
        $this->assertArrayHasKey('widget_id', $schema['properties']);
    }

    /**
     * Test execute deletes widget.
     *
     * @return void
     */
    public function testExecuteDeletesWidget(): void
    {
        $ability = $this->getAbilityInstance();

        $GLOBALS['wp_registered_widgets'] = array(
            'text-2' => array( 'name' => 'Text' ),
        );

        Functions\when('wp_get_sidebars_widgets')->justReturn(
            array(
                'sidebar-1' => array( 'text-2' ),
            )
        );
        Functions\when('wp_set_sidebars_widgets')->justReturn(true);
        Functions\when('get_option')->justReturn(
            array(
                2 => array( 'title' => 'Test' ),
            )
        );
        Functions\when('update_option')->justReturn(true);

        $result = $ability->doExecute(array( 'widget_id' => 'text-2' ));

        $this->assertIsArray($result);
        $this->assertArrayHasKey('deleted', $result);
        $this->assertArrayHasKey('widget_id', $result);
        $this->assertTrue($result['deleted']);
        $this->assertEquals('text-2', $result['widget_id']);

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
     * Test annotations are correct for destructive ability.
     *
     * @return void
     */
    public function testGetAnnotations(): void
    {
        $ability     = new DeleteWidgetAbility();
        $annotations = $ability->getAnnotations();

        $this->assertFalse($annotations['readonly']);
        $this->assertTrue($annotations['destructive']);
        $this->assertTrue($annotations['idempotent']);
    }
}
