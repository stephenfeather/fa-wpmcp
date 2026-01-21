<?php
/**
 * Tests for WebhookResult value object.
 *
 * @package FAWpmcp\Tests\ValueObjects
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\ValueObjects;

use FAWpmcp\ValueObjects\WebhookResult;
use PHPUnit\Framework\TestCase;

/**
 * Test WebhookResult value object.
 */
final class WebhookResultTest extends TestCase {
	/**
	 * Test stores provided values.
	 *
	 * @return void
	 */
	public function test_stores_values(): void {
		$result = new WebhookResult(
			is_success: true,
			status_code: 200,
			response_body: 'OK',
			error_message: null,
		);

		$this->assertTrue( $result->is_success );
		$this->assertSame( 200, $result->status_code );
		$this->assertSame( 'OK', $result->response_body );
		$this->assertNull( $result->error_message );
	}
}
