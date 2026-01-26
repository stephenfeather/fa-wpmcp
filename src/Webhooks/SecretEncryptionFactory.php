<?php

/**
 * Factory for creating secret encryption instances
 *
 * @package FAWpmcp\Webhooks
 * @since 1.0.0-alpha.4
 */

declare(strict_types=1);

namespace FAWpmcp\Webhooks;

use FAWpmcp\Exceptions\EncryptionException;

/**
 * Creates appropriate SecretEncryption implementation based on available extensions
 *
 * Priority:
 * 1. Sodium (libsodium) - XChaCha20-Poly1305 (preferred)
 * 2. OpenSSL - AES-256-GCM (fallback)
 *
 * @since 1.0.0-alpha.4
 */
final class SecretEncryptionFactory
{
    /**
     * Create a SecretEncryption instance
     *
     * Selects the best available encryption implementation:
     * - Prefers libsodium if available (more modern, better performance)
     * - Falls back to OpenSSL if sodium not available
     *
     * @return SecretEncryption The encryption instance.
     * @throws EncryptionException If no encryption extension available.
     */
    public static function create(): SecretEncryption
    {
        // Prefer libsodium (modern, faster, simpler API).
        if (extension_loaded('sodium')) {
            return new SodiumSecretEncryption();
        }

        // Fallback to OpenSSL (widely available).
        if (extension_loaded('openssl')) {
            return new OpenSslSecretEncryption();
        }

        throw new EncryptionException(
            'No encryption extension available. Install libsodium or enable OpenSSL.'
        );
    }
}
