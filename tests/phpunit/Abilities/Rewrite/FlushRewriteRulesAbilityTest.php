<?php

/**
 * Tests for FlushRewriteRulesAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Rewrite
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Rewrite;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Rewrite\FlushRewriteRulesAbility;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;

/**
 * Test FlushRewriteRulesAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Rewrite
 */
final class FlushRewriteRulesAbilityTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get the ability instance for testing.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new FlushRewriteRulesAbility();
    }

    /**
     * Get expected metadata for the ability.
     *
     * @return array<string, mixed>
     */
    protected function getExpectedMetadata(): array
    {
        return [
            'name'                 => 'fa-wpmcp/flush-rewrite-rules',
            'category'             => 'rewrite',
            'label'                => 'Flush Rewrite Rules',
            'description_contains' => 'flush',
            'operation_type'       => 'write',
            'required_capability'  => 'manage_options',
        ];
    }

    /**
     * Test execute flushes rewrite rules with default hard flush.
     *
     * @return void
     */
    public function testExecuteFlushesRewriteRulesWithHardFlush(): void
    {
        $ability = $this->getAbilityInstance();

        Functions\expect('flush_rewrite_rules')
            ->once()
            ->with(true);

        $result = $ability->doExecute(array());

        $this->assertIsArray($result);
        $this->assertArrayHasKey('flushed', $result);
        $this->assertArrayHasKey('hard', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertTrue($result['flushed']);
        $this->assertTrue($result['hard']);
    }

    /**
     * Test execute can perform soft flush.
     *
     * @return void
     */
    public function testExecuteCanPerformSoftFlush(): void
    {
        $ability = $this->getAbilityInstance();

        Functions\expect('flush_rewrite_rules')
            ->once()
            ->with(false);

        $result = $ability->doExecute(array('hard' => false));

        $this->assertTrue($result['flushed']);
        $this->assertFalse($result['hard']);
    }

    /**
     * Test execute can perform hard flush explicitly.
     *
     * @return void
     */
    public function testExecuteCanPerformHardFlushExplicitly(): void
    {
        $ability = $this->getAbilityInstance();

        Functions\expect('flush_rewrite_rules')
            ->once()
            ->with(true);

        $result = $ability->doExecute(array('hard' => true));

        $this->assertTrue($result['flushed']);
        $this->assertTrue($result['hard']);
    }

    /**
     * Test annotations are correct for write ability.
     *
     * @return void
     */
    public function testGetAnnotations(): void
    {
        $ability     = new FlushRewriteRulesAbility();
        $annotations = $ability->getAnnotations();

        $this->assertFalse($annotations['readonly']);
        $this->assertFalse($annotations['destructive']);
        $this->assertTrue($annotations['idempotent']);
    }

    /**
     * Test input schema includes hard parameter.
     *
     * @return void
     */
    public function testInputSchemaIncludesHardParameter(): void
    {
        $ability = $this->getAbilityInstance();
        $schema  = $ability->getInputSchema();

        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('hard', $schema['properties']);
        $this->assertEquals('boolean', $schema['properties']['hard']['type']);
    }
}
