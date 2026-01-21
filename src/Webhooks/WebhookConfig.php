<?php
/**
 * Webhook configuration interface.
 *
 * @package FAWpmcp
 */

declare(strict_types=1);

namespace FAWpmcp\Webhooks;

/**
 * Interface for webhook configuration.
 *
 * Implementations handle retrieving webhook settings from options, database, etc.
 */
interface WebhookConfig {
    /**
     * Get URLs subscribed to an event.
     *
     * @param string $event Event name (e.g., 'ability.after_execute').
     *
     * @return array<int, string> Array of webhook URLs.
     */
    public function get_subscribed_urls( string $event ): array;

    /**
     * Get webhook secret for HMAC signing.
     *
     * @return string Secret key
     */
    public function get_secret(): string;
}
