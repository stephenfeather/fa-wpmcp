<?php

/**
 * Tests for GetPermalinkStructureAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Rewrite
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Rewrite;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Rewrite\GetPermalinkStructureAbility;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;

/**
 * Test GetPermalinkStructureAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Rewrite
 */
final class GetPermalinkStructureAbilityTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get the ability instance for testing.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new GetPermalinkStructureAbility();
    }

    /**
     * Get expected metadata for the ability.
     *
     * @return array<string, mixed>
     */
    protected function getExpectedMetadata(): array
    {
        return [
            'name'                 => 'fa-wpmcp/get-permalink-structure',
            'category'             => 'rewrite',
            'label'                => 'Get Permalink Structure',
            'description_contains' => 'permalink',
            'operation_type'       => 'read',
            'required_capability'  => 'manage_options',
        ];
    }

    /**
     * Test execute returns custom permalink structure.
     *
     * @return void
     */
    public function testExecuteReturnsCustomPermalinkStructure(): void
    {
        $ability = $this->getAbilityInstance();

        Functions\when('get_option')->alias(function ($option) {
            if ($option === 'permalink_structure') {
                return '/%postname%/';
            }
            return false;
        });

        $result = $ability->doExecute(array());

        $this->assertIsArray($result);
        $this->assertArrayHasKey('structure', $result);
        $this->assertArrayHasKey('is_plain', $result);
        $this->assertEquals('/%postname%/', $result['structure']);
        $this->assertFalse($result['is_plain']);
    }

    /**
     * Test execute returns plain permalink structure.
     *
     * @return void
     */
    public function testExecuteReturnsPlainPermalinkStructure(): void
    {
        $ability = $this->getAbilityInstance();

        Functions\when('get_option')->alias(function ($option) {
            if ($option === 'permalink_structure') {
                return '';
            }
            return false;
        });

        $result = $ability->doExecute(array());

        $this->assertEquals('', $result['structure']);
        $this->assertTrue($result['is_plain']);
    }

    /**
     * Test execute handles date-based permalink structure.
     *
     * @return void
     */
    public function testExecuteHandlesDateBasedPermalinkStructure(): void
    {
        $ability = $this->getAbilityInstance();

        Functions\when('get_option')->alias(function ($option) {
            if ($option === 'permalink_structure') {
                return '/%year%/%monthnum%/%day%/%postname%/';
            }
            return false;
        });

        $result = $ability->doExecute(array());

        $this->assertEquals('/%year%/%monthnum%/%day%/%postname%/', $result['structure']);
        $this->assertFalse($result['is_plain']);
    }

    /**
     * Test execute handles numeric permalink structure.
     *
     * @return void
     */
    public function testExecuteHandlesNumericPermalinkStructure(): void
    {
        $ability = $this->getAbilityInstance();

        Functions\when('get_option')->alias(function ($option) {
            if ($option === 'permalink_structure') {
                return '/archives/%post_id%';
            }
            return false;
        });

        $result = $ability->doExecute(array());

        $this->assertEquals('/archives/%post_id%', $result['structure']);
        $this->assertFalse($result['is_plain']);
    }

    /**
     * Test annotations are correct for read-only ability.
     *
     * @return void
     */
    public function testGetAnnotations(): void
    {
        $ability     = new GetPermalinkStructureAbility();
        $annotations = $ability->getAnnotations();

        $this->assertTrue($annotations['readonly']);
        $this->assertFalse($annotations['destructive']);
        $this->assertTrue($annotations['idempotent']);
    }
}
