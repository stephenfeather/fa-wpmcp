<?php

/**
 * Tests for FlushCache.
 *
 * @package FAWpmcp\Tests\Abilities\Cache
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Cache;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Cache\FlushCache;
use FAWpmcp\Exceptions\CacheOperationException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;

final class FlushCacheTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    protected function getAbilityInstance(): AbstractAbility
    {
        return new FlushCache();
    }

    protected function getExpectedMetadata(): array
    {
        return [
            'name'                 => 'fa-wpmcp/flush-cache',
            'category'             => 'cache',
            'label'                => 'Flush Cache',
            'description_contains' => 'flush',
            'operation_type'       => 'write',
            'required_capability'  => 'manage_options',
        ];
    }

    public function test_annotations_mark_idempotent(): void
    {
        $ability     = $this->getAbilityInstance();
        $annotations = $ability->getAnnotations();

        $this->assertTrue($annotations['idempotent']);
    }

    public function test_input_schema_has_optional_group(): void
    {
        $ability = $this->getAbilityInstance();
        $schema  = $ability->getInputSchema();

        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('group', $schema['properties']);
        $this->assertArrayNotHasKey('required', $schema);
    }

    public function test_output_schema_structure(): void
    {
        $ability = $this->getAbilityInstance();
        $schema  = $ability->getOutputSchema();

        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('flushed', $schema['properties']);
        $this->assertArrayHasKey('group', $schema['properties']);
        $this->assertArrayHasKey('message', $schema['properties']);
    }

    public function test_flushes_entire_cache_without_group(): void
    {
        Functions\expect('wp_cache_flush')->once()->andReturn(true);

        $ability = $this->getAbilityInstance();
        $result  = $ability->doExecute(array());

        $this->assertTrue($result['flushed']);
        $this->assertEquals('all', $result['group']);
        $this->assertStringContainsString('Entire cache flushed', $result['message']);
    }

    public function test_flushes_specific_group_when_supported(): void
    {
        Functions\expect('wp_cache_supports')->once()->with('flush_group')->andReturn(true);
        Functions\expect('wp_cache_flush_group')->once()->with('posts')->andReturn(true);

        $ability = $this->getAbilityInstance();
        $result  = $ability->doExecute(array( 'group' => 'posts' ));

        $this->assertTrue($result['flushed']);
        $this->assertEquals('posts', $result['group']);
        $this->assertStringContainsString('posts', $result['message']);
    }

    public function test_throws_exception_when_group_flush_not_supported(): void
    {
        $this->expectException(CacheOperationException::class);
        $this->expectExceptionMessage('does not support group flushing');

        Functions\expect('wp_cache_supports')->once()->with('flush_group')->andReturn(false);

        $ability = $this->getAbilityInstance();
        $ability->doExecute(array( 'group' => 'posts' ));
    }

    public function test_throws_exception_when_flush_fails(): void
    {
        $this->expectException(CacheOperationException::class);
        $this->expectExceptionMessage('Failed to flush cache');

        Functions\expect('wp_cache_flush')->once()->andReturn(false);

        $ability = $this->getAbilityInstance();
        $ability->doExecute(array());
    }

    public function test_throws_exception_when_group_flush_fails(): void
    {
        $this->expectException(CacheOperationException::class);
        $this->expectExceptionMessage("Failed to flush cache group 'posts'");

        Functions\expect('wp_cache_supports')->once()->with('flush_group')->andReturn(true);
        Functions\expect('wp_cache_flush_group')->once()->with('posts')->andReturn(false);

        $ability = $this->getAbilityInstance();
        $ability->doExecute(array( 'group' => 'posts' ));
    }

    public function test_handles_empty_group_string_as_full_flush(): void
    {
        Functions\expect('wp_cache_flush')->once()->andReturn(true);

        $ability = $this->getAbilityInstance();
        $result  = $ability->doExecute(array( 'group' => '' ));

        $this->assertTrue($result['flushed']);
        $this->assertEquals('all', $result['group']);
    }
}
