<?php

/**
 * Tests for WebhookPayload value object.
 *
 * @package FAWpmcp
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\ValueObjects;

use FAWpmcp\ValueObjects\WebhookPayload;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class WebhookPayloadTest extends TestCase {

	public function test_webhook_payload_is_immutable(): void {
		$timestamp = new DateTimeImmutable( '2026-01-20 12:00:00' );

		$payload = new WebhookPayload(
			event: 'ability.after_execute',
			timestamp: $timestamp,
			ability: [
				'name' => 'fa-wpmcp/create-post',
				'category' => 'posts-pages',
				'operation' => 'write',
			],
			user: [
				'id' => 1,
				'login' => 'admin',
				'ip' => '192.168.1.1',
			],
			input: [ 'title' => 'Test Post' ],
			output: [ 'post_id' => 42 ],
			success: true,
			execution_time_ms: 150,
		);

		$this->assertEquals( 'ability.after_execute', $payload->event );
		$this->assertEquals( $timestamp, $payload->timestamp );
		$this->assertEquals( 'fa-wpmcp/create-post', $payload->ability['name'] );
		$this->assertTrue( $payload->success );
	}

	public function test_to_array_returns_complete_structure(): void {
		$timestamp = new DateTimeImmutable( '2026-01-20 12:00:00' );

		$payload = new WebhookPayload(
			event: 'ability.after_execute',
			timestamp: $timestamp,
			ability: [
				'name' => 'fa-wpmcp/create-post',
				'category' => 'posts-pages',
				'operation' => 'write',
			],
			user: [
				'id' => 1,
				'login' => 'admin',
				'ip' => '192.168.1.1',
			],
			input: [ 'title' => 'Test Post' ],
			output: [ 'post_id' => 42 ],
			success: true,
			execution_time_ms: 150,
		);

		$array = $payload->toArray();

		$this->assertIsArray( $array );
		$this->assertEquals( 'ability.after_execute', $array['event'] );
		$this->assertEquals( '2026-01-20T12:00:00+00:00', $array['timestamp'] );
		$this->assertEquals( 'fa-wpmcp/create-post', $array['ability']['name'] );
		$this->assertEquals( 1, $array['user']['id'] );
		$this->assertTrue( $array['success'] );
		$this->assertEquals( 150, $array['execution_time_ms'] );
	}

	public function test_to_json_returns_valid_json(): void {
		$timestamp = new DateTimeImmutable( '2026-01-20 12:00:00' );

		$payload = new WebhookPayload(
			event: 'ability.before_execute',
			timestamp: $timestamp,
			ability: [
				'name' => 'fa-wpmcp/list-posts',
				'category' => 'posts-pages',
				'operation' => 'read',
			],
			user: [
				'id' => 2,
				'login' => 'editor',
				'ip' => '127.0.0.1',
			],
			input: [ 'per_page' => 10 ],
			output: [],
			success: true,
			execution_time_ms: 50,
		);

		$json = $payload->toJson();

		$this->assertJson( $json );
		$decoded = json_decode( $json, true );
		$this->assertEquals( 'ability.before_execute', $decoded['event'] );
		$this->assertEquals( 'fa-wpmcp/list-posts', $decoded['ability']['name'] );
	}

	public function test_handles_failed_execution(): void {
		$timestamp = new DateTimeImmutable( '2026-01-20 12:00:00' );

		$payload = new WebhookPayload(
			event: 'ability.failed',
			timestamp: $timestamp,
			ability: [
				'name' => 'fa-wpmcp/delete-post',
				'category' => 'posts-pages',
				'operation' => 'write',
			],
			user: [
				'id' => 1,
				'login' => 'admin',
				'ip' => '192.168.1.1',
			],
			input: [ 'post_id' => 99 ],
			output: [ 'error' => 'Post not found' ],
			success: false,
			execution_time_ms: 25,
		);

		$this->assertEquals( 'ability.failed', $payload->event );
		$this->assertFalse( $payload->success );
		$this->assertEquals( 'Post not found', $payload->output['error'] );
	}
}
