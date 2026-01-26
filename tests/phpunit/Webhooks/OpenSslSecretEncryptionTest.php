<?php
/**
 * Tests for OpenSslSecretEncryption
 *
 * @package FAWpmcp\Tests\Webhooks
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Webhooks;

use FAWpmcp\Webhooks\OpenSslSecretEncryption;
use PHPUnit\Framework\TestCase;

/**
 * Test OpenSSL-based secret encryption
 *
 * @coversDefaultClass \FAWpmcp\Webhooks\OpenSslSecretEncryption
 */
final class OpenSslSecretEncryptionTest extends TestCase {

	/**
	 * Set up WordPress environment
	 */
	protected function setUp(): void {
		parent::setUp();

		// Define WordPress salts for testing.
		if ( ! defined( 'SECURE_AUTH_KEY' ) ) {
			define( 'SECURE_AUTH_KEY', 'test-secure-auth-key-' . bin2hex( random_bytes( 32 ) ) );
		}
		if ( ! defined( 'LOGGED_IN_KEY' ) ) {
			define( 'LOGGED_IN_KEY', 'test-logged-in-key-' . bin2hex( random_bytes( 32 ) ) );
		}
		if ( ! defined( 'NONCE_SALT' ) ) {
			define( 'NONCE_SALT', 'test-nonce-salt-' . bin2hex( random_bytes( 32 ) ) );
		}
	}

	/**
	 * Test constructor throws when openssl not available
	 *
	 * @covers ::__construct
	 */
	public function testConstructorRequiresOpenSsl(): void {
		if ( ! extension_loaded( 'openssl' ) ) {
			$this->expectException( \RuntimeException::class );
			$this->expectExceptionMessage( 'OpenSSL extension not available' );
			new OpenSslSecretEncryption();
		} else {
			$this->assertTrue( extension_loaded( 'openssl' ) );
		}
	}

	/**
	 * Test encrypt/decrypt round trip
	 *
	 * @covers ::encrypt
	 * @covers ::decrypt
	 */
	public function testEncryptDecryptRoundTrip(): void {
		if ( ! extension_loaded( 'openssl' ) ) {
			$this->markTestSkipped( 'OpenSSL extension not available' );
		}

		$encryption = new OpenSslSecretEncryption();
		$plaintext  = 'my-webhook-secret-' . bin2hex( random_bytes( 16 ) );

		$encrypted = $encryption->encrypt( $plaintext );
		$decrypted = $encryption->decrypt( $encrypted );

		$this->assertSame( $plaintext, $decrypted );
	}

	/**
	 * Test encrypted value has correct prefix
	 *
	 * @covers ::encrypt
	 */
	public function testEncryptedValueHasPrefix(): void {
		if ( ! extension_loaded( 'openssl' ) ) {
			$this->markTestSkipped( 'OpenSSL extension not available' );
		}

		$encryption = new OpenSslSecretEncryption();
		$encrypted  = $encryption->encrypt( 'test-secret' );

		$this->assertStringStartsWith( 'openssl:v1:', $encrypted );
	}

	/**
	 * Test different IVs produce different ciphertext
	 *
	 * @covers ::encrypt
	 */
	public function testDifferentIVsProduceDifferentCiphertext(): void {
		if ( ! extension_loaded( 'openssl' ) ) {
			$this->markTestSkipped( 'OpenSSL extension not available' );
		}

		$encryption = new OpenSslSecretEncryption();
		$plaintext  = 'test-secret';

		$encrypted1 = $encryption->encrypt( $plaintext );
		$encrypted2 = $encryption->encrypt( $plaintext );

		$this->assertNotSame( $encrypted1, $encrypted2, 'Same plaintext should produce different ciphertext due to random IV' );
	}

	/**
	 * Test isEncrypted detects encrypted values
	 *
	 * @covers ::isEncrypted
	 */
	public function testIsEncryptedDetectsEncryptedValues(): void {
		if ( ! extension_loaded( 'openssl' ) ) {
			$this->markTestSkipped( 'OpenSSL extension not available' );
		}

		$encryption = new OpenSslSecretEncryption();
		$encrypted  = $encryption->encrypt( 'test-secret' );

		$this->assertTrue( $encryption->isEncrypted( $encrypted ) );
		$this->assertFalse( $encryption->isEncrypted( 'plain-text-secret' ) );
		$this->assertFalse( $encryption->isEncrypted( 'sodium:v1:something' ) );
	}

	/**
	 * Test decrypt fails on tampered ciphertext
	 *
	 * @covers ::decrypt
	 */
	public function testDecryptFailsOnTamperedCiphertext(): void {
		if ( ! extension_loaded( 'openssl' ) ) {
			$this->markTestSkipped( 'OpenSSL extension not available' );
		}

		$encryption = new OpenSslSecretEncryption();
		$encrypted  = $encryption->encrypt( 'test-secret' );

		// Tamper with ciphertext (flip a bit in the base64 payload).
		$tampered = substr( $encrypted, 0, -1 ) . 'X';

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Decryption failed' );
		$encryption->decrypt( $tampered );
	}

	/**
	 * Test decrypt fails on invalid format
	 *
	 * @covers ::decrypt
	 */
	public function testDecryptFailsOnInvalidFormat(): void {
		if ( ! extension_loaded( 'openssl' ) ) {
			$this->markTestSkipped( 'OpenSSL extension not available' );
		}

		$encryption = new OpenSslSecretEncryption();

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Invalid ciphertext format' );
		$encryption->decrypt( 'plain-text' );
	}

	/**
	 * Test decrypt fails on invalid base64
	 *
	 * @covers ::decrypt
	 */
	public function testDecryptFailsOnInvalidBase64(): void {
		if ( ! extension_loaded( 'openssl' ) ) {
			$this->markTestSkipped( 'OpenSSL extension not available' );
		}

		$encryption = new OpenSslSecretEncryption();

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Decryption failed' );
		$encryption->decrypt( 'openssl:v1:!!!invalid-base64!!!' );
	}

	/**
	 * Test decrypt fails on ciphertext too short
	 *
	 * @covers ::decrypt
	 */
	public function testDecryptFailsOnCiphertextTooShort(): void {
		if ( ! extension_loaded( 'openssl' ) ) {
			$this->markTestSkipped( 'OpenSSL extension not available' );
		}

		$encryption = new OpenSslSecretEncryption();

		// Valid base64 but too short.
		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Decryption failed' );
		$encryption->decrypt( 'openssl:v1:' . base64_encode( 'short' ) );
	}

	/**
	 * Test empty plaintext can be encrypted
	 *
	 * @covers ::encrypt
	 * @covers ::decrypt
	 */
	public function testEmptyPlaintextCanBeEncrypted(): void {
		if ( ! extension_loaded( 'openssl' ) ) {
			$this->markTestSkipped( 'OpenSSL extension not available' );
		}

		$encryption = new OpenSslSecretEncryption();
		$encrypted  = $encryption->encrypt( '' );
		$decrypted  = $encryption->decrypt( $encrypted );

		$this->assertSame( '', $decrypted );
	}

	/**
	 * Test long plaintext can be encrypted
	 *
	 * @covers ::encrypt
	 * @covers ::decrypt
	 */
	public function testLongPlaintextCanBeEncrypted(): void {
		if ( ! extension_loaded( 'openssl' ) ) {
			$this->markTestSkipped( 'OpenSSL extension not available' );
		}

		$encryption = new OpenSslSecretEncryption();
		$plaintext  = str_repeat( 'a', 10000 );
		$encrypted  = $encryption->encrypt( $plaintext );
		$decrypted  = $encryption->decrypt( $encrypted );

		$this->assertSame( $plaintext, $decrypted );
	}
}
