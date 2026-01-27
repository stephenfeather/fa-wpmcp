<?php

/**
 * Tests for UpdatePermalinkStructureAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Rewrite
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Rewrite;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Rewrite\UpdatePermalinkStructureAbility;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;

/**
 * Test UpdatePermalinkStructureAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Rewrite
 */
final class UpdatePermalinkStructureAbilityTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get the ability instance for testing.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new UpdatePermalinkStructureAbility();
    }

    /**
     * Get expected metadata for the ability.
     *
     * @return array<string, mixed>
     */
    protected function getExpectedMetadata(): array
    {
        return [
            'name'                 => 'fa-wpmcp/update-permalink-structure',
            'category'             => 'rewrite',
            'label'                => 'Update Permalink Structure',
            'description_contains' => 'permalink',
            'operation_type'       => 'write',
            'required_capability'  => 'manage_options',
        ];
    }

    /**
     * Test execute updates permalink structure and flushes rules.
     *
     * @return void
     */
    public function testExecuteUpdatesPermalinkStructureAndFlushesRules(): void
    {
        $ability = $this->getAbilityInstance();

        Functions\expect('update_option')
            ->once()
            ->with('permalink_structure', '/%postname%/')
            ->andReturn(true);

        Functions\expect('flush_rewrite_rules')
            ->once()
            ->with(true);

        $result = $ability->doExecute(array('structure' => '/%postname%/'));

        $this->assertIsArray($result);
        $this->assertArrayHasKey('updated', $result);
        $this->assertArrayHasKey('structure', $result);
        $this->assertArrayHasKey('flushed', $result);
        $this->assertTrue($result['updated']);
        $this->assertEquals('/%postname%/', $result['structure']);
        $this->assertTrue($result['flushed']);
    }

    /**
     * Test execute can set plain permalink structure.
     *
     * @return void
     */
    public function testExecuteCanSetPlainPermalinkStructure(): void
    {
        $ability = $this->getAbilityInstance();

        Functions\expect('update_option')
            ->once()
            ->with('permalink_structure', '')
            ->andReturn(true);

        Functions\expect('flush_rewrite_rules')
            ->once()
            ->with(true);

        $result = $ability->doExecute(array('structure' => ''));

        $this->assertTrue($result['updated']);
        $this->assertEquals('', $result['structure']);
        $this->assertTrue($result['flushed']);
    }

    /**
     * Test execute handles update failure.
     *
     * @return void
     */
    public function testExecuteHandlesUpdateFailure(): void
    {
        $ability = $this->getAbilityInstance();

        Functions\expect('update_option')
            ->once()
            ->with('permalink_structure', '/%postname%/')
            ->andReturn(false);

        // flush_rewrite_rules should still be called even on "failure"
        // (update_option returns false if the value didn't change)
        Functions\expect('flush_rewrite_rules')
            ->once()
            ->with(true);

        $result = $ability->doExecute(array('structure' => '/%postname%/'));

        // When update_option returns false, it may mean the value was unchanged
        $this->assertArrayHasKey('updated', $result);
        $this->assertArrayHasKey('structure', $result);
    }

    /**
     * Test annotations are correct for write ability.
     *
     * @return void
     */
    public function testGetAnnotations(): void
    {
        $ability     = new UpdatePermalinkStructureAbility();
        $annotations = $ability->getAnnotations();

        $this->assertFalse($annotations['readonly']);
        $this->assertFalse($annotations['destructive']);
        $this->assertTrue($annotations['idempotent']);
    }

    /**
     * Test input schema requires structure parameter.
     *
     * @return void
     */
    public function testInputSchemaRequiresStructureParameter(): void
    {
        $ability = $this->getAbilityInstance();
        $schema  = $ability->getInputSchema();

        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('structure', $schema['properties']);
        $this->assertEquals('string', $schema['properties']['structure']['type']);
        $this->assertArrayHasKey('required', $schema);
        $this->assertContains('structure', $schema['required']);
    }

    /**
     * Test execute with date-based structure.
     *
     * @return void
     */
    public function testExecuteWithDateBasedStructure(): void
    {
        $ability = $this->getAbilityInstance();

        $structure = '/%year%/%monthnum%/%day%/%postname%/';

        Functions\expect('update_option')
            ->once()
            ->with('permalink_structure', $structure)
            ->andReturn(true);

        Functions\expect('flush_rewrite_rules')
            ->once()
            ->with(true);

        $result = $ability->doExecute(array('structure' => $structure));

        $this->assertTrue($result['updated']);
        $this->assertEquals($structure, $result['structure']);
    }
}
