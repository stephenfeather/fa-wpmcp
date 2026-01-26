<?php

/**
 * Webhook Secret Storage Migration
 *
 * Consolidates webhook secrets from dual storage locations into canonical source.
 * Fixes bug where secrets were stored in both fa_wpmcp_webhook_secret and
 * fa_wpmcp_webhooks['webhook_secret'], which could become out of sync.
 *
 * @package FAWpmcp\Database
 * @since 1.0.0-alpha.4
 */

declare(strict_types=1);

namespace FAWpmcp\Database;

/**
 * Migrates webhook secrets to canonical storage location
 *
 * @since 1.0.0-alpha.4
 */
final class SecretStorageMigration {

	/**
	 * Run the migration
	 *
	 * Consolidates secrets from two locations:
	 * - Canonical: fa_wpmcp_webhook_secret (runtime reads)
	 * - Legacy: fa_wpmcp_webhooks['webhook_secret'] (admin writes)
	 *
	 * After migration, only fa_wpmcp_webhook_secret will contain the secret.
	 *
	 * @return void
	 */
	public static function migrate(): void {
		// Skip if already migrated.
		if ( get_option( 'fa_wpmcp_secret_migration_v1', false ) ) {
			return;
		}

		// Get secrets from both locations.
		$canonical = get_option( 'fa_wpmcp_webhook_secret' );
		$webhooks  = get_option( 'fa_wpmcp_webhooks', array() );
		$ui_secret = is_array( $webhooks ) ? ( $webhooks['webhook_secret'] ?? '' ) : '';

		// Prefer canonical source (runtime reads this).
		$final = $canonical ?: $ui_secret;

		// Save to canonical location.
		if ( $final && is_string( $final ) ) {
			update_option( 'fa_wpmcp_webhook_secret', $final );
		}

		// Remove from webhooks array to prevent future drift.
		if ( is_array( $webhooks ) && isset( $webhooks['webhook_secret'] ) ) {
			unset( $webhooks['webhook_secret'] );
			update_option( 'fa_wpmcp_webhooks', $webhooks );
		}

		// Mark migration complete.
		update_option( 'fa_wpmcp_secret_migration_v1', true );
	}
}
