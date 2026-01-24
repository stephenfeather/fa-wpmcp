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
 * Encrypts webhook secrets at rest for security.
 */
final class OptionsWebhookConfig implements WebhookConfig {
	/**
	 * Secret encryption implementation
	 *
	 * @var SecretEncryption
	 */
	private SecretEncryption $encryption;

	/**
	 * Constructor
	 *
	 * @param SecretEncryption|null $encryption Optional encryption implementation (auto-detects if not provided).
	 */
	public function __construct( ?SecretEncryption $encryption = null ) {
		$this->encryption = $encryption ?? SecretEncryptionFactory::create();
	}

	/**
	 * Get subscribed URLs for an event.
	 *
	 * @param string $event Event name.
	 *
	 * @return array<int, string> Array of webhook URLs.
	 */
	public function getSubscribedUrls( string $event ): array {
		$all_urls = get_option( 'fa_wpmcp_webhook_urls', array() );

		// Validate we have proper structure: array -> event key exists -> event value is array.
		if ( ! is_array( $all_urls ) || ! isset( $all_urls[ $event ] ) || ! is_array( $all_urls[ $event ] ) ) {
			return array();
		}

		return array_values( $all_urls[ $event ] );
	}

	/**
	 * Get webhook secret for HMAC signing.
	 *
	 * Auto-generates and stores encrypted secret if none exists.
	 * Lazily encrypts plain text secrets on first read (migration).
	 *
	 * @return string Decrypted secret key.
	 */
	public function getSecret(): string {
		$stored = get_option( 'fa_wpmcp_webhook_secret' );

		if ( $stored && is_string( $stored ) ) {
			// Check if already encrypted.
			if ( $this->encryption->isEncrypted( $stored ) ) {
				// Decrypt and return.
				return $this->encryption->decrypt( $stored );
			}

			// Lazy migration: encrypt plain text secret.
			$encrypted = $this->encryption->encrypt( $stored );
			update_option( 'fa_wpmcp_webhook_secret', $encrypted );
			return $stored;
		}

		// Generate new secret (32 bytes = 64 hex chars).
		$secret = bin2hex( random_bytes( 32 ) );

		// Store encrypted for security.
		update_option(
			'fa_wpmcp_webhook_secret',
			$this->encryption->encrypt( $secret )
		);

		return $secret;
	}
}
