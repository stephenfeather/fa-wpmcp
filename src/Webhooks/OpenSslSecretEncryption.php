<?php
/**
 * OpenSSL-based secret encryption using AES-256-GCM AEAD
 *
 * @package FAWpmcp\Webhooks
 * @since 1.0.0-alpha.4
 */

declare(strict_types=1);

namespace FAWpmcp\Webhooks;

use FAWpmcp\Exceptions\EncryptionException;

/**
 * Encrypts webhook secrets using OpenSSL AES-256-GCM
 *
 * Fallback encryption when libsodium is not available:
 * - AES-256-GCM cipher (256-bit key, 96-bit IV)
 * - HKDF-SHA256 key derivation from WordPress salts
 * - Format: openssl:v1:base64(iv||tag||ciphertext)
 *
 * @since 1.0.0-alpha.4
 */
final class OpenSslSecretEncryption implements SecretEncryption {

	/**
	 * Algorithm prefix for identifying encrypted values
	 */
	private const PREFIX = 'openssl:v1:';

	/**
	 * Cipher algorithm
	 */
	private const CIPHER = 'aes-256-gcm';

	/**
	 * IV length for AES-256-GCM (96 bits / 12 bytes)
	 */
	private const IV_LENGTH = 12;

	/**
	 * Authentication tag length (128 bits / 16 bytes)
	 */
	private const TAG_LENGTH = 16;

	/**
	 * Encryption key derived from WordPress salts
	 *
	 * @var string
	 */
	private string $key;

	/**
	 * Constructor
	 *
	 * @throws EncryptionException If openssl extension not available.
	 */
	public function __construct() {
		if ( ! extension_loaded( 'openssl' ) ) {
			throw new EncryptionException( 'OpenSSL extension not available' );
		}

		if ( ! in_array( self::CIPHER, openssl_get_cipher_methods(), true ) ) {
			throw new EncryptionException( 'AES-256-GCM cipher not available' );
		}

		$this->key = $this->deriveKey();
	}

	/**
	 * Destructor - clear encryption key from memory
	 */
	public function __destruct() {
		if ( isset( $this->key ) ) {
			// Overwrite key with zeros (PHP doesn't have sodium_memzero equivalent).
			$this->key = str_repeat( "\0", strlen( $this->key ) );
			unset( $this->key );
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
			// Generate random IV (96-bit for GCM).
			$iv = random_bytes( self::IV_LENGTH );

			// Encrypt with AEAD.
			$tag        = '';
			$ciphertext = openssl_encrypt(
				$plaintext,
				self::CIPHER,
				$this->key,
				OPENSSL_RAW_DATA,
				$iv,
				$tag,
				'',  // No additional data.
				self::TAG_LENGTH
			);

			if ( false === $ciphertext ) {
				throw new EncryptionException( 'Encryption failed' );
			}

			// Format: iv || tag || ciphertext.
			$encrypted = $iv . $tag . $ciphertext;

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

			// Extract IV, tag, and ciphertext.
			$min_length = self::IV_LENGTH + self::TAG_LENGTH;
			if ( strlen( $encrypted ) < $min_length ) {
				throw new EncryptionException( 'Ciphertext too short' );
			}

			$iv         = substr( $encrypted, 0, self::IV_LENGTH );
			$tag        = substr( $encrypted, self::IV_LENGTH, self::TAG_LENGTH );
			$ciphertext = substr( $encrypted, $min_length );

			// Decrypt and verify authentication tag.
			$plaintext = openssl_decrypt(
				$ciphertext,
				self::CIPHER,
				$this->key,
				OPENSSL_RAW_DATA,
				$iv,
				$tag,
				''  // No additional data.
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
	 * @return bool True if the value has the openssl:v1: prefix.
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

		if ( false === $key || 32 !== strlen( $key ) ) {
			throw new EncryptionException( 'Key derivation failed' );
		}

		return $key;
	}
}
