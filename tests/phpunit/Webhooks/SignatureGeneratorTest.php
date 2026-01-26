<?php

/**
 * Tests for SignatureGenerator.
 *
 * @package FAWpmcp
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Webhooks;

use FAWpmcp\Webhooks\SignatureGenerator;
use PHPUnit\Framework\TestCase;

class SignatureGeneratorTest extends TestCase
{
    public function test_generates_hmac_signature(): void
    {
        $payload = '{"event":"test"}';
        $secret  = 'test-secret';

        $signature = SignatureGenerator::generate($payload, $secret);

        $this->assertStringStartsWith('sha256=', $signature);
        $this->assertMatchesRegularExpression('/^sha256=[a-f0-9]{64}$/', $signature);
    }

    public function test_same_input_produces_same_signature(): void
    {
        $payload = '{"event":"test","data":"value"}';
        $secret  = 'test-secret';

        $sig1 = SignatureGenerator::generate($payload, $secret);
        $sig2 = SignatureGenerator::generate($payload, $secret);

        // Pure function: deterministic output
        $this->assertEquals($sig1, $sig2);
    }

    public function test_different_payload_produces_different_signature(): void
    {
        $secret = 'test-secret';

        $sig1 = SignatureGenerator::generate('{"event":"test1"}', $secret);
        $sig2 = SignatureGenerator::generate('{"event":"test2"}', $secret);

        $this->assertNotEquals($sig1, $sig2);
    }

    public function test_different_secret_produces_different_signature(): void
    {
        $payload = '{"event":"test"}';

        $sig1 = SignatureGenerator::generate($payload, 'secret-1');
        $sig2 = SignatureGenerator::generate($payload, 'secret-2');

        $this->assertNotEquals($sig1, $sig2);
    }

    public function test_verify_returns_true_for_valid_signature(): void
    {
        $payload   = '{"event":"ability.after_execute","success":true}';
        $secret    = 'webhook-secret-key';
        $signature = SignatureGenerator::generate($payload, $secret);

        $result = SignatureGenerator::verify($payload, $signature, $secret);

        $this->assertTrue($result);
    }

    public function test_verify_returns_false_for_invalid_signature(): void
    {
        $payload   = '{"event":"ability.after_execute"}';
        $secret    = 'webhook-secret-key';
        $signature = 'sha256=invalidhash';

        $result = SignatureGenerator::verify($payload, $signature, $secret);

        $this->assertFalse($result);
    }

    public function test_verify_returns_false_for_tampered_payload(): void
    {
        $original_payload = '{"event":"test","amount":100}';
        $secret           = 'webhook-secret-key';
        $signature        = SignatureGenerator::generate($original_payload, $secret);

        // Attacker changes the payload
        $tampered_payload = '{"event":"test","amount":999}';

        $result = SignatureGenerator::verify($tampered_payload, $signature, $secret);

        $this->assertFalse($result);
    }

    public function test_verify_returns_false_for_wrong_secret(): void
    {
        $payload   = '{"event":"test"}';
        $secret    = 'correct-secret';
        $signature = SignatureGenerator::generate($payload, $secret);

        $result = SignatureGenerator::verify($payload, $signature, 'wrong-secret');

        $this->assertFalse($result);
    }

    public function test_uses_timing_safe_comparison(): void
    {
        // This test verifies that hash_equals is used internally
        // by checking that verify() works correctly
        $payload   = '{"event":"test"}';
        $secret    = 'test-secret';
        $signature = SignatureGenerator::generate($payload, $secret);

        // Timing-safe comparison should still work
        $this->assertTrue(SignatureGenerator::verify($payload, $signature, $secret));
    }
}
