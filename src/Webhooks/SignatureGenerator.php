<?php

/**
 * HMAC signature generation for webhooks.
 *
 * @package FAWpmcp
 */

declare(strict_types=1);

namespace FAWpmcp\Webhooks;

/**
 * Pure functions for HMAC signature generation and verification.
 *
 * All methods are static and side-effect free.
 */
final class SignatureGenerator {

	/**
	 * Generate HMAC-SHA256 signature for payload.
	 *
	 * Pure function: deterministic output.
	 *
	 * @param string $payload Payload string (typically JSON).
	 * @param string $secret  Secret key for HMAC.
	 *
	 * @return string Signature in format "sha256=<hex>".
	 */
	public static function generate( string $payload, string $secret ): string {
		return 'sha256=' . hash_hmac( 'sha256', $payload, $secret );
	}

	/**
	 * Verify signature matches payload.
	 *
	 * Uses timing-safe comparison to prevent timing attacks.
	 * Pure function: comparison only.
	 *
	 * @param string $payload   Payload string.
	 * @param string $signature Signature to verify.
	 * @param string $secret    Secret key for HMAC.
	 *
	 * @return bool True if signature is valid.
	 */
	public static function verify( string $payload, string $signature, string $secret ): bool {
		$expected = self::generate( $payload, $secret );
		return hash_equals( $expected, $signature );
	}
}
