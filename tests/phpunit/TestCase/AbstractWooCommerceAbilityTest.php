<?php

/**
 * Base test case for WooCommerce abilities.
 *
 * @package FAWpmcp\Tests\TestCase
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\TestCase;

use Brain\Monkey\Functions;

/**
 * Base test case for WooCommerce-specific abilities.
 *
 * Provides automatic WooCommerce active state mocking and helpers
 * for testing WooCommerce integration.
 *
 * @package FAWpmcp\Tests\TestCase
 */
abstract class AbstractWooCommerceAbilityTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Mock WooCommerce version for tests.
     *
     * @var string
     */
    protected string $wcVersion = '8.5.0';

    /**
     * Set up test environment with WooCommerce mocked as active.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockWooCommerceActive();
    }

    /**
     * Mock WooCommerce as active and available.
     *
     * Sets up class_exists, function_exists, and WC() to simulate
     * a properly configured WooCommerce installation.
     *
     * @param string|null $version Optional WooCommerce version to mock.
     * @return void
     */
    protected function mockWooCommerceActive(?string $version = null): void
    {
        $version = $version ?? $this->wcVersion;

        // Create a mock WooCommerce instance.
        $wc = (object) ['version' => $version];

        // Mock WC() function to return our mock.
        Functions\when('WC')->justReturn($wc);
    }

    /**
     * Mock WooCommerce as inactive (class not loaded).
     *
     * Use this in tests that verify behavior when WooCommerce is not installed.
     *
     * @return void
     */
    protected function mockWooCommerceInactive(): void
    {
        Functions\when('WC')->justReturn(null);
    }

    /**
     * Mock WooCommerce as having an old version.
     *
     * Use this in tests that verify version checking behavior.
     *
     * @param string $version The old version to mock.
     * @return void
     */
    protected function mockWooCommerceOldVersion(string $version = '7.9.0'): void
    {
        $wc = (object) ['version' => $version];
        Functions\when('WC')->justReturn($wc);
    }

    /**
     * Assert that the ability throws WooCommerceNotActiveException.
     *
     * @param callable $callback The code that should throw.
     * @param string   $message  Optional assertion message.
     * @return void
     */
    protected function assertWooCommerceNotActive(callable $callback, string $message = ''): void
    {
        $this->expectException(\FAWpmcp\Exceptions\WooCommerceNotActiveException::class);
        $callback();
    }
}
