<?php

/**
 * Tests for DatabaseWebhookQueue.
 *
 * @package FAWpmcp\Tests\Webhooks
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Webhooks;

use Brain\Monkey\Functions;
use DateTimeImmutable;
use FAWpmcp\ValueObjects\WebhookPayload;
use FAWpmcp\Webhooks\DatabaseWebhookQueue;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test DatabaseWebhookQueue behavior.
 */
final class DatabaseWebhookQueueTest extends TestCase {

	use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

	protected function setUp(): void {
		parent::setUp();
		\Brain\Monkey\setUp();

		if ( ! defined( 'ARRAY_A' ) ) {
			define( 'ARRAY_A', 'ARRAY_A' );
		}
	}

	protected function tearDown(): void {
		\Brain\Monkey\tearDown();
		parent::tearDown();
		unset( $GLOBALS['wpdb'] );
	}

	/**
	 * Test enqueue inserts pending record.
	 *
	 * @return void
	 */
	public function test_enqueue_inserts_pending_record(): void {
		$payload = new WebhookPayload(
			event: 'ability.executed',
			timestamp: new DateTimeImmutable( '2026-01-21 10:00:00' ),
			ability: array( 'name' => 'fa-wpmcp/list-posts' ),
			user: array( 'id' => 1 ),
			input: array(),
			output: array(),
			success: true,
			execution_time_ms: 10,
		);

		$wpdb = Mockery::mock();
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'insert' )
			->once()
			->with(
				'wp_fa_wpmcp_webhook_queue',
				Mockery::on(
					function ( $data ) use ( $payload ) {
						return 'https://example.com' === $data['url']
						&& 'ability.executed' === $data['event_type']
						&& $payload->toJson() === $data['payload']
						&& 'pending' === $data['status']
						&& is_string( $data['created_at'] )
						&& '' !== $data['created_at'];
					}
				),
				array( '%s', '%s', '%s', '%s', '%s', '%s' )
			)
			->andReturn( 1 );

		$GLOBALS['wpdb'] = $wpdb;

		$queue = new DatabaseWebhookQueue();
		$queue->enqueue( 'https://example.com', $payload );
	}

	/**
	 * Test get_pending maps retry_count to attempt_count.
	 *
	 * @return void
	 */
	public function test_get_pending_maps_retry_count(): void {
		$wpdb = Mockery::mock();
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'prepare' )
			->once()
			->andReturn( 'prepared' );
		$wpdb->shouldReceive( 'get_results' )
			->once()
			->with( 'prepared', ARRAY_A )
			->andReturn(
				array(
					array(
						'id'          => '5',
						'url'         => 'https://example.com',
						'payload'     => '{}',
						'retry_count' => '2',
					),
				)
			);

		$GLOBALS['wpdb'] = $wpdb;

		$queue  = new DatabaseWebhookQueue();
		$result = $queue->getPending( 10 );

		$this->assertSame( 5, $result[0]['id'] );
		$this->assertSame( 2, $result[0]['attempt_count'] );
	}

	/**
	 * Test get_pending returns empty array when no results.
	 *
	 * @return void
	 */
	public function test_get_pending_returns_empty_when_none(): void {
		$wpdb = Mockery::mock();
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'prepare' )
			->once()
			->andReturn( 'prepared' );
		$wpdb->shouldReceive( 'get_results' )
			->once()
			->with( 'prepared', ARRAY_A )
			->andReturn( false );

		$GLOBALS['wpdb'] = $wpdb;

		$queue = new DatabaseWebhookQueue();
		$this->assertSame( array(), $queue->getPending( 10 ) );
	}

	/**
	 * Test mark_complete updates status.
	 *
	 * @return void
	 */
	public function test_mark_complete_updates_status(): void {
		$wpdb = Mockery::mock();
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'update' )
			->once()
			->with(
				'wp_fa_wpmcp_webhook_queue',
				Mockery::on(
					function ( $data ) {
						return 'completed' === $data['status']
						&& is_string( $data['completed_at'] )
						&& '' !== $data['completed_at'];
					}
				),
				array( 'id' => 10 ),
				array( '%s', '%s' ),
				array( '%d' )
			)
			->andReturn( 1 );

		$GLOBALS['wpdb'] = $wpdb;

		$queue = new DatabaseWebhookQueue();
		$queue->markComplete( 10 );
	}

	/**
	 * Test mark_failed updates error status.
	 *
	 * @return void
	 */
	public function test_mark_failed_updates_error_status(): void {
		$wpdb = Mockery::mock();
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'update' )
			->once()
			->with(
				'wp_fa_wpmcp_webhook_queue',
				array(
					'status'        => 'failed',
					'error_message' => 'bad',
				),
				array( 'id' => 12 ),
				array( '%s', '%s' ),
				array( '%d' )
			)
			->andReturn( 1 );

		$GLOBALS['wpdb'] = $wpdb;

		$queue = new DatabaseWebhookQueue();
		$queue->markFailed( 12, 'bad' );
	}

	/**
	 * Test schedule_retry increments retry count and sets next_retry_at.
	 *
	 * @return void
	 */
	public function test_schedule_retry_updates_next_attempt(): void {
		$next = new DateTimeImmutable( '2026-01-21 10:05:00' );

		$wpdb = Mockery::mock();
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'prepare' )
			->once()
			->andReturn( 'prepared' );
		$wpdb->shouldReceive( 'query' )
			->once()
			->with( 'prepared' )
			->andReturn( 1 );

		$GLOBALS['wpdb'] = $wpdb;

		$queue = new DatabaseWebhookQueue();
		$queue->scheduleRetry( 99, $next );
	}
}
