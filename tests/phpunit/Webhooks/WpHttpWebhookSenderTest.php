<?php

/**
 * Tests for WpHttpWebhookSender.
 *
 * @package FAWpmcp\Tests\Webhooks
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Webhooks;

use Brain\Monkey\Functions;
use FAWpmcp\Webhooks\WpHttpWebhookSender;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test WpHttpWebhookSender behavior.
 */
final class WpHttpWebhookSenderTest extends TestCase
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
     * Test returns failure result when wp_remote_post returns WP_Error.
     *
     * @return void
     */
    public function test_send_returns_error_result_for_wp_error(): void
    {
        $wp_error = Mockery::mock('WP_Error');
        $wp_error->shouldReceive('get_error_message')
            ->once()
            ->andReturn('Network error');

        Functions\expect('wp_remote_post')
            ->once()
            ->andReturn($wp_error);

        Functions\expect('is_wp_error')
            ->once()
            ->with($wp_error)
            ->andReturn(true);

        $sender = new WpHttpWebhookSender();
        $result = $sender->send('https://example.com', '{}', 'sig');

        $this->assertFalse($result->is_success);
        $this->assertSame(0, $result->status_code);
        $this->assertSame('Network error', $result->error_message);
    }

    /**
     * Test returns success result for 2xx responses.
     *
     * @return void
     */
    public function test_send_returns_success_for_2xx_response(): void
    {
        $response = array( 'body' => 'OK' );

        Functions\expect('wp_remote_post')
            ->once()
            ->andReturn($response);

        Functions\expect('is_wp_error')
            ->once()
            ->with($response)
            ->andReturn(false);

        Functions\expect('wp_remote_retrieve_response_code')
            ->once()
            ->with($response)
            ->andReturn(204);

        Functions\expect('wp_remote_retrieve_body')
            ->once()
            ->with($response)
            ->andReturn('OK');

        $sender = new WpHttpWebhookSender();
        $result = $sender->send('https://example.com', '{}', 'sig');

        $this->assertTrue($result->is_success);
        $this->assertSame(204, $result->status_code);
        $this->assertSame('OK', $result->response_body);
        $this->assertNull($result->error_message);
    }

    /**
     * Test returns error message for non-2xx responses.
     *
     * @return void
     */
    public function test_send_returns_error_for_non_2xx_response(): void
    {
        $response = array( 'body' => 'fail' );

        Functions\expect('wp_remote_post')
            ->once()
            ->andReturn($response);

        Functions\expect('is_wp_error')
            ->once()
            ->with($response)
            ->andReturn(false);

        Functions\expect('wp_remote_retrieve_response_code')
            ->once()
            ->with($response)
            ->andReturn(500);

        Functions\expect('wp_remote_retrieve_body')
            ->once()
            ->with($response)
            ->andReturn('fail');

        Functions\expect('wp_remote_retrieve_response_message')
            ->once()
            ->with($response)
            ->andReturn('Server error');

        $sender = new WpHttpWebhookSender();
        $result = $sender->send('https://example.com', '{}', 'sig');

        $this->assertFalse($result->is_success);
        $this->assertSame(500, $result->status_code);
        $this->assertSame('Server error', $result->error_message);
    }
}
