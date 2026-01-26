<?php

/**
 * Tests for RateLimiter orchestration.
 *
 * @package FAWpmcp\Tests\RateLimiting
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\RateLimiting;

use FAWpmcp\RateLimiting\RateLimiter;
use FAWpmcp\RateLimiting\RateLimitStore;
use FAWpmcp\RateLimiting\RateLimitConfig;
use FAWpmcp\ValueObjects\RateLimitResult;
use PHPUnit\Framework\TestCase;
use Mockery;

/**
 * Test RateLimiter orchestration.
 *
 * @package FAWpmcp\Tests\RateLimiting
 */
class RateLimiterTest extends TestCase
{
    /**
     * Tear down Mockery after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test check returns allowed when under limits.
     *
     * @return void
     */
    public function test_check_returns_allowed_when_under_limits(): void
    {
        $store = Mockery::mock(RateLimitStore::class);
        $store->shouldReceive('get')
            ->andReturn(10); // Low count.

        $config = Mockery::mock(RateLimitConfig::class);
        $config->shouldReceive('getAll')
            ->andReturn(array()); // Use defaults.

        $limiter = new RateLimiter($store, $config);

        $result = $limiter->check('fa-wpmcp/list-posts', 1, '127.0.0.1');

        $this->assertTrue($result->allowed);
        $this->assertInstanceOf(RateLimitResult::class, $result);
    }

    /**
     * Test check returns denied when over minute limit.
     *
     * @return void
     */
    public function test_check_returns_denied_when_over_minute_limit(): void
    {
        $store = Mockery::mock(RateLimitStore::class);
        $store->shouldReceive('get')
            ->andReturnUsing(
                function ($key) {
                    // Return high count for minute key, low for hour.
                    if (str_contains($key, '_minute')) {
                        return 100; // Over limit.
                    }
                    return 10;
                }
            );

        $config = Mockery::mock(RateLimitConfig::class);
        $config->shouldReceive('getAll')
            ->andReturn(array()); // Use defaults (60/min).

        $limiter = new RateLimiter($store, $config);

        $result = $limiter->check('fa-wpmcp/list-posts', 1, '127.0.0.1');

        $this->assertFalse($result->allowed);
        $this->assertEquals('minute', $result->limit_type);
    }

    /**
     * Test check returns denied when over hour limit.
     *
     * @return void
     */
    public function test_check_returns_denied_when_over_hour_limit(): void
    {
        $store = Mockery::mock(RateLimitStore::class);
        $store->shouldReceive('get')
            ->andReturnUsing(
                function ($key) {
                    // Return low count for minute key, high for hour.
                    if (str_contains($key, '_hour')) {
                        return 600; // Over limit (default 500).
                    }
                    return 10;
                }
            );

        $config = Mockery::mock(RateLimitConfig::class);
        $config->shouldReceive('getAll')
            ->andReturn(array()); // Use defaults (500/hour).

        $limiter = new RateLimiter($store, $config);

        $result = $limiter->check('fa-wpmcp/list-posts', 1, '127.0.0.1');

        $this->assertFalse($result->allowed);
        $this->assertEquals('hour', $result->limit_type);
    }

    /**
     * Test check uses custom config for ability.
     *
     * @return void
     */
    public function test_check_uses_custom_config(): void
    {
        $store = Mockery::mock(RateLimitStore::class);
        $store->shouldReceive('get')
            ->andReturn(5); // Under custom limit of 10.

        $config = Mockery::mock(RateLimitConfig::class);
        $config->shouldReceive('getAll')
            ->andReturn(
                array(
                    'fa-wpmcp/create-post' => array(
                        'requests_per_minute' => 10,
                        'requests_per_hour'   => 100,
                    ),
                )
            );

        $limiter = new RateLimiter($store, $config);

        $result = $limiter->check('fa-wpmcp/create-post', 1, '127.0.0.1');

        $this->assertTrue($result->allowed);
    }

    /**
     * Test record increments both minute and hour counters (LEGACY - see new tests below).
     *
     * @return void
     */
    public function test_record_increments_both_counters(): void
    {
        $store = Mockery::mock(RateLimitStore::class);
        $store->shouldReceive('increment')
            ->times(4); // 2 for IP (minute+hour), 2 for user (minute+hour).

        $config = Mockery::mock(RateLimitConfig::class);

        $limiter = new RateLimiter($store, $config);

        $limiter->record('fa-wpmcp/list-posts', 1, '127.0.0.1');

        // Verify increment was called four times via Mockery.
        $this->assertTrue(true); // Mockery expectations verify this.
    }

    /**
     * Test record uses correct TTL for minute counter.
     *
     * @return void
     */
    public function test_record_uses_correct_minute_ttl(): void
    {
        $store = Mockery::mock(RateLimitStore::class);
        $store->shouldReceive('increment')
            ->with(Mockery::on(fn($key) => str_contains($key, '_minute')), 60)
            ->twice(); // Once for IP, once for user.
        $store->shouldReceive('increment')
            ->with(Mockery::on(fn($key) => str_contains($key, '_hour')), 3600)
            ->twice(); // Once for IP, once for user.

        $config = Mockery::mock(RateLimitConfig::class);

        $limiter = new RateLimiter($store, $config);

        $limiter->record('fa-wpmcp/list-posts', 1, '127.0.0.1');

        $this->assertTrue(true); // Mockery expectations verify TTLs.
    }

    /**
     * Test check and record workflow.
     *
     * @return void
     */
    public function test_check_and_record_workflow(): void
    {
        $store = Mockery::mock(RateLimitStore::class);
        $store->shouldReceive('get')
            ->andReturn(10); // Under limit.
        $store->shouldReceive('increment')
            ->times(4); // Record increments both IP and user (2 each).

        $config = Mockery::mock(RateLimitConfig::class);
        $config->shouldReceive('getAll')
            ->andReturn(array());

        $limiter = new RateLimiter($store, $config);

        // Check first.
        $result = $limiter->check('fa-wpmcp/list-posts', 1, '127.0.0.1');
        $this->assertTrue($result->allowed);

        // Then record.
        $limiter->record('fa-wpmcp/list-posts', 1, '127.0.0.1');

        $this->assertTrue(true); // Workflow completed.
    }

    /**
     * Test check with zero user ID (anonymous).
     *
     * @return void
     */
    public function test_check_with_zero_user_id(): void
    {
        $store = Mockery::mock(RateLimitStore::class);
        $store->shouldReceive('get')
            ->andReturn(10);

        $config = Mockery::mock(RateLimitConfig::class);
        $config->shouldReceive('getAll')
            ->andReturn(array());

        $limiter = new RateLimiter($store, $config);

        $result = $limiter->check('fa-wpmcp/list-posts', 0, '192.168.1.1');

        $this->assertTrue($result->allowed);
    }

    /**
     * Test result contains retry after when denied.
     *
     * @return void
     */
    public function test_result_contains_retry_after_when_denied(): void
    {
        $store = Mockery::mock(RateLimitStore::class);
        $store->shouldReceive('get')
            ->andReturnUsing(
                function ($key) {
                    if (str_contains($key, '_minute')) {
                        return 100; // Over limit.
                    }
                    return 10;
                }
            );

        $config = Mockery::mock(RateLimitConfig::class);
        $config->shouldReceive('getAll')
            ->andReturn(array());

        $limiter = new RateLimiter($store, $config);

        $result = $limiter->check('fa-wpmcp/list-posts', 1, '127.0.0.1');

        $this->assertFalse($result->allowed);
        $this->assertGreaterThan(0, $result->retry_after);
        $this->assertLessThanOrEqual(60, $result->retry_after);
    }

    /**
     * Test check denies when IP limit exceeded even if user limit is OK.
     *
     * @return void
     */
    public function test_check_denies_when_ip_limit_exceeded(): void
    {
        $store = Mockery::mock(RateLimitStore::class);
        $store->shouldReceive('get')
            ->andReturnUsing(
                function ($key) {
                    // IP-based keys return high count, user-based return low.
                    if (str_contains($key, '_ip_') && str_contains($key, '_minute')) {
                        return 100; // Over limit.
                    }
                    return 10; // Under limit.
                }
            );

        $config = Mockery::mock(RateLimitConfig::class);
        $config->shouldReceive('getAll')
            ->andReturn(array());

        $limiter = new RateLimiter($store, $config);

        $result = $limiter->check('fa-wpmcp/list-posts', 1, '127.0.0.1');

        $this->assertFalse($result->allowed);
        $this->assertEquals('minute', $result->limit_type);
    }

    /**
     * Test check denies when user limit exceeded even if IP limit is OK.
     *
     * @return void
     */
    public function test_check_denies_when_user_limit_exceeded(): void
    {
        $store = Mockery::mock(RateLimitStore::class);
        $store->shouldReceive('get')
            ->andReturnUsing(
                function ($key) {
                    // User-based keys return high count, IP-based return low.
                    if (str_contains($key, '_user_') && str_contains($key, '_minute')) {
                        return 100; // Over limit.
                    }
                    return 10; // Under limit.
                }
            );

        $config = Mockery::mock(RateLimitConfig::class);
        $config->shouldReceive('getAll')
            ->andReturn(array());

        $limiter = new RateLimiter($store, $config);

        $result = $limiter->check('fa-wpmcp/list-posts', 1, '127.0.0.1');

        $this->assertFalse($result->allowed);
        $this->assertEquals('minute', $result->limit_type);
    }

    /**
     * Test check allows only when BOTH IP and user limits are under threshold.
     *
     * @return void
     */
    public function test_check_allows_when_both_limits_ok(): void
    {
        $store = Mockery::mock(RateLimitStore::class);
        $store->shouldReceive('get')
            ->andReturn(10); // All keys return low count.

        $config = Mockery::mock(RateLimitConfig::class);
        $config->shouldReceive('getAll')
            ->andReturn(array());

        $limiter = new RateLimiter($store, $config);

        $result = $limiter->check('fa-wpmcp/list-posts', 1, '127.0.0.1');

        $this->assertTrue($result->allowed);
    }

    /**
     * Test record increments both IP and user counters for authenticated users.
     *
     * @return void
     */
    public function test_record_increments_ip_and_user_counters(): void
    {
        $store = Mockery::mock(RateLimitStore::class);
        $store->shouldReceive('increment')
            ->times(4); // 2 for IP (minute+hour), 2 for user (minute+hour).

        $config = Mockery::mock(RateLimitConfig::class);

        $limiter = new RateLimiter($store, $config);

        $limiter->record('fa-wpmcp/list-posts', 1, '127.0.0.1');

        // Mockery expectations verify this.
        $this->assertTrue(true);
    }

    /**
     * Test record increments only IP counters for anonymous users.
     *
     * @return void
     */
    public function test_record_increments_only_ip_for_anonymous(): void
    {
        $store = Mockery::mock(RateLimitStore::class);
        $store->shouldReceive('increment')
            ->times(2); // Only 2 for IP (minute+hour), none for user.

        $config = Mockery::mock(RateLimitConfig::class);

        $limiter = new RateLimiter($store, $config);

        $limiter->record('fa-wpmcp/list-posts', 0, '192.168.1.1');

        // Mockery expectations verify this.
        $this->assertTrue(true);
    }

    /**
     * Test anonymous users only checked against IP limits.
     *
     * @return void
     */
    public function test_anonymous_users_only_checked_against_ip(): void
    {
        $store = Mockery::mock(RateLimitStore::class);
        $store->shouldReceive('get')
            ->andReturnUsing(
                function ($key) {
                    // Should only get IP-based keys, not user-based.
                    $this->assertStringContainsString('_ip_', $key);
                    return 10;
                }
            );

        $config = Mockery::mock(RateLimitConfig::class);
        $config->shouldReceive('getAll')
            ->andReturn(array());

        $limiter = new RateLimiter($store, $config);

        $result = $limiter->check('fa-wpmcp/list-posts', 0, '192.168.1.1');

        $this->assertTrue($result->allowed);
    }
}
