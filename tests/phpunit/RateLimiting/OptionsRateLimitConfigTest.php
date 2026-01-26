<?php

/**
 * Tests for OptionsRateLimitConfig.
 *
 * @package FAWpmcp\Tests\RateLimiting
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\RateLimiting;

use Brain\Monkey\Functions;
use FAWpmcp\RateLimiting\OptionsRateLimitConfig;
use PHPUnit\Framework\TestCase;
use Mockery;

/**
 * Test OptionsRateLimitConfig behavior.
 */
final class OptionsRateLimitConfigTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        \Brain\Monkey\setUp();
    }

    protected function tearDown(): void
    {
        \Brain\Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Test get_all merges defaults when option missing or invalid.
     *
     * @return void
     */
    public function test_get_all_merges_defaults_with_invalid_option(): void
    {
        Functions\expect('get_option')
            ->once()
            ->with('fa_wpmcp_rate_limits', array())
            ->andReturn('not-an-array');

        $config = new OptionsRateLimitConfig();
        $result = $config->getAll();

        $this->assertArrayHasKey('default', $result);
        $this->assertSame(60, $result['default']['requests_per_minute']);
    }

    /**
     * Test get returns ability-specific overrides.
     *
     * @return void
     */
    public function test_get_returns_ability_override_when_present(): void
    {
        Functions\expect('get_option')
            ->once()
            ->andReturn(
                array(
                    'custom' => array(
                        'requests_per_minute' => 10,
                        'requests_per_hour'   => 100,
                    ),
                )
            );

        $config = new OptionsRateLimitConfig();
        $result = $config->get('custom');

        $this->assertSame(10, $result['requests_per_minute']);
        $this->assertSame(100, $result['requests_per_hour']);
    }

    /**
     * Test set updates option with merged config.
     *
     * @return void
     */
    public function test_set_updates_option(): void
    {
        Functions\expect('get_option')
            ->once()
            ->andReturn(array());

        Functions\expect('update_option')
            ->once()
            ->with(
                'fa_wpmcp_rate_limits',
                Mockery::on(
                    function ($config) {
                        return isset($config['default']) && isset($config['ability-x']);
                    }
                )
            )
            ->andReturn(true);

        $config = new OptionsRateLimitConfig();
        $config->set('ability-x', 5, 50);
    }
}
