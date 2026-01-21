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
final class SettingsSanitizer {
    /**
     * Available webhook events.
     *
     * @var array<string>
     */
    private const WEBHOOK_EVENTS = array(
        'ability.before_execute',
        'ability.after_execute',
        'ability.error',
    );

    /**
     * Sanitize category settings.
     *
     * @param mixed $input Raw input data.
     * @return array<string, array<string, bool>> Sanitized settings.
     */
    public function sanitizeCategorySettings( $input ): array {
        if ( ! is_array( $input ) ) {
            return array();
        }

        $sanitized = array();

        foreach ( $input as $category => $settings ) {
            $category = sanitize_text_field( $category );
            $sanitized[ $category ] = array(
                'enable_read'  => isset( $settings['enable_read'] ) && '1' === sanitize_text_field( $settings['enable_read'] ),
                'enable_write' => isset( $settings['enable_write'] ) && '1' === sanitize_text_field( $settings['enable_write'] ),
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
    public function sanitizeAbilitySettings( $input ): array {
        if ( ! is_array( $input ) ) {
            return array();
        }

        $sanitized = array();

        foreach ( $input as $ability => $settings ) {
            $ability = sanitize_text_field( $ability );
            $sanitized[ $ability ] = array(
                'enabled' => isset( $settings['enabled'] ) && '1' === sanitize_text_field( $settings['enabled'] ),
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
    public function sanitizeAbilityRateLimits( $input ): array {
        if ( ! is_array( $input ) ) {
            return array();
        }

        $sanitized = array();

        foreach ( $input as $ability => $limits ) {
            $ability = sanitize_text_field( $ability );
            $sanitized[ $ability ] = array(
                'requests_per_minute' => absint( $limits['requests_per_minute'] ?? 0 ),
                'requests_per_hour'   => absint( $limits['requests_per_hour'] ?? 0 ),
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
    public function sanitizeWebhookEndpoints( $input ): array {
        if ( ! is_array( $input ) ) {
            return array();
        }

        $sanitized = array();

        foreach ( $input as $endpoint ) {
            $sanitized_endpoint = $this->sanitizeSingleEndpoint( $endpoint );
            if ( $sanitized_endpoint !== null ) {
                $sanitized[] = $sanitized_endpoint;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize a single webhook endpoint.
     *
     * @param mixed $endpoint Endpoint data.
     * @return array<string, mixed>|null Sanitized endpoint or null if invalid.
     */
    private function sanitizeSingleEndpoint( $endpoint ): ?array {
        if ( ! is_array( $endpoint ) ) {
            return null;
        }

        $url = isset( $endpoint['url'] ) ? esc_url_raw( $endpoint['url'] ) : '';
        if ( empty( $url ) ) {
            return null;
        }

        return array(
            'url'    => $url,
            'events' => $this->sanitizeWebhookEvents( $endpoint['events'] ?? null ),
        );
    }

    /**
     * Sanitize webhook events array.
     *
     * @param mixed $events Raw events data.
     * @return array<string> Sanitized event names.
     */
    private function sanitizeWebhookEvents( $events ): array {
        if ( ! is_array( $events ) ) {
            return array();
        }

        $sanitized = array();
        foreach ( $events as $event ) {
            $event = sanitize_text_field( $event );
            if ( in_array( $event, self::WEBHOOK_EVENTS, true ) ) {
                $sanitized[] = $event;
            }
        }

        return $sanitized;
    }
}
