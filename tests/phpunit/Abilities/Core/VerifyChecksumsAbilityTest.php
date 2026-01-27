<?php

/**
 * Tests for VerifyChecksumsAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Core
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Core;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Core\VerifyChecksumsAbility;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;

// Define WP_Error stub if not exists.
if (! class_exists('WP_Error')) {
    // phpcs:ignore Generic.Classes.DuplicateClassName.Found
    class WP_Error
    {
        public $errors   = array();
        public $code     = '';
        public $message  = '';

        public function __construct(string $code = '', string $message = '')
        {
            $this->code    = $code;
            $this->message = $message;
            $this->errors  = array( $code => array( $message ) );
        }
    }
}

/**
 * Test VerifyChecksumsAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Core
 */
class VerifyChecksumsAbilityTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get the ability instance to test.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new VerifyChecksumsAbility();
    }

    /**
     * Get the expected metadata for this ability.
     *
     * @return array<string, mixed>
     */
    protected function getExpectedMetadata(): array
    {
        return array(
            'name'                 => 'fa-wpmcp/verify-checksums',
            'category'             => 'core',
            'label'                => 'Verify Checksums',
            'description_contains' => 'checksum',
            'operation_type'       => 'read',
            'required_capability'  => 'manage_options',
        );
    }

    /**
     * Test ability returns input schema with version/locale options.
     *
     * @return void
     */
    public function testGetInputSchema(): void
    {
        $ability = $this->getAbilityInstance();
        $schema  = $ability->getInputSchema();

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('version', $schema['properties']);
        $this->assertArrayHasKey('locale', $schema['properties']);
    }

    /**
     * Test ability returns output schema with verification properties.
     *
     * @return void
     */
    public function testGetOutputSchema(): void
    {
        $ability = $this->getAbilityInstance();
        $schema  = $ability->getOutputSchema();

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('verified', $schema['properties']);
        $this->assertArrayHasKey('version', $schema['properties']);
        $this->assertArrayHasKey('files_checked', $schema['properties']);
        $this->assertArrayHasKey('mismatches', $schema['properties']);
        $this->assertArrayHasKey('missing_files', $schema['properties']);
    }

    /**
     * Test execute throws exception when checksums unavailable.
     *
     * @return void
     */
    public function testExecuteThrowsExceptionWhenChecksumsUnavailable(): void
    {
        $ability = $this->getAbilityInstance();

        Functions\when('get_bloginfo')->justReturn('6.9.1');

        $wp_error = new WP_Error('http_request_failed', 'Connection failed');

        Functions\when('wp_remote_get')->justReturn($wp_error);
        Functions\when('is_wp_error')->justReturn(true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to fetch checksums');

        $ability->doExecute(array());
    }

    /**
     * Test execute returns result structure when checksums available.
     *
     * @return void
     */
    public function testExecuteReturnsResultStructure(): void
    {
        $ability = $this->getAbilityInstance();

        Functions\when('get_bloginfo')->justReturn('6.9.1');

        // Provide a checksum for a nonexistent file - this tests the structure.
        $response_body = json_encode(
            array(
                'checksums' => array(
                    'test-file.php' => 'abc123',
                ),
            )
        );

        Functions\when('wp_remote_get')->justReturn(
            array(
                'response' => array( 'code' => 200 ),
                'body'     => $response_body,
            )
        );
        Functions\when('wp_remote_retrieve_body')->justReturn($response_body);
        Functions\when('is_wp_error')->justReturn(false);

        $result = $ability->doExecute(array());

        // The file won't exist, so verified should be false.
        $this->assertFalse($result['verified']);
        $this->assertEquals(1, $result['files_checked']);
        $this->assertArrayHasKey('mismatches', $result);
        $this->assertArrayHasKey('missing_files', $result);
        $this->assertEquals('6.9.1', $result['version']);
        $this->assertEquals('en_US', $result['locale']);
    }

    /**
     * Test execute detects missing files.
     *
     * @return void
     */
    public function testExecuteDetectsMissingFiles(): void
    {
        $ability = $this->getAbilityInstance();

        Functions\when('get_bloginfo')->justReturn('6.9.1');

        // Mock API response with a file that doesn't exist.
        $response_body = json_encode(
            array(
                'checksums' => array(
                    'wp-includes/nonexistent-file.php' => 'abc123',
                ),
            )
        );

        Functions\when('wp_remote_get')->justReturn(
            array(
                'response' => array( 'code' => 200 ),
                'body'     => $response_body,
            )
        );
        Functions\when('wp_remote_retrieve_body')->justReturn($response_body);
        Functions\when('is_wp_error')->justReturn(false);

        $result = $ability->doExecute(array());

        $this->assertFalse($result['verified']);
        $this->assertContains('wp-includes/nonexistent-file.php', $result['missing_files']);
    }

    /**
     * Test execute uses custom version when provided.
     *
     * @return void
     */
    public function testExecuteUsesCustomVersion(): void
    {
        $ability = $this->getAbilityInstance();

        // Provide a checksum so it doesn't throw an exception.
        $response_body = json_encode(
            array(
                'checksums' => array(
                    'test-file.php' => 'abc123',
                ),
            )
        );

        Functions\when('wp_remote_get')->justReturn(
            array(
                'response' => array( 'code' => 200 ),
                'body'     => $response_body,
            )
        );
        Functions\when('wp_remote_retrieve_body')->justReturn($response_body);
        Functions\when('is_wp_error')->justReturn(false);

        $result = $ability->doExecute(array( 'version' => '6.8.0' ));

        // Verify the custom version is returned in the result.
        $this->assertEquals('6.8.0', $result['version']);
    }

    /**
     * Test annotations are correct for read-only ability.
     *
     * @return void
     */
    public function testGetAnnotations(): void
    {
        $ability     = new VerifyChecksumsAbility();
        $annotations = $ability->getAnnotations();

        $this->assertTrue($annotations['readonly']);
        $this->assertFalse($annotations['destructive']);
    }
}
