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
final class SettingsSanitizerTest extends TestCase
{
    /**
     * Set up Brain\Monkey before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        Functions\when('sanitize_text_field')->alias(
            static function ($value): string {
                return is_string($value) ? trim($value) : (string) $value;
            }
        );
        Functions\when('absint')->alias(
            static function ($value): int {
                return abs((int) $value);
            }
        );
        Functions\when('esc_url_raw')->alias(
            static function ($value): string {
                return is_string($value) && str_starts_with($value, 'http') ? $value : '';
            }
        );
    }

    /**
     * Tear down Brain\Monkey after each test.
     */
    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Test sanitizeCategorySettings returns empty array for non-array input.
     */
    public function test_sanitize_category_settings_returns_empty_for_non_array(): void
    {
        $sanitizer = new SettingsSanitizer();

        $this->assertSame(array(), $sanitizer->sanitizeCategorySettings('nope'));
    }

    /**
     * Test sanitizeCategorySettings sanitizes category keys and flags.
     */
    public function test_sanitize_category_settings_sanitizes_flags(): void
    {
        $sanitizer = new SettingsSanitizer();

        $input = array(
            ' posts ' => array(
                'enable_read'  => '1',
                'enable_write' => '0',
            ),
            'pages'   => array(
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

        $this->assertSame($expected, $sanitizer->sanitizeCategorySettings($input));
    }

    /**
     * Test sanitizeAbilitySettings sanitizes ability keys and enabled flags.
     */
    public function test_sanitize_ability_settings_sanitizes_flags(): void
    {
        $sanitizer = new SettingsSanitizer();

        $input = array(
            ' fa-wpmcp/get-post '    => array( 'enabled' => '1' ),
            'fa-wpmcp/update-post'   => array( 'enabled' => '0' ),
            'fa-wpmcp/list-posts'    => array(),
            'fa-wpmcp/delete-posts ' => array( 'enabled' => '1' ),
        );

        $expected = array(
            'fa-wpmcp/get-post'     => array( 'enabled' => true ),
            'fa-wpmcp/update-post'  => array( 'enabled' => false ),
            'fa-wpmcp/list-posts'   => array( 'enabled' => false ),
            'fa-wpmcp/delete-posts' => array( 'enabled' => true ),
        );

        $this->assertSame($expected, $sanitizer->sanitizeAbilitySettings($input));
    }

    /**
     * Test sanitizeAbilityRateLimits sanitizes ability rate limits.
     */
    public function test_sanitize_ability_rate_limits_sanitizes_values(): void
    {
        $sanitizer = new SettingsSanitizer();

        $input = array(
            ' fa-wpmcp/get-post ' => array(
                'requests_per_minute' => '5',
                'requests_per_hour'   => -10,
            ),
            'fa-wpmcp/list-posts' => array(),
        );

        $expected = array(
            'fa-wpmcp/get-post'   => array(
                'requests_per_minute' => 5,
                'requests_per_hour'   => 10,
            ),
            'fa-wpmcp/list-posts' => array(
                'requests_per_minute' => 0,
                'requests_per_hour'   => 0,
            ),
        );

        $this->assertSame($expected, $sanitizer->sanitizeAbilityRateLimits($input));
    }

    /**
     * Test sanitizeWebhookEndpoints filters invalid endpoints and events.
     */
    public function test_sanitize_webhook_endpoints_filters_invalid(): void
    {
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

        $this->assertSame($expected, $sanitizer->sanitizeWebhookEndpoints($input));
    }

    /**
     * Test HTTPS webhook URLs are always accepted.
     */
    public function test_https_webhook_url_always_accepted(): void
    {
        $sanitizer = new SettingsSanitizer();

        // Mock environment functions - even in production, HTTPS is accepted.
        Functions\when('wp_get_environment_type')->justReturn('production');

        $input = array(
            array(
                'url'    => 'https://secure.example.com/webhook',
                'events' => array( 'ability.after_execute' ),
            ),
        );

        $result = $sanitizer->sanitizeWebhookEndpoints($input);

        $this->assertCount(1, $result);
        $this->assertSame('https://secure.example.com/webhook', $result[0]['url']);
    }

    /**
     * Test HTTP webhook URL is rejected in production environment.
     */
    public function test_http_webhook_url_rejected_in_production(): void
    {
        $sanitizer = new SettingsSanitizer();

        // Mock production environment.
        Functions\when('wp_get_environment_type')->justReturn('production');
        Functions\when('wp_parse_url')->alias('parse_url');
        Functions\when('__')->returnArg();
        Functions\when('esc_html')->returnArg();
        Functions\when('sanitize_title')->returnArg();
        Functions\when('add_settings_error')->justReturn(null);

        $input = array(
            array(
                'url'    => 'http://insecure.example.com/webhook',
                'events' => array( 'ability.after_execute' ),
            ),
        );

        $result = $sanitizer->sanitizeWebhookEndpoints($input);

        // HTTP URL should be rejected in production.
        $this->assertCount(0, $result);
    }

    /**
     * Test HTTP webhook URL is allowed with warning in development environment.
     */
    public function test_http_webhook_url_allowed_in_development(): void
    {
        $sanitizer = new SettingsSanitizer();

        // Mock development environment.
        Functions\when('wp_get_environment_type')->justReturn('development');
        Functions\when('wp_parse_url')->alias('parse_url');
        Functions\when('__')->returnArg();
        Functions\when('esc_html')->returnArg();
        Functions\when('sanitize_title')->returnArg();
        Functions\when('add_settings_error')->justReturn(null);

        $input = array(
            array(
                'url'    => 'http://dev.example.com/webhook',
                'events' => array( 'ability.after_execute' ),
            ),
        );

        $result = $sanitizer->sanitizeWebhookEndpoints($input);

        // HTTP URL should be allowed in development.
        $this->assertCount(1, $result);
        $this->assertSame('http://dev.example.com/webhook', $result[0]['url']);
    }

    /**
     * Test HTTP webhook URL is allowed with warning in staging environment.
     */
    public function test_http_webhook_url_allowed_in_staging(): void
    {
        $sanitizer = new SettingsSanitizer();

        // Mock staging environment.
        Functions\when('wp_get_environment_type')->justReturn('staging');
        Functions\when('wp_parse_url')->alias('parse_url');
        Functions\when('__')->returnArg();
        Functions\when('esc_html')->returnArg();
        Functions\when('sanitize_title')->returnArg();
        Functions\when('add_settings_error')->justReturn(null);

        $input = array(
            array(
                'url'    => 'http://staging.example.com/webhook',
                'events' => array( 'ability.after_execute' ),
            ),
        );

        $result = $sanitizer->sanitizeWebhookEndpoints($input);

        // HTTP URL should be allowed in staging.
        $this->assertCount(1, $result);
        $this->assertSame('http://staging.example.com/webhook', $result[0]['url']);
    }

    /**
     * Test HTTP webhook URL is allowed with warning in local environment.
     */
    public function test_http_webhook_url_allowed_in_local(): void
    {
        $sanitizer = new SettingsSanitizer();

        // Mock local environment.
        Functions\when('wp_get_environment_type')->justReturn('local');
        Functions\when('wp_parse_url')->alias('parse_url');
        Functions\when('__')->returnArg();
        Functions\when('esc_html')->returnArg();
        Functions\when('sanitize_title')->returnArg();
        Functions\when('add_settings_error')->justReturn(null);

        $input = array(
            array(
                'url'    => 'http://localhost/webhook',
                'events' => array( 'ability.after_execute' ),
            ),
        );

        $result = $sanitizer->sanitizeWebhookEndpoints($input);

        // HTTP URL should be allowed in local.
        $this->assertCount(1, $result);
        $this->assertSame('http://localhost/webhook', $result[0]['url']);
    }

    /**
     * Test non-http/https webhook URL schemes are rejected.
     */
    public function test_invalid_scheme_webhook_url_rejected(): void
    {
        $sanitizer = new SettingsSanitizer();

        // ftp:// URLs should be rejected regardless of environment.
        Functions\when('wp_get_environment_type')->justReturn('development');
        Functions\when('esc_url_raw')->alias(
            static function ($value): string {
                // Allow ftp URLs to pass sanitization for this test.
                return is_string($value) ? $value : '';
            }
        );

        $input = array(
            array(
                'url'    => 'ftp://files.example.com/webhook',
                'events' => array( 'ability.after_execute' ),
            ),
        );

        $result = $sanitizer->sanitizeWebhookEndpoints($input);

        // FTP URL should be rejected.
        $this->assertCount(0, $result);
    }

    /**
     * Test sanitizeAbilitySettings returns empty array for non-array input.
     */
    public function test_sanitize_ability_settings_returns_empty_for_non_array(): void
    {
        $sanitizer = new SettingsSanitizer();

        $this->assertSame(array(), $sanitizer->sanitizeAbilitySettings('not-an-array'));
        $this->assertSame(array(), $sanitizer->sanitizeAbilitySettings(null));
        $this->assertSame(array(), $sanitizer->sanitizeAbilitySettings(123));
    }

    /**
     * Test sanitizeAbilityRateLimits returns empty array for non-array input.
     */
    public function test_sanitize_ability_rate_limits_returns_empty_for_non_array(): void
    {
        $sanitizer = new SettingsSanitizer();

        $this->assertSame(array(), $sanitizer->sanitizeAbilityRateLimits('not-an-array'));
        $this->assertSame(array(), $sanitizer->sanitizeAbilityRateLimits(null));
        $this->assertSame(array(), $sanitizer->sanitizeAbilityRateLimits(123));
    }

    /**
     * Test sanitizeWebhookEndpoints returns empty array for non-array input.
     */
    public function test_sanitize_webhook_endpoints_returns_empty_for_non_array(): void
    {
        $sanitizer = new SettingsSanitizer();

        $this->assertSame(array(), $sanitizer->sanitizeWebhookEndpoints('not-an-array'));
        $this->assertSame(array(), $sanitizer->sanitizeWebhookEndpoints(null));
        $this->assertSame(array(), $sanitizer->sanitizeWebhookEndpoints(123));
    }

    /**
     * Test webhook endpoint with empty URL is rejected.
     */
    public function test_webhook_endpoint_with_empty_url_rejected(): void
    {
        $sanitizer = new SettingsSanitizer();

        $input = array(
            array(
                'url'    => '',
                'events' => array( 'ability.after_execute' ),
            ),
            array(
                'url'    => null,
                'events' => array( 'ability.after_execute' ),
            ),
        );

        $result = $sanitizer->sanitizeWebhookEndpoints($input);

        $this->assertCount(0, $result);
    }

    /**
     * Test webhook endpoint with missing URL key is rejected.
     */
    public function test_webhook_endpoint_with_missing_url_rejected(): void
    {
        $sanitizer = new SettingsSanitizer();

        $input = array(
            array(
                'events' => array( 'ability.after_execute' ),
            ),
        );

        $result = $sanitizer->sanitizeWebhookEndpoints($input);

        $this->assertCount(0, $result);
    }

    /**
     * Test webhook events with non-string values are filtered out.
     */
    public function test_webhook_events_with_non_string_values_filtered(): void
    {
        // Override the sanitize_text_field mock to handle non-string values gracefully.
        Functions\when('sanitize_text_field')->alias(
            static function ($value): string {
                if (is_array($value)) {
                    return '';
                }
                return is_string($value) ? trim($value) : (string) $value;
            }
        );

        $sanitizer = new SettingsSanitizer();

        $input = array(
            array(
                'url'    => 'https://example.com/webhook',
                'events' => array(
                    'ability.before_execute',
                    123,
                    null,
                    array( 'nested' ),
                    'ability.after_execute',
                ),
            ),
        );

        $result = $sanitizer->sanitizeWebhookEndpoints($input);

        $this->assertCount(1, $result);
        // Only valid string events that are in the whitelist should remain.
        $this->assertContains('ability.before_execute', $result[0]['events']);
        $this->assertContains('ability.after_execute', $result[0]['events']);
        $this->assertCount(2, $result[0]['events']);
    }

    /**
     * Test rate limits with string values are converted to integers.
     */
    public function test_rate_limits_with_string_values_converted(): void
    {
        $sanitizer = new SettingsSanitizer();

        $input = array(
            'fa-wpmcp/get-post' => array(
                'requests_per_minute' => '15',
                'requests_per_hour'   => '100',
            ),
        );

        $expected = array(
            'fa-wpmcp/get-post' => array(
                'requests_per_minute' => 15,
                'requests_per_hour'   => 100,
            ),
        );

        $this->assertSame($expected, $sanitizer->sanitizeAbilityRateLimits($input));
    }

    /**
     * Test rate limits with float values are truncated to integers.
     */
    public function test_rate_limits_with_float_values_truncated(): void
    {
        $sanitizer = new SettingsSanitizer();

        $input = array(
            'fa-wpmcp/get-post' => array(
                'requests_per_minute' => 5.9,
                'requests_per_hour'   => 10.1,
            ),
        );

        $expected = array(
            'fa-wpmcp/get-post' => array(
                'requests_per_minute' => 5,
                'requests_per_hour'   => 10,
            ),
        );

        $this->assertSame($expected, $sanitizer->sanitizeAbilityRateLimits($input));
    }

    /**
     * Test multiple webhook endpoints are all processed.
     */
    public function test_multiple_webhook_endpoints_processed(): void
    {
        $sanitizer = new SettingsSanitizer();

        $input = array(
            array(
                'url'    => 'https://example1.com/webhook',
                'events' => array( 'ability.before_execute' ),
            ),
            array(
                'url'    => 'https://example2.com/webhook',
                'events' => array( 'ability.after_execute' ),
            ),
            array(
                'url'    => 'https://example3.com/webhook',
                'events' => array( 'ability.failed' ),
            ),
        );

        $result = $sanitizer->sanitizeWebhookEndpoints($input);

        $this->assertCount(3, $result);
        $this->assertSame('https://example1.com/webhook', $result[0]['url']);
        $this->assertSame('https://example2.com/webhook', $result[1]['url']);
        $this->assertSame('https://example3.com/webhook', $result[2]['url']);
    }

    /**
     * Test category settings with missing enable flags default to false.
     */
    public function test_category_settings_missing_flags_default_false(): void
    {
        $sanitizer = new SettingsSanitizer();

        $input = array(
            'posts' => array(),
            'pages' => array( 'enable_read' => '1' ),
        );

        $expected = array(
            'posts' => array(
                'enable_read'  => false,
                'enable_write' => false,
            ),
            'pages' => array(
                'enable_read'  => true,
                'enable_write' => false,
            ),
        );

        $this->assertSame($expected, $sanitizer->sanitizeCategorySettings($input));
    }

    /**
     * Test HTTPS warning notice is added for HTTP URLs in development.
     */
    public function test_http_url_adds_warning_notice_in_development(): void
    {
        $sanitizer = new SettingsSanitizer();

        Functions\when('wp_get_environment_type')->justReturn('development');
        Functions\when('wp_parse_url')->alias('parse_url');
        Functions\when('__')->returnArg();
        Functions\when('esc_html')->returnArg();
        Functions\when('sanitize_title')->returnArg();

        // Capture the add_settings_error call.
        $notice_captured = false;
        $notice_type     = null;
        Functions\expect('add_settings_error')
            ->once()
            ->andReturnUsing(function ($setting, $code, $message, $type) use (&$notice_captured, &$notice_type) {
                $notice_captured = true;
                $notice_type     = $type;
            });

        $input = array(
            array(
                'url'    => 'http://dev.example.com/webhook',
                'events' => array( 'ability.after_execute' ),
            ),
        );

        $result = $sanitizer->sanitizeWebhookEndpoints($input);

        // HTTP URL should be allowed in development.
        $this->assertCount(1, $result);
        // Warning notice should have been added.
        $this->assertTrue($notice_captured);
        $this->assertSame('warning', $notice_type);
    }

    /**
     * Test HTTPS rejection notice is added for HTTP URLs in production.
     */
    public function test_http_url_adds_error_notice_in_production(): void
    {
        $sanitizer = new SettingsSanitizer();

        Functions\when('wp_get_environment_type')->justReturn('production');
        Functions\when('wp_parse_url')->alias('parse_url');
        Functions\when('__')->returnArg();
        Functions\when('esc_html')->returnArg();
        Functions\when('sanitize_title')->returnArg();

        // Capture the add_settings_error call.
        $notice_captured = false;
        $notice_type     = null;
        Functions\expect('add_settings_error')
            ->once()
            ->andReturnUsing(function ($setting, $code, $message, $type) use (&$notice_captured, &$notice_type) {
                $notice_captured = true;
                $notice_type     = $type;
            });

        $input = array(
            array(
                'url'    => 'http://insecure.example.com/webhook',
                'events' => array( 'ability.after_execute' ),
            ),
        );

        $result = $sanitizer->sanitizeWebhookEndpoints($input);

        // HTTP URL should be rejected in production.
        $this->assertCount(0, $result);
        // Error notice should have been added.
        $this->assertTrue($notice_captured);
        $this->assertSame('error', $notice_type);
    }
}
