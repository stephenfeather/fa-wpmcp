<?php

/**
 * Tests for SecretEncryptionFactory
 *
 * @package FAWpmcp\Tests\Webhooks
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Webhooks;

use FAWpmcp\Webhooks\SecretEncryptionFactory;
use FAWpmcp\Webhooks\SecretEncryption;
use FAWpmcp\Webhooks\SodiumSecretEncryption;
use FAWpmcp\Webhooks\OpenSslSecretEncryption;
use PHPUnit\Framework\TestCase;

/**
 * Test SecretEncryptionFactory
 *
 * @coversDefaultClass \FAWpmcp\Webhooks\SecretEncryptionFactory
 */
final class SecretEncryptionFactoryTest extends TestCase
{
    /**
     * Set up WordPress environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Define WordPress salts for testing encryption implementations.
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
     * Test create returns a SecretEncryption instance.
     *
     * @covers ::create
     */
    public function testCreateReturnsSecretEncryptionInstance(): void
    {
        // At least one encryption extension should be available.
        if (! extension_loaded('sodium') && ! extension_loaded('openssl')) {
            $this->markTestSkipped('Neither sodium nor openssl extension available');
        }

        $encryption = SecretEncryptionFactory::create();

        $this->assertInstanceOf(SecretEncryption::class, $encryption);
    }

    /**
     * Test create prefers sodium when available.
     *
     * @covers ::create
     */
    public function testCreatePrefersSodiumWhenAvailable(): void
    {
        if (! extension_loaded('sodium')) {
            $this->markTestSkipped('Sodium extension not available');
        }

        $encryption = SecretEncryptionFactory::create();

        $this->assertInstanceOf(SodiumSecretEncryption::class, $encryption);
    }

    /**
     * Test created encryption can encrypt and decrypt.
     *
     * @covers ::create
     */
    public function testCreatedEncryptionCanEncryptAndDecrypt(): void
    {
        if (! extension_loaded('sodium') && ! extension_loaded('openssl')) {
            $this->markTestSkipped('Neither sodium nor openssl extension available');
        }

        $encryption = SecretEncryptionFactory::create();
        $plaintext  = 'test-webhook-secret-' . bin2hex(random_bytes(16));

        $encrypted = $encryption->encrypt($plaintext);
        $decrypted = $encryption->decrypt($encrypted);

        $this->assertSame($plaintext, $decrypted);
    }

    /**
     * Test created encryption has expected prefix based on implementation.
     *
     * @covers ::create
     */
    public function testCreatedEncryptionHasExpectedPrefix(): void
    {
        if (! extension_loaded('sodium') && ! extension_loaded('openssl')) {
            $this->markTestSkipped('Neither sodium nor openssl extension available');
        }

        $encryption = SecretEncryptionFactory::create();
        $encrypted  = $encryption->encrypt('test-secret');

        if (extension_loaded('sodium')) {
            $this->assertStringStartsWith('sodium:v1:', $encrypted);
        } else {
            $this->assertStringStartsWith('openssl:v1:', $encrypted);
        }
    }

    /**
     * Test factory is deterministic (same implementation on repeated calls).
     *
     * @covers ::create
     */
    public function testFactoryIsDeterministic(): void
    {
        if (! extension_loaded('sodium') && ! extension_loaded('openssl')) {
            $this->markTestSkipped('Neither sodium nor openssl extension available');
        }

        $encryption1 = SecretEncryptionFactory::create();
        $encryption2 = SecretEncryptionFactory::create();

        $this->assertSame(get_class($encryption1), get_class($encryption2));
    }

    /**
     * Test factory returns different instances (not singleton).
     *
     * @covers ::create
     */
    public function testFactoryReturnsDifferentInstances(): void
    {
        if (! extension_loaded('sodium') && ! extension_loaded('openssl')) {
            $this->markTestSkipped('Neither sodium nor openssl extension available');
        }

        $encryption1 = SecretEncryptionFactory::create();
        $encryption2 = SecretEncryptionFactory::create();

        $this->assertNotSame($encryption1, $encryption2);
    }

    /**
     * Test created encryption isEncrypted works correctly.
     *
     * @covers ::create
     */
    public function testCreatedEncryptionIsEncryptedWorks(): void
    {
        if (! extension_loaded('sodium') && ! extension_loaded('openssl')) {
            $this->markTestSkipped('Neither sodium nor openssl extension available');
        }

        $encryption = SecretEncryptionFactory::create();
        $encrypted  = $encryption->encrypt('test-secret');

        $this->assertTrue($encryption->isEncrypted($encrypted));
        $this->assertFalse($encryption->isEncrypted('plain-text'));
    }
}
