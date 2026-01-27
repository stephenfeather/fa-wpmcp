<?php

/**
 * Tests for UpdateWidgetAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Widgets
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Widgets;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Widgets\UpdateWidgetAbility;
use FAWpmcp\Exceptions\WidgetNotFoundException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;

/**
 * Test UpdateWidgetAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Widgets
 */
class UpdateWidgetAbilityTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get an instance of the ability being tested.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new UpdateWidgetAbility();
    }

    /**
     * Get expected metadata for the ability.
     *
     * @return array<string, string>
     */
    protected function getExpectedMetadata(): array
    {
        return array(
            'name'                 => 'fa-wpmcp/update-widget',
            'category'             => 'widgets',
            'label'                => 'Update Widget',
            'description_contains' => 'update',
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
        $this->assertArrayHasKey('settings', $schema['properties']);
        $this->assertContains('widget_id', $schema['required']);
        $this->assertContains('settings', $schema['required']);
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
        $this->assertArrayHasKey('settings', $schema['properties']);
    }

    /**
     * Test execute updates widget settings.
     *
     * @return void
     */
    public function testExecuteUpdatesWidgetSettings(): void
    {
        $ability = $this->getAbilityInstance();

        $GLOBALS['wp_registered_widgets'] = array(
            'text-2' => array( 'name' => 'Text' ),
        );

        Functions\when('get_option')->justReturn(
            array(
                2 => array( 'title' => 'Old Title' ),
            )
        );
        Functions\when('update_option')->justReturn(true);

        $new_settings = array( 'title' => 'New Title', 'text' => 'New content' );
        $result       = $ability->doExecute(
            array(
                'widget_id' => 'text-2',
                'settings'  => $new_settings,
            )
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('widget_id', $result);
        $this->assertArrayHasKey('settings', $result);
        $this->assertEquals('text-2', $result['widget_id']);
        $this->assertEquals($new_settings, $result['settings']);

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

        $ability->doExecute(
            array(
                'widget_id' => 'nonexistent',
                'settings'  => array( 'title' => 'Test' ),
            )
        );

        unset($GLOBALS['wp_registered_widgets']);
    }

    /**
     * Test annotations are correct for write ability.
     *
     * @return void
     */
    public function testGetAnnotations(): void
    {
        $ability     = new UpdateWidgetAbility();
        $annotations = $ability->getAnnotations();

        $this->assertFalse($annotations['readonly']);
        $this->assertTrue($annotations['destructive']);
        $this->assertTrue($annotations['idempotent']);
    }
}
