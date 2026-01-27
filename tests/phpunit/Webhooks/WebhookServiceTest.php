<?php

/**
 * Tests for WebhookService
 *
 * @package FAWpmcp\Tests\Webhooks
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Webhooks;

use Brain\Monkey;
use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use FAWpmcp\Webhooks\WebhookService;
use FAWpmcp\Webhooks\WebhookManager;
use PHPUnit\Framework\TestCase;

/**
 * Test WebhookService
 *
 * @coversDefaultClass \FAWpmcp\Webhooks\WebhookService
 */
final class WebhookServiceTest extends TestCase
{
    /**
     * Set up Brain\Monkey and WordPress environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        // Define WordPress salts for encryption.
        if (! defined('SECURE_AUTH_KEY')) {
            define('SECURE_AUTH_KEY', 'test-secure-auth-key-' . bin2hex(random_bytes(32)));
        }
        if (! defined('LOGGED_IN_KEY')) {
            define('LOGGED_IN_KEY', 'test-logged-in-key-' . bin2hex(random_bytes(32)));
        }
        if (! defined('NONCE_SALT')) {
            define('NONCE_SALT', 'test-nonce-salt-' . bin2hex(random_bytes(32)));
        }

        // Mock WordPress functions used by dependencies.
        Functions\when('get_option')->justReturn(array());
        Functions\when('update_option')->justReturn(true);
        Functions\when('delete_option')->justReturn(true);
    }

    /**
     * Tear down Brain\Monkey.
     */
    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Test constructor creates valid service instance.
     *
     * @covers ::__construct
     */
    public function testConstructorCreatesValidInstance(): void
    {
        if (! extension_loaded('sodium') && ! extension_loaded('openssl')) {
            $this->markTestSkipped('Neither sodium nor openssl extension available');
        }

        $service = new WebhookService();

        $this->assertInstanceOf(WebhookService::class, $service);
    }

    /**
     * Test getManager returns WebhookManager instance.
     *
     * @covers ::__construct
     * @covers ::getManager
     */
    public function testGetManagerReturnsWebhookManager(): void
    {
        if (! extension_loaded('sodium') && ! extension_loaded('openssl')) {
            $this->markTestSkipped('Neither sodium nor openssl extension available');
        }

        $service = new WebhookService();

        $this->assertInstanceOf(WebhookManager::class, $service->getManager());
    }

    /**
     * Test init registers WordPress hooks for ability events.
     *
     * @covers ::init
     * @covers ::registerAbilityHooks
     */
    public function testInitRegistersAbilityHooks(): void
    {
        if (! extension_loaded('sodium') && ! extension_loaded('openssl')) {
            $this->markTestSkipped('Neither sodium nor openssl extension available');
        }

        // Mock WP-Cron functions for scheduler.
        Functions\when('wp_next_scheduled')->justReturn(false);
        Functions\when('wp_schedule_event')->justReturn(true);

        // Track registered hooks.
        $registered_hooks = array();

        Actions\expectAdded('fa_wpmcp_ability_before_execute')
            ->once()
            ->whenHappen(function () use (&$registered_hooks) {
                $registered_hooks[] = 'fa_wpmcp_ability_before_execute';
            });

        Actions\expectAdded('fa_wpmcp_ability_after_execute')
            ->once()
            ->whenHappen(function () use (&$registered_hooks) {
                $registered_hooks[] = 'fa_wpmcp_ability_after_execute';
            });

        Actions\expectAdded('fa_wpmcp_ability_failed')
            ->once()
            ->whenHappen(function () use (&$registered_hooks) {
                $registered_hooks[] = 'fa_wpmcp_ability_failed';
            });

        $service = new WebhookService();
        $service->init();

        $this->assertCount(3, $registered_hooks);
        $this->assertContains('fa_wpmcp_ability_before_execute', $registered_hooks);
        $this->assertContains('fa_wpmcp_ability_after_execute', $registered_hooks);
        $this->assertContains('fa_wpmcp_ability_failed', $registered_hooks);
    }

    /**
     * Test onBeforeExecute triggers webhook manager.
     *
     * @covers ::onBeforeExecute
     */
    public function testOnBeforeExecuteTriggersManager(): void
    {
        if (! extension_loaded('sodium') && ! extension_loaded('openssl')) {
            $this->markTestSkipped('Neither sodium nor openssl extension available');
        }

        // Mock database functions for queue.
        global $wpdb;
        $wpdb = \Mockery::mock('WPDB');
        $wpdb->prefix = 'wp_';
        $wpdb->shouldReceive('prepare')->andReturnUsing(function ($sql) {
            return $sql;
        });
        $wpdb->shouldReceive('query')->andReturn(1);
        $wpdb->shouldReceive('insert')->andReturn(1);
        $wpdb->insert_id = 1;

        $service = new WebhookService();

        $context = array(
            'ability_name' => 'posts-pages.get',
            'category'     => 'posts-pages',
            'operation'    => 'read',
            'user_id'      => 1,
            'user_login'   => 'admin',
            'ip'           => '127.0.0.1',
            'input'        => array( 'post_id' => 123 ),
        );

        // This should not throw - triggers manager's trigger method.
        $service->onBeforeExecute($context);

        // If we got here without exception, the method executed.
        $this->assertTrue(true);
    }

    /**
     * Test onAfterExecute triggers webhook manager.
     *
     * @covers ::onAfterExecute
     */
    public function testOnAfterExecuteTriggersManager(): void
    {
        if (! extension_loaded('sodium') && ! extension_loaded('openssl')) {
            $this->markTestSkipped('Neither sodium nor openssl extension available');
        }

        global $wpdb;
        $wpdb = \Mockery::mock('WPDB');
        $wpdb->prefix = 'wp_';
        $wpdb->shouldReceive('prepare')->andReturnUsing(function ($sql) {
            return $sql;
        });
        $wpdb->shouldReceive('query')->andReturn(1);
        $wpdb->shouldReceive('insert')->andReturn(1);
        $wpdb->insert_id = 1;

        $service = new WebhookService();

        $context = array(
            'ability_name'      => 'posts-pages.get',
            'category'          => 'posts-pages',
            'operation'         => 'read',
            'user_id'           => 1,
            'user_login'        => 'admin',
            'ip'                => '127.0.0.1',
            'input'             => array( 'post_id' => 123 ),
            'output'            => array( 'ID' => 123, 'post_title' => 'Test' ),
            'success'           => true,
            'execution_time_ms' => 15,
        );

        $service->onAfterExecute($context);

        $this->assertTrue(true);
    }

    /**
     * Test onFailed triggers webhook manager.
     *
     * @covers ::onFailed
     */
    public function testOnFailedTriggersManager(): void
    {
        if (! extension_loaded('sodium') && ! extension_loaded('openssl')) {
            $this->markTestSkipped('Neither sodium nor openssl extension available');
        }

        global $wpdb;
        $wpdb = \Mockery::mock('WPDB');
        $wpdb->prefix = 'wp_';
        $wpdb->shouldReceive('prepare')->andReturnUsing(function ($sql) {
            return $sql;
        });
        $wpdb->shouldReceive('query')->andReturn(1);
        $wpdb->shouldReceive('insert')->andReturn(1);
        $wpdb->insert_id = 1;

        $service = new WebhookService();

        $context = array(
            'ability_name'      => 'posts-pages.delete',
            'category'          => 'posts-pages',
            'operation'         => 'write',
            'user_id'           => 1,
            'user_login'        => 'admin',
            'ip'                => '127.0.0.1',
            'input'             => array( 'post_id' => 123 ),
            'output'            => array( 'error' => 'Permission denied' ),
            'success'           => false,
            'execution_time_ms' => 5,
        );

        $service->onFailed($context);

        $this->assertTrue(true);
    }

    /**
     * Test deactivate unschedules webhook processing.
     *
     * @covers ::deactivate
     */
    public function testDeactivateUnschedulesProcessing(): void
    {
        if (! extension_loaded('sodium') && ! extension_loaded('openssl')) {
            $this->markTestSkipped('Neither sodium nor openssl extension available');
        }

        // Mock Action Scheduler function if it exists (may be defined by other tests).
        Functions\when('as_unschedule_all_actions')->justReturn(null);

        // Mock WP-Cron functions.
        Functions\expect('wp_next_scheduled')
            ->once()
            ->with('fa_wpmcp_process_webhook_queue')
            ->andReturn(12345);

        Functions\expect('wp_unschedule_event')
            ->once()
            ->with(12345, 'fa_wpmcp_process_webhook_queue')
            ->andReturn(true);

        $service = new WebhookService();
        $service->deactivate();

        // Mockery expectations are assertions - this confirms they were met.
        $this->assertTrue(true, 'WP-Cron unschedule functions called');
    }

    /**
     * Test getManager returns same instance on repeated calls.
     *
     * @covers ::getManager
     */
    public function testGetManagerReturnsSameInstance(): void
    {
        if (! extension_loaded('sodium') && ! extension_loaded('openssl')) {
            $this->markTestSkipped('Neither sodium nor openssl extension available');
        }

        $service = new WebhookService();

        $manager1 = $service->getManager();
        $manager2 = $service->getManager();

        $this->assertSame($manager1, $manager2);
    }
}
