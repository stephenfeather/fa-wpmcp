<?php

/**
 * Tests for ListConfigConstantsAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Config
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Config;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Config\ListConfigConstantsAbility;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;

/**
 * Test ListConfigConstantsAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Config
 */
class ListConfigConstantsAbilityTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get the ability instance to test.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new ListConfigConstantsAbility();
    }

    /**
     * Get the expected metadata for this ability.
     *
     * @return array<string, mixed>
     */
    protected function getExpectedMetadata(): array
    {
        return array(
            'name'                 => 'fa-wpmcp/list-config-constants',
            'category'             => 'config',
            'label'                => 'List Config Constants',
            'description_contains' => 'constant',
            'operation_type'       => 'read',
            'required_capability'  => 'manage_options',
        );
    }

    /**
     * Test ability returns input schema with category property.
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
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('category', $schema['properties']);
        $this->assertArrayHasKey('enum', $schema['properties']['category']);
    }

    /**
     * Test ability returns output schema with constants array.
     *
     * @return void
     */
    public function testGetOutputSchema(): void
    {
        $ability = $this->getAbilityInstance();
        $schema  = $ability->getOutputSchema();

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('constants', $schema['properties']);
        $this->assertArrayHasKey('total', $schema['properties']);
        $this->assertEquals('array', $schema['properties']['constants']['type']);
        $this->assertEquals('integer', $schema['properties']['total']['type']);
    }

    /**
     * Test execute returns constants list.
     *
     * @return void
     */
    public function testExecuteReturnsConstantsList(): void
    {
        $ability = $this->getAbilityInstance();

        // Define test constants if not already defined.
        if (! defined('WP_DEBUG')) {
            define('WP_DEBUG', true);
        }
        if (! defined('ABSPATH')) {
            define('ABSPATH', '/var/www/html/');
        }

        // json_encode is used directly, no mock needed.

        $result = $ability->doExecute(array());

        $this->assertIsArray($result);
        $this->assertArrayHasKey('constants', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertIsArray($result['constants']);
        $this->assertIsInt($result['total']);
        $this->assertEquals(count($result['constants']), $result['total']);
    }

    /**
     * Test execute filters by category.
     *
     * @return void
     */
    public function testExecuteFiltersByCategory(): void
    {
        $ability = $this->getAbilityInstance();

        // Ensure debug constants are defined.
        if (! defined('WP_DEBUG')) {
            define('WP_DEBUG', true);
        }

        // json_encode is used directly, no mock needed.

        $result = $ability->doExecute(array('category' => 'debug'));

        $this->assertIsArray($result);

        // All returned constants should be in the debug category.
        foreach ($result['constants'] as $constant) {
            $this->assertEquals('debug', $constant['category']);
        }
    }

    /**
     * Test constant value formatting for boolean.
     *
     * @return void
     */
    public function testFormatsBooleansCorrectly(): void
    {
        $ability = $this->getAbilityInstance();

        if (! defined('WP_DEBUG')) {
            define('WP_DEBUG', true);
        }

        // json_encode is used directly, no mock needed.

        $result = $ability->doExecute(array('category' => 'debug'));

        // Find WP_DEBUG in results.
        $wpDebug = null;
        foreach ($result['constants'] as $constant) {
            if ($constant['name'] === 'WP_DEBUG') {
                $wpDebug = $constant;
                break;
            }
        }

        if ($wpDebug !== null) {
            $this->assertEquals('boolean', $wpDebug['type']);
            $this->assertContains($wpDebug['value'], array('true', 'false'));
        }
    }

    /**
     * Test constants are sorted alphabetically.
     *
     * @return void
     */
    public function testConstantsAreSorted(): void
    {
        $ability = $this->getAbilityInstance();

        // json_encode is used directly, no mock needed.

        $result = $ability->doExecute(array());

        if (count($result['constants']) > 1) {
            $names = array_column($result['constants'], 'name');
            $sortedNames = $names;
            sort($sortedNames);
            $this->assertEquals($sortedNames, $names);
        }
    }

    /**
     * Test annotations are correct for read-only ability.
     *
     * @return void
     */
    public function testGetAnnotations(): void
    {
        $ability     = new ListConfigConstantsAbility();
        $annotations = $ability->getAnnotations();

        $this->assertTrue($annotations['readonly']);
        $this->assertFalse($annotations['destructive']);
        $this->assertTrue($annotations['idempotent']);
    }
}
