<?php
/**
 * Tests for WebhookManager.
 *
 * @package FAWpmcp
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Webhooks;

use FAWpmcp\Webhooks\WebhookManager;
use FAWpmcp\Webhooks\WebhookQueue;
use FAWpmcp\Webhooks\WebhookSender;
use FAWpmcp\Webhooks\WebhookConfig;
use FAWpmcp\ValueObjects\WebhookPayload;
use FAWpmcp\ValueObjects\WebhookResult;
use DateTimeImmutable;
use Mockery;
use PHPUnit\Framework\TestCase;

class WebhookManagerTest extends TestCase {
	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	public function test_trigger_enqueues_webhooks_for_subscribed_urls(): void {
		$queue  = Mockery::mock( WebhookQueue::class );
		$sender = Mockery::mock( WebhookSender::class );
		$config = Mockery::mock( WebhookConfig::class );

		$manager = new WebhookManager( $queue, $sender, $config );

		// Config returns subscribed URLs
		$config->shouldReceive( 'get_subscribed_urls' )
			->with( 'ability.after_execute' )
			->andReturn(
				[
					'https://example.com/webhook1',
					'https://example.com/webhook2',
				]
			);

		// Expect enqueue to be called for each URL
		$queue->shouldReceive( 'enqueue' )
			->twice()
			->withArgs(
				function ( $url, $payload ) {
					return in_array( $url, [ 'https://example.com/webhook1', 'https://example.com/webhook2' ], true )
					&& $payload instanceof WebhookPayload
					&& 'ability.after_execute' === $payload->event;
				}
			);

		$manager->trigger(
			'ability.after_execute',
			[
				'ability_name'       => 'fa-wpmcp/create-post',
				'category'           => 'posts-pages',
				'operation'          => 'write',
				'user_id'            => 1,
				'user_login'         => 'admin',
				'ip'                 => '192.168.1.1',
				'input'              => [ 'title' => 'Test' ],
				'output'             => [ 'post_id' => 42 ],
				'success'            => true,
				'execution_time_ms'  => 150,
			]
		);
	}

	public function test_trigger_does_nothing_when_no_subscribers(): void {
		$queue  = Mockery::mock( WebhookQueue::class );
		$sender = Mockery::mock( WebhookSender::class );
		$config = Mockery::mock( WebhookConfig::class );

		$manager = new WebhookManager( $queue, $sender, $config );

		// No subscribed URLs
		$config->shouldReceive( 'get_subscribed_urls' )
			->with( 'ability.before_execute' )
			->andReturn( [] );

		// Should not enqueue anything
		$queue->shouldNotReceive( 'enqueue' );

		$manager->trigger(
			'ability.before_execute',
			[
				'ability_name'       => 'fa-wpmcp/list-posts',
				'category'           => 'posts-pages',
				'operation'          => 'read',
				'user_id'            => 1,
				'user_login'         => 'admin',
				'ip'                 => '127.0.0.1',
				'input'              => [],
				'output'             => [],
				'success'            => true,
				'execution_time_ms'  => 50,
			]
		);
	}

	public function test_process_queue_sends_pending_webhooks(): void {
		$queue  = Mockery::mock( WebhookQueue::class );
		$sender = Mockery::mock( WebhookSender::class );
		$config = Mockery::mock( WebhookConfig::class );

		$manager = new WebhookManager( $queue, $sender, $config );

		$pending_webhook = [
			'id'            => 1,
			'url'           => 'https://example.com/webhook',
			'payload'       => '{"event":"test"}',
			'attempt_count' => 0,
		];

		$queue->shouldReceive( 'get_pending' )
			->with( 10 )
			->andReturn( [ $pending_webhook ] );

		$config->shouldReceive( 'get_secret' )
			->andReturn( 'test-secret' );

		$sender->shouldReceive( 'send' )
			->once()
			->withArgs(
				function ( $url, $payload, $signature ) {
					return 'https://example.com/webhook' === $url
					&& '{"event":"test"}' === $payload
					&& str_starts_with( $signature, 'sha256=' );
				}
			)
			->andReturn(
				new WebhookResult(
					is_success: true,
					status_code: 200,
					response_body: 'OK',
					error_message: null,
				)
			);

		$queue->shouldReceive( 'mark_complete' )
			->once()
			->with( 1 );

		$manager->process_queue();
	}

	public function test_process_queue_retries_failed_webhooks(): void {
		$queue  = Mockery::mock( WebhookQueue::class );
		$sender = Mockery::mock( WebhookSender::class );
		$config = Mockery::mock( WebhookConfig::class );

		$manager = new WebhookManager( $queue, $sender, $config );

		$pending_webhook = [
			'id'            => 2,
			'url'           => 'https://example.com/webhook',
			'payload'       => '{"event":"test"}',
			'attempt_count' => 0,
		];

		$queue->shouldReceive( 'get_pending' )
			->with( 10 )
			->andReturn( [ $pending_webhook ] );

		$config->shouldReceive( 'get_secret' )
			->andReturn( 'test-secret' );

		// Webhook send fails
		$sender->shouldReceive( 'send' )
			->once()
			->andReturn(
				new WebhookResult(
					is_success: false,
					status_code: 500,
					response_body: 'Internal Server Error',
					error_message: 'HTTP 500',
				)
			);

		// Should schedule retry (not mark failed yet - attempt_count is 0)
		$queue->shouldReceive( 'schedule_retry' )
			->once()
			->withArgs(
				function ( $id, $next_attempt ) {
					return 2 === $id && $next_attempt instanceof DateTimeImmutable;
				}
			);

		$manager->process_queue();
	}

	public function test_process_queue_marks_failed_after_max_retries(): void {
		$queue  = Mockery::mock( WebhookQueue::class );
		$sender = Mockery::mock( WebhookSender::class );
		$config = Mockery::mock( WebhookConfig::class );

		$manager = new WebhookManager( $queue, $sender, $config );

		// Already attempted 2 times, this is the 3rd (final) attempt
		$pending_webhook = [
			'id'            => 3,
			'url'           => 'https://example.com/webhook',
			'payload'       => '{"event":"test"}',
			'attempt_count' => 2,
		];

		$queue->shouldReceive( 'get_pending' )
			->with( 10 )
			->andReturn( [ $pending_webhook ] );

		$config->shouldReceive( 'get_secret' )
			->andReturn( 'test-secret' );

		// Webhook send fails on final attempt
		$sender->shouldReceive( 'send' )
			->once()
			->andReturn(
				new WebhookResult(
					is_success: false,
					status_code: 404,
					response_body: 'Not Found',
					error_message: 'HTTP 404',
				)
			);

		// Should mark as failed (max retries exceeded)
		$queue->shouldReceive( 'mark_failed' )
			->once()
			->with( 3, 'Max retries exceeded' );

		$manager->process_queue();
	}

	public function test_process_queue_handles_multiple_webhooks(): void {
		$queue  = Mockery::mock( WebhookQueue::class );
		$sender = Mockery::mock( WebhookSender::class );
		$config = Mockery::mock( WebhookConfig::class );

		$manager = new WebhookManager( $queue, $sender, $config );

		$webhooks = [
			[
				'id'            => 1,
				'url'           => 'https://example.com/webhook1',
				'payload'       => '{"event":"test1"}',
				'attempt_count' => 0,
			],
			[
				'id'            => 2,
				'url'           => 'https://example.com/webhook2',
				'payload'       => '{"event":"test2"}',
				'attempt_count' => 0,
			],
		];

		$queue->shouldReceive( 'get_pending' )
			->with( 10 )
			->andReturn( $webhooks );

		$config->shouldReceive( 'get_secret' )
			->andReturn( 'test-secret' );

		// Both webhooks succeed
		$sender->shouldReceive( 'send' )
			->twice()
			->andReturn(
				new WebhookResult(
					is_success: true,
					status_code: 200,
					response_body: 'OK',
					error_message: null,
				)
			);

		$queue->shouldReceive( 'mark_complete' )
			->once()
			->with( 1 );

		$queue->shouldReceive( 'mark_complete' )
			->once()
			->with( 2 );

		$manager->process_queue();
	}
}
