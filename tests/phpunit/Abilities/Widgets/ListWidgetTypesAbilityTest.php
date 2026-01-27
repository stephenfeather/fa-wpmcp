<?php

/**
 * Tests for ListWidgetTypesAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Widgets
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Widgets;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Widgets\ListWidgetTypesAbility;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use stdClass;

/**
 * Test ListWidgetTypesAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Widgets
 */
class ListWidgetTypesAbilityTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get an instance of the ability being tested.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new ListWidgetTypesAbility();
    }

    /**
     * Get expected metadata for the ability.
     *
     * @return array<string, string>
     */
    protected function getExpectedMetadata(): array
    {
        return array(
            'name'                 => 'fa-wpmcp/list-widget-types',
            'category'             => 'widgets',
            'label'                => 'List Widget Types',
            'description_contains' => 'widget types',
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
        $this->assertArrayHasKey('widget_types', $schema['properties']);
        $this->assertArrayHasKey('total', $schema['properties']);
    }

    /**
     * Test execute returns list of widget types.
     *
     * @return void
     */
    public function testExecuteReturnsListOfWidgetTypes(): void
    {
        $ability = $this->getAbilityInstance();

        // Create mock widget objects.
        $widget1                 = new stdClass();
        $widget1->id_base        = 'text';
        $widget1->name           = 'Text';
        $widget1->widget_options = array( 'description' => 'Arbitrary text or HTML.' );

        $widget2                 = new stdClass();
        $widget2->id_base        = 'search';
        $widget2->name           = 'Search';
        $widget2->widget_options = array( 'description' => 'A search form for your site.' );

        $GLOBALS['wp_widget_factory']          = new stdClass();
        $GLOBALS['wp_widget_factory']->widgets = array( $widget1, $widget2 );

        $result = $ability->doExecute(array());

        $this->assertIsArray($result);
        $this->assertArrayHasKey('widget_types', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertCount(2, $result['widget_types']);
        $this->assertEquals(2, $result['total']);

        unset($GLOBALS['wp_widget_factory']);
    }

    /**
     * Test execute returns widget type structure with expected fields.
     *
     * @return void
     */
    public function testExecuteReturnsWidgetTypeStructureWithExpectedFields(): void
    {
        $ability = $this->getAbilityInstance();

        $widget                 = new stdClass();
        $widget->id_base        = 'text';
        $widget->name           = 'Text';
        $widget->widget_options = array( 'description' => 'Arbitrary text or HTML.' );

        $GLOBALS['wp_widget_factory']          = new stdClass();
        $GLOBALS['wp_widget_factory']->widgets = array( $widget );

        $result = $ability->doExecute(array());

        $widget_type = $result['widget_types'][0];
        $this->assertArrayHasKey('id_base', $widget_type);
        $this->assertArrayHasKey('name', $widget_type);
        $this->assertArrayHasKey('description', $widget_type);
        $this->assertEquals('text', $widget_type['id_base']);
        $this->assertEquals('Text', $widget_type['name']);
        $this->assertEquals('Arbitrary text or HTML.', $widget_type['description']);

        unset($GLOBALS['wp_widget_factory']);
    }

    /**
     * Test execute returns empty array when no widget types exist.
     *
     * @return void
     */
    public function testExecuteReturnsEmptyArrayWhenNoWidgetTypes(): void
    {
        $ability = $this->getAbilityInstance();

        $GLOBALS['wp_widget_factory']          = new stdClass();
        $GLOBALS['wp_widget_factory']->widgets = array();

        $result = $ability->doExecute(array());

        $this->assertIsArray($result['widget_types']);
        $this->assertEmpty($result['widget_types']);
        $this->assertEquals(0, $result['total']);

        unset($GLOBALS['wp_widget_factory']);
    }

    /**
     * Test annotations are correct for read-only ability.
     *
     * @return void
     */
    public function testGetAnnotations(): void
    {
        $ability     = new ListWidgetTypesAbility();
        $annotations = $ability->getAnnotations();

        $this->assertTrue($annotations['readonly']);
        $this->assertFalse($annotations['destructive']);
        $this->assertTrue($annotations['idempotent']);
    }
}
