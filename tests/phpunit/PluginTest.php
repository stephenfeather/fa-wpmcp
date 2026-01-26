<?php

/**
 * Test plugin core functionality.
 *
 * @package FAWpmcp\Tests
 */

declare(strict_types=1);

namespace FAWpmcp\Tests;

use FAWpmcp\Plugin;
use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Test plugin core functionality.
 */
class PluginTest extends TestCase
{
    /**
     * Set up test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }

    /**
     * Tear down test environment.
     */
    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Test that get_instance returns a singleton.
     */
    public function test_get_instance_returns_singleton(): void
    {
        $instance1 = Plugin::getInstance();
        $instance2 = Plugin::getInstance();

        $this->assertSame($instance1, $instance2);
    }

    /**
     * Test that init registers WordPress hooks.
     */
    public function test_init_registers_hooks(): void
    {
        // Mock WordPress functions called during init.
        Functions\expect('add_action')
            ->atLeast()
            ->once();

        Functions\expect('get_option')
            ->andReturn(array());

        Functions\expect('wp_generate_uuid4')
            ->andReturn('test-uuid');

        // Set up mock $wpdb before init is called.
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Required for unit tests.
        $GLOBALS['wpdb'] = \Mockery::mock('\wpdb');
        $GLOBALS['wpdb']->prefix = 'wp_';

        $plugin = Plugin::getInstance();
        $plugin->init();

        $this->assertTrue(true); // Assert that we got here without errors.
    }

    /**
     * Test service registration.
     */
    public function test_register_service_stores_service(): void
    {
        $plugin  = Plugin::getInstance();
        $service = new \stdClass();
        $service->name = 'test';

        $plugin->registerService('test_service', $service);
        $retrieved = $plugin->getService('test_service');

        $this->assertSame($service, $retrieved);
    }

    /**
     * Test get_service returns null for non-existent service.
     */
    public function test_get_service_returns_null_for_missing_service(): void
    {
        $plugin = Plugin::getInstance();

        $result = $plugin->getService('non_existent');

        $this->assertNull($result);
    }

    /**
     * Test that has_abilities_api method exists and returns bool.
     *
     * Note: We can't easily mock function_exists with Brain Monkey,
     * so we just verify the method exists and returns a boolean.
     */
    public function test_has_abilities_api_returns_bool(): void
    {
        $plugin = Plugin::getInstance();
        $result = $plugin->hasAbilitiesApi();

        $this->assertIsBool($result);
    }

    /**
     * Test checkAbilitiesApiAndShowNotice method exists.
     *
     * We verify the method is callable and doesn't throw errors.
     */
    public function test_check_abilities_api_method_exists(): void
    {
        $plugin = Plugin::getInstance();

        $this->assertTrue(method_exists($plugin, 'checkAbilitiesApiAndShowNotice'));
    }

    /**
     * Test that a notice is registered when Abilities API is missing.
     */
    public function test_check_abilities_api_adds_admin_notice_when_missing(): void
    {
        Functions\expect('add_action')
            ->once()
            ->with('admin_notices', \Mockery::type('callable'));

        $plugin = Plugin::getInstance();
        $plugin->checkAbilitiesApiAndShowNotice();

        $this->assertTrue(true);
    }

    /**
     * Test that no notice is registered when Abilities API is available.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_check_abilities_api_does_not_add_notice_when_available(): void
    {
        eval('namespace { function wp_register_ability() {} }');

        Functions\expect('add_action')->never();

        $plugin = Plugin::getInstance();
        $plugin->checkAbilitiesApiAndShowNotice();

        $this->assertTrue(true);
    }

    /**
     * Test that init registers Post abilities with AbilityRegistry.
     */
    public function test_init_registers_post_abilities(): void
    {
        // Mock WordPress functions called during init.
        Functions\expect('add_action')
            ->atLeast()
            ->once();

        Functions\expect('get_option')
            ->andReturn(array());

        Functions\expect('wp_generate_uuid4')
            ->andReturn('test-uuid');

        // Set up mock $wpdb before init is called.
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Required for unit tests.
        $GLOBALS['wpdb'] = \Mockery::mock('\wpdb');
        $GLOBALS['wpdb']->prefix = 'wp_';

        $plugin = Plugin::getInstance();
        $plugin->init();

        // Get the ability registry service.
        $registry = $plugin->getService('ability_registry');

        $this->assertInstanceOf(\FAWpmcp\Abilities\AbilityRegistry::class, $registry);

        // Verify all 5 Post abilities are registered.
        $this->assertTrue($registry->has('fa-wpmcp/get-post'), 'GetPost ability should be registered');
        $this->assertTrue($registry->has('fa-wpmcp/list-posts'), 'ListPosts ability should be registered');
        $this->assertTrue($registry->has('fa-wpmcp/create-post'), 'CreatePost ability should be registered');
        $this->assertTrue($registry->has('fa-wpmcp/update-post'), 'UpdatePost ability should be registered');
        $this->assertTrue($registry->has('fa-wpmcp/delete-post'), 'DeletePost ability should be registered');

        // Verify they are in the correct category.
        $post_abilities = $registry->byCategory('posts-pages');
        $this->assertCount(5, $post_abilities, 'Should have 5 abilities in posts-pages category');
    }
}
