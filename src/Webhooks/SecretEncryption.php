<?php
/**
 * Secret Encryption Interface
 *
 * @package FAWpmcp\Webhooks
 * @since 1.0.0-alpha.4
 */

declare(strict_types=1);

namespace FAWpmcp\Webhooks;

/**
 * Interface for encrypting and decrypting webhook secrets
 *
 * Implementations must provide authenticated encryption (AEAD) to ensure
 * both confidentiality and integrity of encrypted secrets.
 *
 * @since 1.0.0-alpha.4
 */
interface SecretEncryption {
	/**
	 * Encrypt a plaintext secret
	 *
	 * @param string $plaintext The plaintext secret to encrypt.
	 * @return string The encrypted secret with algorithm prefix (e.g., "sodium:v1:...").
	 * @throws \RuntimeException If encryption fails.
	 */
	public function encrypt( string $plaintext ): string;

	/**
	 * Decrypt an encrypted secret
	 *
	 * @param string $ciphertext The encrypted secret to decrypt.
	 * @return string The decrypted plaintext secret.
	 * @throws \RuntimeException If decryption fails or ciphertext is tampered.
	 */
	public function decrypt( string $ciphertext ): string;

	/**
	 * Check if a value is encrypted
	 *
	 * @param string $value The value to check.
	 * @return bool True if the value appears to be encrypted by this implementation.
	 */
	public function isEncrypted( string $value ): bool;
}
