<?php
/**
 * Database-backed webhook queue implementation.
 *
 * @package FAWpmcp
 */

declare(strict_types=1);

namespace FAWpmcp\Webhooks;

use FAWpmcp\ValueObjects\WebhookPayload;
use DateTimeImmutable;

/**
 * Database webhook queue implementation.
 *
 * Uses wp_fa_wpmcp_webhook_queue table for persistence.
 */
final class DatabaseWebhookQueue implements WebhookQueue {
	/**
	 * MySQL datetime format for timestamps.
	 *
	 * @var string
	 */
	private const MYSQL_DATETIME_FORMAT = 'Y-m-d H:i:s';

	/**
	 * Enqueue a webhook for delivery.
	 *
	 * @param string         $url     Webhook URL.
	 * @param WebhookPayload $payload Payload to send.
	 */
	public function enqueue( string $url, WebhookPayload $payload ): void {
		global $wpdb;

		$table = $wpdb->prefix . 'fa_wpmcp_webhook_queue';

		// Generate signature for storage.
		$json_payload = $payload->toJson();

		$wpdb->insert(
			$table,
			array(
				'url'        => $url,
				'event_type' => $payload->event,
				'payload'    => $json_payload,
				'signature'  => '', // Will be generated during send.
				'status'     => 'pending',
				'created_at' => gmdate( self::MYSQL_DATETIME_FORMAT ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Get pending webhooks.
	 *
	 * @param int $limit Maximum number to retrieve.
	 *
	 * @return array<int, array{id: int, url: string, payload: string, attempt_count: int}>
	 */
	public function getPending( int $limit ): array {
		global $wpdb;

		$table = $wpdb->prefix . 'fa_wpmcp_webhook_queue';
		$now   = gmdate( self::MYSQL_DATETIME_FORMAT );
		$sql   = "SELECT id, url, payload, retry_count
            FROM $table
            WHERE status = 'pending'
            AND (next_retry_at IS NULL OR next_retry_at <= %s)
            ORDER BY created_at ASC
            LIMIT %d";

        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		// Table name is constructed from prefix, not user input.
		$results = $wpdb->get_results(
			$wpdb->prepare( $sql, $now, $limit ),
			ARRAY_A
		);
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

		if ( ! $results ) {
			return array();
		}

		// Map retry_count to attempt_count for interface compliance.
		return array_map(
			fn( $row ) => array(
				'id'            => (int) $row['id'],
				'url'           => $row['url'],
				'payload'       => $row['payload'],
				'attempt_count' => (int) $row['retry_count'],
			),
			$results
		);
	}

	/**
	 * Mark webhook as successfully delivered.
	 *
	 * @param int $id Webhook ID.
	 */
	public function markComplete( int $id ): void {
		global $wpdb;

		$table = $wpdb->prefix . 'fa_wpmcp_webhook_queue';

		$wpdb->update(
			$table,
			array(
				'status'       => 'completed',
				'completed_at' => gmdate( self::MYSQL_DATETIME_FORMAT ),
			),
			array( 'id' => $id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Mark webhook as failed after max retries.
	 *
	 * @param int    $id            Webhook ID.
	 * @param string $error_message Error message.
	 */
	public function markFailed( int $id, string $error_message ): void {
		global $wpdb;

		$table = $wpdb->prefix . 'fa_wpmcp_webhook_queue';

		$wpdb->update(
			$table,
			array(
				'status'        => 'failed',
				'error_message' => $error_message,
			),
			array( 'id' => $id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Schedule webhook for retry.
	 *
	 * @param int               $id           Webhook ID.
	 * @param DateTimeImmutable $next_attempt Next attempt time.
	 */
	public function scheduleRetry( int $id, DateTimeImmutable $next_attempt ): void {
		global $wpdb;

		$table = $wpdb->prefix . 'fa_wpmcp_webhook_queue';
		$sql   = "UPDATE $table
            SET retry_count = retry_count + 1,
                next_retry_at = %s
            WHERE id = %d";

        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		// Table name is constructed from prefix, not user input.
		// Increment retry count.
		$wpdb->query(
			$wpdb->prepare(
				$sql,
				$next_attempt->format( self::MYSQL_DATETIME_FORMAT ),
				$id
			)
		);
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
	}
}
