<?php

/**
 * Tests for ListRewriteRulesAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Rewrite
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Rewrite;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Rewrite\ListRewriteRulesAbility;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;

/**
 * Test ListRewriteRulesAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Rewrite
 */
final class ListRewriteRulesAbilityTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get the ability instance for testing.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new ListRewriteRulesAbility();
    }

    /**
     * Get expected metadata for the ability.
     *
     * @return array<string, mixed>
     */
    protected function getExpectedMetadata(): array
    {
        return [
            'name'                 => 'fa-wpmcp/list-rewrite-rules',
            'category'             => 'rewrite',
            'label'                => 'List Rewrite Rules',
            'description_contains' => 'rewrite',
            'operation_type'       => 'read',
            'required_capability'  => 'manage_options',
        ];
    }

    /**
     * Test execute returns list of rewrite rules.
     *
     * @return void
     */
    public function testExecuteReturnsListOfRewriteRules(): void
    {
        $ability = $this->getAbilityInstance();

        $rules = array(
            'category/(.+?)/?$'       => 'index.php?category_name=$matches[1]',
            'tag/([^/]+)/?$'          => 'index.php?tag=$matches[1]',
            '([^/]+)/?$'              => 'index.php?pagename=$matches[1]',
        );

        Functions\when('get_option')->alias(function ($option) use ($rules) {
            if ($option === 'rewrite_rules') {
                return $rules;
            }
            return false;
        });

        $result = $ability->doExecute(array());

        $this->assertIsArray($result);
        $this->assertArrayHasKey('rules', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertCount(3, $result['rules']);
        $this->assertEquals(3, $result['total']);
    }

    /**
     * Test execute returns rules with correct structure.
     *
     * @return void
     */
    public function testExecuteReturnsRulesWithCorrectStructure(): void
    {
        $ability = $this->getAbilityInstance();

        $rules = array(
            'category/(.+?)/?$' => 'index.php?category_name=$matches[1]',
        );

        Functions\when('get_option')->alias(function ($option) use ($rules) {
            if ($option === 'rewrite_rules') {
                return $rules;
            }
            return false;
        });

        $result = $ability->doExecute(array());

        $this->assertArrayHasKey('pattern', $result['rules'][0]);
        $this->assertArrayHasKey('target', $result['rules'][0]);
        $this->assertEquals('category/(.+?)/?$', $result['rules'][0]['pattern']);
        $this->assertEquals('index.php?category_name=$matches[1]', $result['rules'][0]['target']);
    }

    /**
     * Test execute returns empty array when no rewrite rules exist.
     *
     * @return void
     */
    public function testExecuteReturnsEmptyArrayWhenNoRules(): void
    {
        $ability = $this->getAbilityInstance();

        Functions\when('get_option')->alias(function ($option) {
            if ($option === 'rewrite_rules') {
                return false;
            }
            return false;
        });

        $result = $ability->doExecute(array());

        $this->assertIsArray($result['rules']);
        $this->assertEmpty($result['rules']);
        $this->assertEquals(0, $result['total']);
    }

    /**
     * Test execute handles empty array of rewrite rules.
     *
     * @return void
     */
    public function testExecuteHandlesEmptyArrayOfRules(): void
    {
        $ability = $this->getAbilityInstance();

        Functions\when('get_option')->alias(function ($option) {
            if ($option === 'rewrite_rules') {
                return array();
            }
            return false;
        });

        $result = $ability->doExecute(array());

        $this->assertIsArray($result['rules']);
        $this->assertEmpty($result['rules']);
        $this->assertEquals(0, $result['total']);
    }

    /**
     * Test annotations are correct for read-only ability.
     *
     * @return void
     */
    public function testGetAnnotations(): void
    {
        $ability     = new ListRewriteRulesAbility();
        $annotations = $ability->getAnnotations();

        $this->assertTrue($annotations['readonly']);
        $this->assertFalse($annotations['destructive']);
        $this->assertTrue($annotations['idempotent']);
    }
}
