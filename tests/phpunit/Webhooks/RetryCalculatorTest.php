<?php

/**
 * Tests for RetryCalculator.
 *
 * @package FAWpmcp\Tests\Webhooks
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Webhooks;

use DateTimeImmutable;
use FAWpmcp\Webhooks\RetryCalculator;
use PHPUnit\Framework\TestCase;

/**
 * Test RetryCalculator logic.
 */
final class RetryCalculatorTest extends TestCase
{
    /**
     * Test calculates exponential backoff delays.
     *
     * @return void
     */
    public function test_calculateNextAttempt_uses_exponential_backoff(): void
    {
        $base = new DateTimeImmutable('2026-01-21 00:00:00');

        $first  = RetryCalculator::calculateNextAttempt(0, $base);
        $second = RetryCalculator::calculateNextAttempt(1, $base);
        $third  = RetryCalculator::calculateNextAttempt(2, $base);

        $this->assertSame('2026-01-21 00:01:00', $first->format('Y-m-d H:i:s'));
        $this->assertSame('2026-01-21 00:02:00', $second->format('Y-m-d H:i:s'));
        $this->assertSame('2026-01-21 00:04:00', $third->format('Y-m-d H:i:s'));
    }

    /**
     * Test calculateNextAttempt is deterministic.
     *
     * @return void
     */
    public function test_calculateNextAttempt_is_deterministic(): void
    {
        $base = new DateTimeImmutable('2026-01-21 00:00:00');

        $one = RetryCalculator::calculateNextAttempt(1, $base);
        $two = RetryCalculator::calculateNextAttempt(1, $base);

        $this->assertEquals($one, $two);
    }
}
