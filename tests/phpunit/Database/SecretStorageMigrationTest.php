<?php

/**
 * Tests for SecretStorageMigration
 *
 * @package FAWpmcp\Tests\Database
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Database;

use Brain\Monkey\Functions;
use FAWpmcp\Database\SecretStorageMigration;
use PHPUnit\Framework\TestCase;

/**
 * Test secret storage migration
 *
 * @coversDefaultClass \FAWpmcp\Database\SecretStorageMigration
 */
final class SecretStorageMigrationTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * Set up before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        \Brain\Monkey\setUp();
    }
    /**
     * Clean up after each test
     */
    public function tearDown(): void
    {
        \Brain\Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Test migration consolidates secret from canonical location
     *
     * @covers ::migrate
     */
    public function testMigrationPrefersCanonicalSource(): void
    {
        // Migration flag not set yet.
        Functions\expect('get_option')
            ->once()
            ->with('fa_wpmcp_secret_migration_v1', false)
            ->andReturn(false);

        // Get canonical secret.
        Functions\expect('get_option')
            ->once()
            ->with('fa_wpmcp_webhook_secret')
            ->andReturn('canonical-secret');

        // Get webhooks array with UI secret.
        Functions\expect('get_option')
            ->once()
            ->with('fa_wpmcp_webhooks', array())
            ->andReturn(
                array(
                    'webhook_secret'    => 'ui-secret',
                    'webhook_endpoints' => array(),
                )
            );

        // Should update canonical location.
        Functions\expect('update_option')
            ->once()
            ->with('fa_wpmcp_webhook_secret', 'canonical-secret')
            ->andReturn(true);

        // Should remove secret from webhooks array.
        Functions\expect('update_option')
            ->once()
            ->with('fa_wpmcp_webhooks', array( 'webhook_endpoints' => array() ))
            ->andReturn(true);

        // Should set migration flag.
        Functions\expect('update_option')
            ->once()
            ->with('fa_wpmcp_secret_migration_v1', true)
            ->andReturn(true);

        SecretStorageMigration::migrate();

        // Expectations verified by Mockery.
        $this->assertTrue(true);
    }

    /**
     * Test migration uses UI secret if canonical not set
     *
     * @covers ::migrate
     */
    public function testMigrationUsesUiSecretAsFallback(): void
    {
        Functions\expect('get_option')
            ->once()
            ->with('fa_wpmcp_secret_migration_v1', false)
            ->andReturn(false);

        // No canonical secret.
        Functions\expect('get_option')
            ->once()
            ->with('fa_wpmcp_webhook_secret')
            ->andReturn(false);

        // Get UI secret.
        Functions\expect('get_option')
            ->once()
            ->with('fa_wpmcp_webhooks', array())
            ->andReturn(
                array(
                    'webhook_secret'    => 'ui-secret',
                    'webhook_endpoints' => array(),
                )
            );

        // Should save UI secret to canonical location.
        Functions\expect('update_option')
            ->once()
            ->with('fa_wpmcp_webhook_secret', 'ui-secret')
            ->andReturn(true);

        // Should remove secret from webhooks array.
        Functions\expect('update_option')
            ->once()
            ->with('fa_wpmcp_webhooks', array( 'webhook_endpoints' => array() ))
            ->andReturn(true);

        // Should set migration flag.
        Functions\expect('update_option')
            ->once()
            ->with('fa_wpmcp_secret_migration_v1', true)
            ->andReturn(true);

        SecretStorageMigration::migrate();

        $this->assertTrue(true);
    }

    /**
     * Test migration handles missing secrets gracefully
     *
     * @covers ::migrate
     */
    public function testMigrationHandlesMissingSecrets(): void
    {
        Functions\expect('get_option')
            ->once()
            ->with('fa_wpmcp_secret_migration_v1', false)
            ->andReturn(false);

        Functions\expect('get_option')
            ->once()
            ->with('fa_wpmcp_webhook_secret')
            ->andReturn(false);

        Functions\expect('get_option')
            ->once()
            ->with('fa_wpmcp_webhooks', array())
            ->andReturn(array());

        // Should only set migration flag (no secret to save).
        Functions\expect('update_option')
            ->once()
            ->with('fa_wpmcp_secret_migration_v1', true)
            ->andReturn(true);

        SecretStorageMigration::migrate();

        $this->assertTrue(true);
    }

    /**
     * Test migration runs only once
     *
     * @covers ::migrate
     */
    public function testMigrationRunsOnlyOnce(): void
    {
        // Migration already done.
        Functions\expect('get_option')
            ->once()
            ->with('fa_wpmcp_secret_migration_v1', false)
            ->andReturn(true);

        // Should not make any other calls.
        SecretStorageMigration::migrate();

        $this->assertTrue(true);
    }
}
