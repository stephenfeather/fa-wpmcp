<?php

/**
 * Settings Sanitizer for FA WPMCP.
 *
 * @package FAWpmcp\Admin
 */

declare(strict_types=1);

namespace FAWpmcp\Admin;

/**
 * Handles sanitization of settings form inputs.
 *
 * Provides sanitization methods for:
 * - Category permissions
 * - Ability permissions
 * - Rate limit configuration
 * - Webhook endpoint configuration
 *
 * @package FAWpmcp\Admin
 */
final class SettingsSanitizer
{
    /**
     * Available webhook events.
     *
     * @var array<string>
     */
    private const WEBHOOK_EVENTS = array(
        'ability.before_execute',
        'ability.after_execute',
        'ability.failed',
    );

    /**
     * Sanitize category settings.
     *
     * @param mixed $input Raw input data.
     * @return array<string, array<string, bool>> Sanitized settings.
     */
    public function sanitizeCategorySettings($input): array
    {
        if (! is_array($input)) {
            return array();
        }

        $sanitized = array();

        foreach ($input as $category => $settings) {
            $category               = sanitize_text_field($category);
            $sanitized[ $category ] = array(
                'enable_read'  => isset($settings['enable_read']) && '1' === sanitize_text_field($settings['enable_read']),
                'enable_write' => isset($settings['enable_write']) && '1' === sanitize_text_field($settings['enable_write']),
            );
        }

        return $sanitized;
    }

    /**
     * Sanitize ability settings.
     *
     * @param mixed $input Raw input data.
     * @return array<string, array<string, bool>> Sanitized settings.
     */
    public function sanitizeAbilitySettings($input): array
    {
        if (! is_array($input)) {
            return array();
        }

        $sanitized = array();

        foreach ($input as $ability => $settings) {
            $ability               = sanitize_text_field($ability);
            $sanitized[ $ability ] = array(
                'enabled' => isset($settings['enabled']) && '1' === sanitize_text_field($settings['enabled']),
            );
        }

        return $sanitized;
    }

    /**
     * Sanitize ability rate limits.
     *
     * @param mixed $input Raw input data.
     * @return array<string, array<string, int>> Sanitized settings.
     */
    public function sanitizeAbilityRateLimits($input): array
    {
        if (! is_array($input)) {
            return array();
        }

        $sanitized = array();

        foreach ($input as $ability => $limits) {
            $ability               = sanitize_text_field($ability);
            $sanitized[ $ability ] = array(
                'requests_per_minute' => absint($limits['requests_per_minute'] ?? 0),
                'requests_per_hour'   => absint($limits['requests_per_hour'] ?? 0),
            );
        }

        return $sanitized;
    }

    /**
     * Sanitize webhook endpoints.
     *
     * @param mixed $input Raw input data.
     * @return array<array<string, mixed>> Sanitized endpoints.
     */
    public function sanitizeWebhookEndpoints($input): array
    {
        if (! is_array($input)) {
            return array();
        }

        $sanitized = array();

        foreach ($input as $endpoint) {
            $sanitized_endpoint = $this->sanitizeSingleEndpoint($endpoint);
            if (null !== $sanitized_endpoint) {
                $sanitized[] = $sanitized_endpoint;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize a single webhook endpoint.
     *
     * Enforces HTTPS in production environments.
     * Warns but allows HTTP in staging/development environments.
     *
     * @param mixed $endpoint Endpoint data.
     * @return array<string, mixed>|null Sanitized endpoint or null if invalid.
     */
    private function sanitizeSingleEndpoint($endpoint): ?array
    {
        if (! is_array($endpoint)) {
            return null;
        }

        $url = isset($endpoint['url']) ? esc_url_raw($endpoint['url']) : '';
        if (empty($url)) {
            return null;
        }

        // Check HTTPS requirement based on environment.
        $https_result = $this->validateWebhookUrlHttps($url);
        if (false === $https_result) {
            // Production: reject non-HTTPS URLs.
            return null;
        }

        return array(
            'url'    => $url,
            'events' => $this->sanitizeWebhookEvents($endpoint['events'] ?? null),
        );
    }

    /**
     * Validate webhook URL HTTPS requirement based on environment.
     *
     * - Production: HTTPS required (returns false for HTTP)
     * - Staging/Development/Local: HTTPS recommended, HTTP allowed with warning
     *
     * @param string $url The webhook URL to validate.
     * @return bool True if URL is acceptable, false if rejected.
     */
    private function validateWebhookUrlHttps(string $url): bool
    {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Using native for consistency.
        $parsed = parse_url($url);
        $scheme = isset($parsed['scheme']) ? strtolower($parsed['scheme']) : '';

        // HTTPS is always acceptable.
        if ('https' === $scheme) {
            return true;
        }

        // HTTP handling depends on environment.
        if ('http' === $scheme) {
            $environment = $this->getEnvironmentType();

            if ('production' === $environment) {
                // Production: reject HTTP webhooks.
                $this->addHttpsWarningNotice($url, true);
                return false;
            }

            // Staging/Development/Local: allow with warning.
            $this->addHttpsWarningNotice($url, false);
            return true;
        }

        // Non-http/https schemes are rejected.
        return false;
    }

    /**
     * Get the current WordPress environment type.
     *
     * @return string Environment type: 'production', 'staging', 'development', or 'local'.
     */
    private function getEnvironmentType(): string
    {
        if (function_exists('wp_get_environment_type')) {
            return wp_get_environment_type();
        }

        // Fallback: check WP_ENVIRONMENT_TYPE constant.
        if (defined('WP_ENVIRONMENT_TYPE')) {
            return WP_ENVIRONMENT_TYPE;
        }

        // Default to production for safety.
        return 'production';
    }

    /**
     * Add an admin notice about HTTP webhook URLs.
     *
     * @param string $url      The HTTP URL that triggered the warning.
     * @param bool   $rejected Whether the URL was rejected (production) or just warned (non-production).
     * @return void
     */
    private function addHttpsWarningNotice(string $url, bool $rejected): void
    {
        $host = wp_parse_url($url, PHP_URL_HOST) ?? $url;

        if ($rejected) {
            $message = sprintf(
                /* translators: %s: webhook host */
                __('Webhook URL for %s was rejected: HTTPS is required in production environments.', 'fa-wpmcp'),
                '<code>' . esc_html($host) . '</code>'
            );
            $type = 'error';
        } else {
            $message = sprintf(
                /* translators: %s: webhook host */
                __('Warning: Webhook URL for %s uses HTTP instead of HTTPS. This is insecure and will be rejected in production.', 'fa-wpmcp'),
                '<code>' . esc_html($host) . '</code>'
            );
            $type = 'warning';
        }

        add_settings_error(
            'fa_wpmcp_webhooks',
            'webhook_https_' . sanitize_title($host),
            $message,
            $type
        );
    }

    /**
     * Sanitize webhook events array.
     *
     * @param mixed $events Raw events data.
     * @return array<string> Sanitized event names.
     */
    private function sanitizeWebhookEvents($events): array
    {
        if (! is_array($events)) {
            return array();
        }

        $sanitized = array();
        foreach ($events as $event) {
            $event = sanitize_text_field($event);
            if (in_array($event, self::WEBHOOK_EVENTS, true)) {
                $sanitized[] = $event;
            }
        }

        return $sanitized;
    }
}
