<?php

/**
 * Tests for GetSidebarAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Widgets
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Widgets;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Widgets\GetSidebarAbility;
use FAWpmcp\Exceptions\SidebarNotFoundException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;

/**
 * Test GetSidebarAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Widgets
 */
class GetSidebarAbilityTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get an instance of the ability being tested.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new GetSidebarAbility();
    }

    /**
     * Get expected metadata for the ability.
     *
     * @return array<string, string>
     */
    protected function getExpectedMetadata(): array
    {
        return array(
            'name'                 => 'fa-wpmcp/get-sidebar',
            'category'             => 'widgets',
            'label'                => 'Get Sidebar',
            'description_contains' => 'sidebar',
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
        $this->assertArrayHasKey('id', $schema['properties']);
        $this->assertArrayHasKey('name', $schema['properties']);
        $this->assertArrayHasKey('description', $schema['properties']);
        $this->assertArrayHasKey('before_widget', $schema['properties']);
        $this->assertArrayHasKey('after_widget', $schema['properties']);
    }

    /**
     * Test execute returns sidebar details.
     *
     * @return void
     */
    public function testExecuteReturnsSidebarDetails(): void
    {
        $ability = $this->getAbilityInstance();

        $GLOBALS['wp_registered_sidebars'] = array(
            'sidebar-1' => array(
                'name'          => 'Primary Sidebar',
                'description'   => 'Main sidebar area',
                'before_widget' => '<div class="widget">',
                'after_widget'  => '</div>',
                'before_title'  => '<h2>',
                'after_title'   => '</h2>',
            ),
        );

        $result = $ability->doExecute(array( 'sidebar_id' => 'sidebar-1' ));

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('description', $result);
        $this->assertArrayHasKey('before_widget', $result);
        $this->assertArrayHasKey('after_widget', $result);
        $this->assertArrayHasKey('before_title', $result);
        $this->assertArrayHasKey('after_title', $result);
        $this->assertEquals('sidebar-1', $result['id']);
        $this->assertEquals('Primary Sidebar', $result['name']);

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

        $this->expectException(SidebarNotFoundException::class);
        $this->expectExceptionMessage('Sidebar "nonexistent" not found.');

        $ability->doExecute(array( 'sidebar_id' => 'nonexistent' ));

        unset($GLOBALS['wp_registered_sidebars']);
    }

    /**
     * Test annotations are correct for read-only ability.
     *
     * @return void
     */
    public function testGetAnnotations(): void
    {
        $ability     = new GetSidebarAbility();
        $annotations = $ability->getAnnotations();

        $this->assertTrue($annotations['readonly']);
        $this->assertFalse($annotations['destructive']);
        $this->assertTrue($annotations['idempotent']);
    }
}
