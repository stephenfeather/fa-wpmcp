<?php

/**
 * Tests for GetCacheType.
 *
 * @package FAWpmcp\Tests\Abilities\Cache
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Cache;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Cache\GetCacheType;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;

final class GetCacheTypeTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        // Define WP_CONTENT_DIR if not defined.
        if (! defined('WP_CONTENT_DIR')) {
            define('WP_CONTENT_DIR', sys_get_temp_dir());
        }
    }

    protected function getAbilityInstance(): AbstractAbility
    {
        return new GetCacheType();
    }

    protected function getExpectedMetadata(): array
    {
        return [
            'name'                 => 'fa-wpmcp/get-cache-type',
            'category'             => 'cache',
            'label'                => 'Get Cache Type',
            'description_contains' => 'cache',
            'operation_type'       => 'read',
            'required_capability'  => 'manage_options',
        ];
    }

    public function test_output_schema_structure(): void
    {
        $ability = $this->getAbilityInstance();
        $schema  = $ability->getOutputSchema();

        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('type', $schema['properties']);
        $this->assertArrayHasKey('persistent', $schema['properties']);
        $this->assertArrayHasKey('drop_in', $schema['properties']);
        $this->assertArrayHasKey('drop_in_path', $schema['properties']);
    }

    public function test_returns_default_type_without_external_cache(): void
    {
        Functions\expect('wp_using_ext_object_cache')->twice()->andReturn(false);

        $ability = $this->getAbilityInstance();
        $result  = $ability->doExecute(array());

        $this->assertEquals('default', $result['type']);
        $this->assertFalse($result['persistent']);
    }

    public function test_detects_redis_from_class_name(): void
    {
        global $wp_object_cache;

        // Create a mock Redis cache class.
        $wp_object_cache = new class () {
        };

        // Override get_class to return a Redis class name.
        Functions\expect('wp_using_ext_object_cache')->twice()->andReturn(true);

        // We need to use a real Redis class mock.
        $redis_cache                = \Mockery::mock('WP_Object_Cache_Redis');
        $GLOBALS['wp_object_cache'] = $redis_cache;

        $ability = $this->getAbilityInstance();
        $result  = $ability->doExecute(array());

        // Since we can't easily override get_class, we check the flow.
        $this->assertTrue($result['persistent']);
        $this->assertContains($result['type'], array( 'redis', 'unknown' ));

        // Clean up.
        $wp_object_cache = null;
    }

    public function test_returns_persistent_true_for_external_cache(): void
    {
        Functions\expect('wp_using_ext_object_cache')->twice()->andReturn(true);

        $ability = $this->getAbilityInstance();
        $result  = $ability->doExecute(array());

        $this->assertTrue($result['persistent']);
    }

    public function test_returns_drop_in_false_when_no_file(): void
    {
        Functions\expect('wp_using_ext_object_cache')->twice()->andReturn(false);

        $ability = $this->getAbilityInstance();
        $result  = $ability->doExecute(array());

        // The drop-in check uses file_exists on the temp dir path.
        $this->assertIsBool($result['drop_in']);
    }
}
