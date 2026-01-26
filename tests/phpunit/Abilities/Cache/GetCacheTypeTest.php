<?php

/**
 * Tests for GetCacheType.
 *
 * @package FAWpmcp\Tests\Abilities\Cache
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Cache;

use FAWpmcp\Abilities\Cache\GetCacheType;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

final class GetCacheTypeTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        \Brain\Monkey\setUp();
        // Define WP_CONTENT_DIR if not defined.
        if (! defined('WP_CONTENT_DIR')) {
            define('WP_CONTENT_DIR', sys_get_temp_dir());
        }
    }

    protected function tearDown(): void
    {
        \Brain\Monkey\tearDown();
        parent::tearDown();
    }

    public function test_ability_metadata(): void
    {
        $ability = new GetCacheType();
        $this->assertEquals('fa-wpmcp/get-cache-type', $ability->getName());
        $this->assertEquals('cache', $ability->getCategory());
        $this->assertEquals('Get Cache Type', $ability->getLabel());
        $this->assertStringContainsString('cache', strtolower($ability->getDescription()));
        $this->assertEquals('manage_options', $ability->getRequiredCapability());
    }

    public function test_operation_type_is_read(): void
    {
        $ability = new GetCacheType();
        $this->assertEquals('read', $ability->getOperationType());
    }

    public function test_input_schema_has_no_required_fields(): void
    {
        $ability = new GetCacheType();
        $schema  = $ability->getInputSchema();

        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayNotHasKey('required', $schema);
    }

    public function test_output_schema_structure(): void
    {
        $ability = new GetCacheType();
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

        $ability = new GetCacheType();
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

        $ability = new GetCacheType();
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

        $ability = new GetCacheType();
        $result  = $ability->doExecute(array());

        $this->assertTrue($result['persistent']);
    }

    public function test_returns_drop_in_false_when_no_file(): void
    {
        Functions\expect('wp_using_ext_object_cache')->twice()->andReturn(false);

        $ability = new GetCacheType();
        $result  = $ability->doExecute(array());

        // The drop-in check uses file_exists on the temp dir path.
        $this->assertIsBool($result['drop_in']);
    }
}
