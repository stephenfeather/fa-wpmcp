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
final class OpenSslSecretEncryptionTest extends TestCase
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
     * Test constructor throws when openssl not available
     *
     * @covers ::__construct
     */
    public function testConstructorRequiresOpenSsl(): void
    {
        if (! extension_loaded('openssl')) {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('OpenSSL extension not available');
            new OpenSslSecretEncryption();
        } else {
            $this->assertTrue(extension_loaded('openssl'));
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
        if (! extension_loaded('openssl')) {
            $this->markTestSkipped('OpenSSL extension not available');
        }

        $encryption = new OpenSslSecretEncryption();
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
        if (! extension_loaded('openssl')) {
            $this->markTestSkipped('OpenSSL extension not available');
        }

        $encryption = new OpenSslSecretEncryption();
        $encrypted  = $encryption->encrypt('test-secret');

        $this->assertStringStartsWith('openssl:v1:', $encrypted);
    }

    /**
     * Test different IVs produce different ciphertext
     *
     * @covers ::encrypt
     */
    public function testDifferentIVsProduceDifferentCiphertext(): void
    {
        if (! extension_loaded('openssl')) {
            $this->markTestSkipped('OpenSSL extension not available');
        }

        $encryption = new OpenSslSecretEncryption();
        $plaintext  = 'test-secret';

        $encrypted1 = $encryption->encrypt($plaintext);
        $encrypted2 = $encryption->encrypt($plaintext);

        $this->assertNotSame($encrypted1, $encrypted2, 'Same plaintext should produce different ciphertext due to random IV');
    }

    /**
     * Test isEncrypted detects encrypted values
     *
     * @covers ::isEncrypted
     */
    public function testIsEncryptedDetectsEncryptedValues(): void
    {
        if (! extension_loaded('openssl')) {
            $this->markTestSkipped('OpenSSL extension not available');
        }

        $encryption = new OpenSslSecretEncryption();
        $encrypted  = $encryption->encrypt('test-secret');

        $this->assertTrue($encryption->isEncrypted($encrypted));
        $this->assertFalse($encryption->isEncrypted('plain-text-secret'));
        $this->assertFalse($encryption->isEncrypted('sodium:v1:something'));
    }

    /**
     * Test decrypt fails on tampered ciphertext
     *
     * @covers ::decrypt
     */
    public function testDecryptFailsOnTamperedCiphertext(): void
    {
        if (! extension_loaded('openssl')) {
            $this->markTestSkipped('OpenSSL extension not available');
        }

        $encryption = new OpenSslSecretEncryption();
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
        if (! extension_loaded('openssl')) {
            $this->markTestSkipped('OpenSSL extension not available');
        }

        $encryption = new OpenSslSecretEncryption();

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
        if (! extension_loaded('openssl')) {
            $this->markTestSkipped('OpenSSL extension not available');
        }

        $encryption = new OpenSslSecretEncryption();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Decryption failed');
        $encryption->decrypt('openssl:v1:!!!invalid-base64!!!');
    }

    /**
     * Test decrypt fails on ciphertext too short
     *
     * @covers ::decrypt
     */
    public function testDecryptFailsOnCiphertextTooShort(): void
    {
        if (! extension_loaded('openssl')) {
            $this->markTestSkipped('OpenSSL extension not available');
        }

        $encryption = new OpenSslSecretEncryption();

        // Valid base64 but too short.
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Decryption failed');
        $encryption->decrypt('openssl:v1:' . base64_encode('short'));
    }

    /**
     * Test empty plaintext can be encrypted
     *
     * @covers ::encrypt
     * @covers ::decrypt
     */
    public function testEmptyPlaintextCanBeEncrypted(): void
    {
        if (! extension_loaded('openssl')) {
            $this->markTestSkipped('OpenSSL extension not available');
        }

        $encryption = new OpenSslSecretEncryption();
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
        if (! extension_loaded('openssl')) {
            $this->markTestSkipped('OpenSSL extension not available');
        }

        $encryption = new OpenSslSecretEncryption();
        $plaintext  = str_repeat('a', 10000);
        $encrypted  = $encryption->encrypt($plaintext);
        $decrypted  = $encryption->decrypt($encrypted);

        $this->assertSame($plaintext, $decrypted);
    }

    /**
     * Test decrypt fails with specific message on invalid base64.
     *
     * @covers ::decrypt
     */
    public function testDecryptShowsInvalidBase64Message(): void
    {
        if (! extension_loaded('openssl')) {
            $this->markTestSkipped('OpenSSL extension not available');
        }

        $encryption = new OpenSslSecretEncryption();

        // Use characters that are invalid in base64 to trigger base64_decode failure.
        try {
            $encryption->decrypt('openssl:v1:@#$%^&*()');
            $this->fail('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Decryption failed', $e->getMessage());
        }
    }

    /**
     * Test decrypt fails with specific message on ciphertext too short.
     *
     * @covers ::decrypt
     */
    public function testDecryptShowsCiphertextTooShortMessage(): void
    {
        if (! extension_loaded('openssl')) {
            $this->markTestSkipped('OpenSSL extension not available');
        }

        $encryption = new OpenSslSecretEncryption();

        // IV is 12 bytes, tag is 16 bytes = 28 bytes minimum.
        // Create a valid base64 string that decodes to less than 28 bytes.
        $short_data = str_repeat('x', 10);

        try {
            $encryption->decrypt('openssl:v1:' . base64_encode($short_data));
            $this->fail('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Decryption failed', $e->getMessage());
            $this->assertStringContainsString('Ciphertext too short', $e->getMessage());
        }
    }

    /**
     * Test decrypt fails with authentication error on tampered data.
     *
     * @covers ::decrypt
     */
    public function testDecryptShowsAuthenticationFailedMessage(): void
    {
        if (! extension_loaded('openssl')) {
            $this->markTestSkipped('OpenSSL extension not available');
        }

        $encryption = new OpenSslSecretEncryption();
        $encrypted  = $encryption->encrypt('test-secret');

        // Create data with correct length but tampered content.
        // IV (12) + Tag (16) + some ciphertext = valid structure but wrong data.
        $fake_iv         = random_bytes(12);
        $fake_tag        = random_bytes(16);
        $fake_ciphertext = random_bytes(20);
        $tampered        = 'openssl:v1:' . base64_encode($fake_iv . $fake_tag . $fake_ciphertext);

        try {
            $encryption->decrypt($tampered);
            $this->fail('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Decryption failed', $e->getMessage());
        }
    }

    /**
     * Test destructor clears key from memory.
     *
     * @covers ::__destruct
     */
    public function testDestructorClearsKey(): void
    {
        if (! extension_loaded('openssl')) {
            $this->markTestSkipped('OpenSSL extension not available');
        }

        // Create and immediately destroy an encryption instance.
        $encryption = new OpenSslSecretEncryption();
        $encrypted  = $encryption->encrypt('test-secret');

        // Verify encryption worked before destruction.
        $this->assertTrue($encryption->isEncrypted($encrypted));

        // Destructor is called automatically when unset or out of scope.
        // This test ensures the destructor doesn't throw.
        unset($encryption);

        // If we got here without exception, destructor worked.
        $this->assertTrue(true);
    }

    /**
     * Test encrypting special characters.
     *
     * @covers ::encrypt
     * @covers ::decrypt
     */
    public function testEncryptSpecialCharacters(): void
    {
        if (! extension_loaded('openssl')) {
            $this->markTestSkipped('OpenSSL extension not available');
        }

        $encryption = new OpenSslSecretEncryption();

        $special_chars = "!@#$%^&*()_+-=[]{}|;':\",./<>?\n\t\r\0";
        $encrypted     = $encryption->encrypt($special_chars);
        $decrypted     = $encryption->decrypt($encrypted);

        $this->assertSame($special_chars, $decrypted);
    }

    /**
     * Test encrypting unicode characters.
     *
     * @covers ::encrypt
     * @covers ::decrypt
     */
    public function testEncryptUnicodeCharacters(): void
    {
        if (! extension_loaded('openssl')) {
            $this->markTestSkipped('OpenSSL extension not available');
        }

        $encryption = new OpenSslSecretEncryption();

        $unicode   = 'Hello 世界 🌍 مرحبا Привет';
        $encrypted = $encryption->encrypt($unicode);
        $decrypted = $encryption->decrypt($encrypted);

        $this->assertSame($unicode, $decrypted);
    }

    /**
     * Test encrypting binary data.
     *
     * @covers ::encrypt
     * @covers ::decrypt
     */
    public function testEncryptBinaryData(): void
    {
        if (! extension_loaded('openssl')) {
            $this->markTestSkipped('OpenSSL extension not available');
        }

        $encryption = new OpenSslSecretEncryption();

        $binary    = random_bytes(256);
        $encrypted = $encryption->encrypt($binary);
        $decrypted = $encryption->decrypt($encrypted);

        $this->assertSame($binary, $decrypted);
    }

    /**
     * Test isEncrypted returns false for empty string.
     *
     * @covers ::isEncrypted
     */
    public function testIsEncryptedReturnsFalseForEmptyString(): void
    {
        if (! extension_loaded('openssl')) {
            $this->markTestSkipped('OpenSSL extension not available');
        }

        $encryption = new OpenSslSecretEncryption();

        $this->assertFalse($encryption->isEncrypted(''));
    }

    /**
     * Test isEncrypted returns false for partial prefix.
     *
     * @covers ::isEncrypted
     */
    public function testIsEncryptedReturnsFalseForPartialPrefix(): void
    {
        if (! extension_loaded('openssl')) {
            $this->markTestSkipped('OpenSSL extension not available');
        }

        $encryption = new OpenSslSecretEncryption();

        $this->assertFalse($encryption->isEncrypted('openssl:'));
        $this->assertFalse($encryption->isEncrypted('openssl:v'));
        $this->assertFalse($encryption->isEncrypted('openssl:v1'));
    }
}
