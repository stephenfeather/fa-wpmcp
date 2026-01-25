<?php
/**
 * Sodium-based secret encryption using XChaCha20-Poly1305 AEAD
 *
 * @package FAWpmcp\Webhooks
 * @since 1.0.0-alpha.4
 */

declare(strict_types=1);

namespace FAWpmcp\Webhooks;

use FAWpmcp\Exceptions\EncryptionException;

/**
 * Encrypts webhook secrets using libsodium XChaCha20-Poly1305
 *
 * Provides authenticated encryption (AEAD) with high security:
 * - XChaCha20-Poly1305 cipher (256-bit key, 192-bit nonce)
 * - HKDF-SHA256 key derivation from WordPress salts
 * - Format: sodium:v1:base64(nonce||ciphertext||tag)
 *
 * @since 1.0.0-alpha.4
 */
final class SodiumSecretEncryption implements SecretEncryption {
	/**
	 * Algorithm prefix for identifying encrypted values
	 */
	private const PREFIX = 'sodium:v1:';

	/**
	 * Encryption key derived from WordPress salts
	 *
	 * @var string
	 */
	private string $key;

	/**
	 * Constructor
	 *
	 * @throws EncryptionException If sodium extension not available.
	 */
	public function __construct() {
		if ( ! extension_loaded( 'sodium' ) ) {
			throw new EncryptionException( 'Sodium extension not available' );
		}

		$this->key = $this->deriveKey();
	}

	/**
	 * Destructor - clear encryption key from memory
	 */
	public function __destruct() {
		if ( isset( $this->key ) ) {
			sodium_memzero( $this->key );
		}
	}

	/**
	 * Encrypt a plaintext secret
	 *
	 * @param string $plaintext The plaintext secret to encrypt.
	 * @return string The encrypted secret with prefix.
	 * @throws EncryptionException If encryption fails.
	 */
	public function encrypt( string $plaintext ): string {
		try {
			// Generate random nonce (192-bit for XChaCha20).
			$nonce = random_bytes( SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES );

			// Encrypt with AEAD (includes authentication tag).
			$ciphertext = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt(
				$plaintext,
				'',  // No additional data.
				$nonce,
				$this->key
			);

			// Clear plaintext from memory.
			sodium_memzero( $plaintext );

			// Format: nonce || ciphertext (tag is embedded).
			$encrypted = $nonce . $ciphertext;

			return self::PREFIX . base64_encode( $encrypted );
		} catch ( \Throwable $e ) {
			throw new EncryptionException( 'Encryption failed: ' . $e->getMessage(), 0, $e );
		}
	}

	/**
	 * Decrypt an encrypted secret
	 *
	 * @param string $ciphertext The encrypted secret to decrypt.
	 * @return string The decrypted plaintext secret.
	 * @throws EncryptionException If decryption fails or ciphertext is tampered.
	 */
	public function decrypt( string $ciphertext ): string {
		if ( ! $this->isEncrypted( $ciphertext ) ) {
			throw new EncryptionException( 'Invalid ciphertext format' );
		}

		try {
			// Remove prefix and decode.
			$encrypted = base64_decode( substr( $ciphertext, strlen( self::PREFIX ) ), true );

			if ( false === $encrypted ) {
				throw new EncryptionException( 'Invalid base64 encoding' );
			}

			// Extract nonce and ciphertext.
			$nonce_length = SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES;
			if ( strlen( $encrypted ) < $nonce_length ) {
				throw new EncryptionException( 'Ciphertext too short' );
			}

			$nonce      = substr( $encrypted, 0, $nonce_length );
			$ciphertext = substr( $encrypted, $nonce_length );

			// Decrypt and verify authentication tag.
			$plaintext = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
				$ciphertext,
				'',  // No additional data.
				$nonce,
				$this->key
			);

			if ( false === $plaintext ) {
				throw new EncryptionException( 'Authentication failed - ciphertext tampered' );
			}

			return $plaintext;
		} catch ( \Throwable $e ) {
			throw new EncryptionException( 'Decryption failed: ' . $e->getMessage(), 0, $e );
		}
	}

	/**
	 * Check if a value is encrypted with this implementation
	 *
	 * @param string $value The value to check.
	 * @return bool True if the value has the sodium:v1: prefix.
	 */
	public function isEncrypted( string $value ): bool {
		return str_starts_with( $value, self::PREFIX );
	}

	/**
	 * Derive 256-bit encryption key from WordPress salts using HKDF
	 *
	 * Uses multiple WordPress salts to ensure sufficient entropy:
	 * - SECURE_AUTH_KEY
	 * - LOGGED_IN_KEY
	 * - NONCE_SALT
	 *
	 * @return string 256-bit (32-byte) encryption key.
	 * @throws EncryptionException If salts not defined or HKDF fails.
	 */
	private function deriveKey(): string {
		// Collect WordPress salts.
		$salts = array(
			defined( 'SECURE_AUTH_KEY' ) ? SECURE_AUTH_KEY : '',
			defined( 'LOGGED_IN_KEY' ) ? LOGGED_IN_KEY : '',
			defined( 'NONCE_SALT' ) ? NONCE_SALT : '',
		);

		// Verify salts are defined.
		foreach ( $salts as $salt ) {
			if ( empty( $salt ) || 'put your unique phrase here' === $salt ) {
				throw new EncryptionException( 'WordPress salts not properly configured' );
			}
		}

		// Combine salts as input key material.
		$ikm = implode( '|', $salts );

		// Derive key using HKDF-SHA256.
		$key = hash_hkdf(
			'sha256',
			$ikm,
			32,  // 256-bit key.
			'fa-wpmcp-webhook-secret-encryption',  // Context string.
			''   // No salt (IKM already has high entropy).
		);

		if ( false === $key || strlen( $key ) !== SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES ) {
			throw new EncryptionException( 'Key derivation failed' );
		}

		return $key;
	}
}
