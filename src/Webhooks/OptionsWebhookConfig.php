<?php
/**
 * WordPress options-based webhook configuration.
 *
 * @package FAWpmcp
 */

declare(strict_types=1);

namespace FAWpmcp\Webhooks;

/**
 * WordPress options webhook configuration implementation.
 *
 * Uses WordPress options API for storing webhook settings.
 */
final class OptionsWebhookConfig implements WebhookConfig {
	/**
	 * Get subscribed URLs for an event.
	 *
	 * @param string $event Event name.
	 *
	 * @return array<int, string> Array of webhook URLs.
	 */
	public function get_subscribed_urls( string $event ): array {
		$all_urls = get_option( 'fa_wpmcp_webhook_urls', array() );

		if ( ! is_array( $all_urls ) ) {
			return array();
		}

		if ( ! isset( $all_urls[ $event ] ) ) {
			return array();
		}

		$urls = $all_urls[ $event ];

		if ( ! is_array( $urls ) ) {
			return array();
		}

		return array_values( $urls );
	}

	/**
	 * Get webhook secret for HMAC signing.
	 *
	 * Auto-generates and stores secret if none exists.
	 *
	 * @return string Secret key.
	 */
	public function get_secret(): string {
		$secret = get_option( 'fa_wpmcp_webhook_secret' );

		if ( $secret && is_string( $secret ) ) {
			return $secret;
		}

		// Generate new secret (32 bytes = 64 hex chars).
		$secret = bin2hex( random_bytes( 32 ) );

		// Store for future use.
		update_option( 'fa_wpmcp_webhook_secret', $secret );

		return $secret;
	}
}
