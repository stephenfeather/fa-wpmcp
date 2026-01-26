<?php

/**
 * Tests for SodiumSecretEncryption
 *
 * @package FAWpmcp\Tests\Webhooks
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Webhooks;

use FAWpmcp\Webhooks\SodiumSecretEncryption;
use PHPUnit\Framework\TestCase;

/**
 * Test sodium-based secret encryption
 *
 * @coversDefaultClass \FAWpmcp\Webhooks\SodiumSecretEncryption
 */
final class SodiumSecretEncryptionTest extends TestCase
{
    /**
     * Set up WordPress environment
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Define WordPress salts for testing.
        if (! defined('SECURE_AUTH_KEY')) {
            define('SECURE_AUTH_KEY', 'test-secure-auth-key-' . bin2hex(random_bytes(32)));
        }
        if (! defined('LOGGED_IN_KEY')) {
            define('LOGGED_IN_KEY', 'test-logged-in-key-' . bin2hex(random_bytes(32)));
        }
        if (! defined('NONCE_SALT')) {
            define('NONCE_SALT', 'test-nonce-salt-' . bin2hex(random_bytes(32)));
        }
    }

    /**
     * Test constructor throws when sodium not available
     *
     * @covers ::__construct
     */
    public function testConstructorRequiresSodium(): void
    {
        if (! extension_loaded('sodium')) {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('Sodium extension not available');
            new SodiumSecretEncryption();
        } else {
            $this->assertTrue(extension_loaded('sodium'));
        }
    }

    /**
     * Test encrypt/decrypt round trip
     *
     * @covers ::encrypt
     * @covers ::decrypt
     */
    public function testEncryptDecryptRoundTrip(): void
    {
        if (! extension_loaded('sodium')) {
            $this->markTestSkipped('Sodium extension not available');
        }

        $encryption = new SodiumSecretEncryption();
        $plaintext  = 'my-webhook-secret-' . bin2hex(random_bytes(16));

        $encrypted = $encryption->encrypt($plaintext);
        $decrypted = $encryption->decrypt($encrypted);

        $this->assertSame($plaintext, $decrypted);
    }

    /**
     * Test encrypted value has correct prefix
     *
     * @covers ::encrypt
     */
    public function testEncryptedValueHasPrefix(): void
    {
        if (! extension_loaded('sodium')) {
            $this->markTestSkipped('Sodium extension not available');
        }

        $encryption = new SodiumSecretEncryption();
        $encrypted  = $encryption->encrypt('test-secret');

        $this->assertStringStartsWith('sodium:v1:', $encrypted);
    }

    /**
     * Test different nonces produce different ciphertext
     *
     * @covers ::encrypt
     */
    public function testDifferentNoncesProduceDifferentCiphertext(): void
    {
        if (! extension_loaded('sodium')) {
            $this->markTestSkipped('Sodium extension not available');
        }

        $encryption = new SodiumSecretEncryption();
        $plaintext  = 'test-secret';

        $encrypted1 = $encryption->encrypt($plaintext);
        $encrypted2 = $encryption->encrypt($plaintext);

        $this->assertNotSame($encrypted1, $encrypted2, 'Same plaintext should produce different ciphertext due to random nonce');
    }

    /**
     * Test isEncrypted detects encrypted values
     *
     * @covers ::isEncrypted
     */
    public function testIsEncryptedDetectsEncryptedValues(): void
    {
        if (! extension_loaded('sodium')) {
            $this->markTestSkipped('Sodium extension not available');
        }

        $encryption = new SodiumSecretEncryption();
        $encrypted  = $encryption->encrypt('test-secret');

        $this->assertTrue($encryption->isEncrypted($encrypted));
        $this->assertFalse($encryption->isEncrypted('plain-text-secret'));
        $this->assertFalse($encryption->isEncrypted('openssl:v1:something'));
    }

    /**
     * Test decrypt fails on tampered ciphertext
     *
     * @covers ::decrypt
     */
    public function testDecryptFailsOnTamperedCiphertext(): void
    {
        if (! extension_loaded('sodium')) {
            $this->markTestSkipped('Sodium extension not available');
        }

        $encryption = new SodiumSecretEncryption();
        $encrypted  = $encryption->encrypt('test-secret');

        // Tamper with ciphertext (flip a bit in the base64 payload).
        $tampered = substr($encrypted, 0, -1) . 'X';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Decryption failed');
        $encryption->decrypt($tampered);
    }

    /**
     * Test decrypt fails on invalid format
     *
     * @covers ::decrypt
     */
    public function testDecryptFailsOnInvalidFormat(): void
    {
        if (! extension_loaded('sodium')) {
            $this->markTestSkipped('Sodium extension not available');
        }

        $encryption = new SodiumSecretEncryption();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid ciphertext format');
        $encryption->decrypt('plain-text');
    }

    /**
     * Test decrypt fails on invalid base64
     *
     * @covers ::decrypt
     */
    public function testDecryptFailsOnInvalidBase64(): void
    {
        if (! extension_loaded('sodium')) {
            $this->markTestSkipped('Sodium extension not available');
        }

        $encryption = new SodiumSecretEncryption();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Decryption failed');
        $encryption->decrypt('sodium:v1:!!!invalid-base64!!!');
    }

    /**
     * Test decrypt fails on ciphertext too short
     *
     * @covers ::decrypt
     */
    public function testDecryptFailsOnCiphertextTooShort(): void
    {
        if (! extension_loaded('sodium')) {
            $this->markTestSkipped('Sodium extension not available');
        }

        $encryption = new SodiumSecretEncryption();

        // Valid base64 but too short.
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Decryption failed');
        $encryption->decrypt('sodium:v1:' . base64_encode('short'));
    }

    /**
     * Test empty plaintext can be encrypted
     *
     * @covers ::encrypt
     * @covers ::decrypt
     */
    public function testEmptyPlaintextCanBeEncrypted(): void
    {
        if (! extension_loaded('sodium')) {
            $this->markTestSkipped('Sodium extension not available');
        }

        $encryption = new SodiumSecretEncryption();
        $encrypted  = $encryption->encrypt('');
        $decrypted  = $encryption->decrypt($encrypted);

        $this->assertSame('', $decrypted);
    }

    /**
     * Test long plaintext can be encrypted
     *
     * @covers ::encrypt
     * @covers ::decrypt
     */
    public function testLongPlaintextCanBeEncrypted(): void
    {
        if (! extension_loaded('sodium')) {
            $this->markTestSkipped('Sodium extension not available');
        }

        $encryption = new SodiumSecretEncryption();
        $plaintext  = str_repeat('a', 10000);
        $encrypted  = $encryption->encrypt($plaintext);
        $decrypted  = $encryption->decrypt($encrypted);

        $this->assertSame($plaintext, $decrypted);
    }
}
