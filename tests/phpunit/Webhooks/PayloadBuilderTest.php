<?php
/**
 * Tests for PayloadBuilder.
 *
 * @package FAWpmcp
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Webhooks;

use FAWpmcp\Webhooks\PayloadBuilder;
use FAWpmcp\ValueObjects\WebhookPayload;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class PayloadBuilderTest extends TestCase {

	public function test_builds_complete_payload(): void {
		$timestamp = new DateTimeImmutable( '2026-01-20 12:00:00' );

		$payload = PayloadBuilder::build(
			event: 'ability.after_execute',
			ability: [
				'name'      => 'fa-wpmcp/create-post',
				'category'  => 'posts-pages',
				'operation' => 'write',
			],
			user: [
				'user_id'    => 1,
				'user_login' => 'admin',
				'ip'         => '192.168.1.1',
			],
			execution: [
				'input'             => [ 'title' => 'Test Post' ],
				'output'            => [ 'post_id' => 42 ],
				'success'           => true,
				'execution_time_ms' => 150,
			],
			timestamp: $timestamp,
		);

		$this->assertInstanceOf( WebhookPayload::class, $payload );
		$this->assertEquals( 'ability.after_execute', $payload->event );
		$this->assertEquals( 'fa-wpmcp/create-post', $payload->ability['name'] );
		$this->assertEquals( 'posts-pages', $payload->ability['category'] );
		$this->assertEquals( 'write', $payload->ability['operation'] );
		$this->assertEquals( 1, $payload->user['id'] );
		$this->assertEquals( 'admin', $payload->user['login'] );
		$this->assertEquals( '192.168.1.1', $payload->user['ip'] );
		$this->assertTrue( $payload->success );
		$this->assertEquals( 150, $payload->execution_time_ms );
	}

	public function test_same_inputs_produce_same_payload(): void {
		$timestamp = new DateTimeImmutable( '2026-01-20 12:00:00' );

		$payload1 = PayloadBuilder::build(
			event: 'ability.after_execute',
			ability: [
				'name'      => 'fa-wpmcp/list-posts',
				'category'  => 'posts-pages',
				'operation' => 'read',
			],
			user: [
				'user_id'    => 1,
				'user_login' => 'admin',
				'ip'         => '127.0.0.1',
			],
			execution: [
				'input'             => [],
				'output'            => [],
				'success'           => true,
				'execution_time_ms' => 100,
			],
			timestamp: $timestamp,
		);

		$payload2 = PayloadBuilder::build(
			event: 'ability.after_execute',
			ability: [
				'name'      => 'fa-wpmcp/list-posts',
				'category'  => 'posts-pages',
				'operation' => 'read',
			],
			user: [
				'user_id'    => 1,
				'user_login' => 'admin',
				'ip'         => '127.0.0.1',
			],
			execution: [
				'input'             => [],
				'output'            => [],
				'success'           => true,
				'execution_time_ms' => 100,
			],
			timestamp: $timestamp,
		);

		// Pure function: same inputs -> same outputs
		$this->assertEquals( $payload1->toJson(), $payload2->toJson() );
	}

	public function test_handles_null_timestamp(): void {
		$before = new DateTimeImmutable();

		$payload = PayloadBuilder::build(
			event: 'ability.before_execute',
			ability: [
				'name'      => 'fa-wpmcp/create-post',
				'category'  => 'posts-pages',
				'operation' => 'write',
			],
			user: [
				'user_id'    => 1,
				'user_login' => 'admin',
				'ip'         => '192.168.1.1',
			],
			execution: [
				'input'             => [],
				'output'            => [],
				'success'           => true,
				'execution_time_ms' => 50,
			],
			timestamp: null,
		);

		$after = new DateTimeImmutable();

		// Should auto-generate timestamp
		$this->assertInstanceOf( DateTimeImmutable::class, $payload->timestamp );
		$this->assertGreaterThanOrEqual( $before->getTimestamp(), $payload->timestamp->getTimestamp() );
		$this->assertLessThanOrEqual( $after->getTimestamp(), $payload->timestamp->getTimestamp() );
	}

	public function test_builds_before_execute_payload(): void {
		$payload = PayloadBuilder::build(
			event: 'ability.before_execute',
			ability: [
				'name'      => 'fa-wpmcp/create-post',
				'category'  => 'posts-pages',
				'operation' => 'write',
			],
			user: [
				'user_id'    => 1,
				'user_login' => 'admin',
				'ip'         => '192.168.1.1',
			],
			execution: [
				'input'             => [ 'title' => 'Test' ],
				'output'            => [],
				'success'           => true,
				'execution_time_ms' => 0,
			],
		);

		$this->assertEquals( 'ability.before_execute', $payload->event );
		$this->assertEmpty( $payload->output );
	}

	public function test_builds_failed_payload(): void {
		$payload = PayloadBuilder::build(
			event: 'ability.failed',
			ability: [
				'name'      => 'fa-wpmcp/delete-post',
				'category'  => 'posts-pages',
				'operation' => 'write',
			],
			user: [
				'user_id'    => 1,
				'user_login' => 'admin',
				'ip'         => '192.168.1.1',
			],
			execution: [
				'input'             => [ 'post_id' => 99 ],
				'output'            => [ 'error' => 'Post not found' ],
				'success'           => false,
				'execution_time_ms' => 25,
			],
		);

		$this->assertEquals( 'ability.failed', $payload->event );
		$this->assertFalse( $payload->success );
		$this->assertEquals( 'Post not found', $payload->output['error'] );
	}
}
