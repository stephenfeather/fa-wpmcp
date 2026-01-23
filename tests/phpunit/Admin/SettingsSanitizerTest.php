<?php
/**
 * Tests for Admin SettingsSanitizer.
 *
 * @package FAWpmcp\Tests\Admin
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Admin;

use Brain\Monkey;
use Brain\Monkey\Functions;
use FAWpmcp\Admin\SettingsSanitizer;
use PHPUnit\Framework\TestCase;

/**
 * Test Admin SettingsSanitizer functionality.
 */
final class SettingsSanitizerTest extends TestCase {
	/**
	 * Set up Brain\Monkey before each test.
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		Functions\when( 'sanitize_text_field' )->alias(
			static function ( $value ): string {
				return is_string( $value ) ? trim( $value ) : (string) $value;
			}
		);
		Functions\when( 'absint' )->alias(
			static function ( $value ): int {
				return abs( (int) $value );
			}
		);
		Functions\when( 'esc_url_raw' )->alias(
			static function ( $value ): string {
				return is_string( $value ) && str_starts_with( $value, 'http' ) ? $value : '';
			}
		);
	}

	/**
	 * Tear down Brain\Monkey after each test.
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Test sanitizeCategorySettings returns empty array for non-array input.
	 */
	public function test_sanitize_category_settings_returns_empty_for_non_array(): void {
		$sanitizer = new SettingsSanitizer();

		$this->assertSame( array(), $sanitizer->sanitizeCategorySettings( 'nope' ) );
	}

	/**
	 * Test sanitizeCategorySettings sanitizes category keys and flags.
	 */
	public function test_sanitize_category_settings_sanitizes_flags(): void {
		$sanitizer = new SettingsSanitizer();

		$input = array(
			' posts ' => array(
				'enable_read'  => '1',
				'enable_write' => '0',
			),
			'pages'  => array(
				'enable_read'  => '0',
				'enable_write' => '1',
			),
		);

		$expected = array(
			'posts' => array(
				'enable_read'  => true,
				'enable_write' => false,
			),
			'pages' => array(
				'enable_read'  => false,
				'enable_write' => true,
			),
		);

		$this->assertSame( $expected, $sanitizer->sanitizeCategorySettings( $input ) );
	}

	/**
	 * Test sanitizeAbilitySettings sanitizes ability keys and enabled flags.
	 */
	public function test_sanitize_ability_settings_sanitizes_flags(): void {
		$sanitizer = new SettingsSanitizer();

		$input = array(
			' fa-wpmcp/get-post '    => array( 'enabled' => '1' ),
			'fa-wpmcp/update-post'   => array( 'enabled' => '0' ),
			'fa-wpmcp/list-posts'    => array(),
			'fa-wpmcp/delete-posts ' => array( 'enabled' => '1' ),
		);

		$expected = array(
			'fa-wpmcp/get-post'  => array( 'enabled' => true ),
			'fa-wpmcp/update-post' => array( 'enabled' => false ),
			'fa-wpmcp/list-posts'  => array( 'enabled' => false ),
			'fa-wpmcp/delete-posts' => array( 'enabled' => true ),
		);

		$this->assertSame( $expected, $sanitizer->sanitizeAbilitySettings( $input ) );
	}

	/**
	 * Test sanitizeAbilityRateLimits sanitizes ability rate limits.
	 */
	public function test_sanitize_ability_rate_limits_sanitizes_values(): void {
		$sanitizer = new SettingsSanitizer();

		$input = array(
			' fa-wpmcp/get-post ' => array(
				'requests_per_minute' => '5',
				'requests_per_hour'   => -10,
			),
			'fa-wpmcp/list-posts' => array(),
		);

		$expected = array(
			'fa-wpmcp/get-post' => array(
				'requests_per_minute' => 5,
				'requests_per_hour'   => 10,
			),
			'fa-wpmcp/list-posts' => array(
				'requests_per_minute' => 0,
				'requests_per_hour'   => 0,
			),
		);

		$this->assertSame( $expected, $sanitizer->sanitizeAbilityRateLimits( $input ) );
	}

	/**
	 * Test sanitizeWebhookEndpoints filters invalid endpoints and events.
	 */
	public function test_sanitize_webhook_endpoints_filters_invalid(): void {
		$sanitizer = new SettingsSanitizer();

		$input = array(
			array(
				'url'    => 'https://example.com/webhook',
				'events' => array( 'ability.before_execute', 'bad.event', ' ability.failed ' ),
			),
			array(
				'url'    => 'not-a-url',
				'events' => array( 'ability.after_execute' ),
			),
			'not-array',
			array(
				'url'    => 'https://example.com/empty',
				'events' => 'not-array',
			),
		);

		$expected = array(
			array(
				'url'    => 'https://example.com/webhook',
				'events' => array( 'ability.before_execute', 'ability.failed' ),
			),
			array(
				'url'    => 'https://example.com/empty',
				'events' => array(),
			),
		);

		$this->assertSame( $expected, $sanitizer->sanitizeWebhookEndpoints( $input ) );
	}
}
