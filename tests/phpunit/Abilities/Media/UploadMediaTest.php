<?php

/**
 * Tests for UploadMedia ability.
 *
 * @package FAWpmcp\Tests\Abilities\Media
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Media;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Media\UploadMedia;
use FAWpmcp\Exceptions\MediaUploadException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test UploadMedia ability functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Media
 */
class UploadMediaTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    protected function getAbilityInstance(): AbstractAbility
    {
        return new UploadMedia();
    }

    protected function getExpectedMetadata(): array
    {
        return [
            'name'                 => 'fa-wpmcp/upload-media',
            'category'             => 'media',
            'label'                => 'Upload Media',
            'description_contains' => 'upload',
            'required_capability'  => 'upload_files',
            'operation_type'       => 'write',
        ];
    }

    /**
     * Ensure the WordPress image includes file exists for tests.
     *
     * @return void
     */
    private function ensureImageIncludesFile(): void
    {
        $path = ABSPATH . 'wp-admin/includes';
        if (! is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $file = $path . '/image.php';
        if (! file_exists($file)) {
            file_put_contents($file, "<?php\n");
        }
    }

    /**
     * Test execute throws exception when no file data or url provided.
     *
     * @return void
     */
    public function testExecuteThrowsExceptionWhenNoFileDataOrUrl(): void
    {
        $ability = $this->getAbilityInstance();

        $this->expectException(MediaUploadException::class);
        $this->expectExceptionMessage('Either file_data or url must be provided');

        $ability->doExecute(array( 'filename' => 'test.jpg' ));
    }

    /**
     * Test execute throws exception for invalid base64 data.
     *
     * @return void
     */
    public function testExecuteThrowsExceptionForInvalidBase64(): void
    {
        $ability = $this->getAbilityInstance();

        $this->expectException(MediaUploadException::class);
        $this->expectExceptionMessage('Invalid base64 data');

        $ability->doExecute(
            array(
                'filename'  => 'test.jpg',
                'file_data' => 'not-base64',
            )
        );
    }

    /**
     * Test execute throws exception when fetched file exceeds size limit.
     *
     * @return void
     */
    public function testExecuteThrowsExceptionForFileSizeLimit(): void
    {
        $ability = $this->getAbilityInstance();

        Functions\when('wp_remote_get')->justReturn(array( 'response' => array( 'code' => 200 ) ));
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('wp_remote_retrieve_body')->justReturn(str_repeat('a', 10485761));

        $this->expectException(MediaUploadException::class);
        $this->expectExceptionMessage('File size exceeds 10MB limit');

        $ability->doExecute(
            array(
                'filename' => 'test.jpg',
                'url'      => 'https://example.com/file.jpg',
            )
        );
    }

    /**
     * Test execute throws exception when remote fetch fails.
     *
     * @return void
     */
    public function testExecuteThrowsExceptionWhenRemoteFetchFails(): void
    {
        $ability = $this->getAbilityInstance();

        $error = Mockery::mock();
        $error->shouldReceive('get_error_message')->andReturn('Request failed');

        Functions\when('wp_remote_get')->justReturn($error);
        Functions\when('is_wp_error')->justReturn(true);

        $this->expectException(MediaUploadException::class);
        $this->expectExceptionMessage('Failed to fetch URL');

        $ability->doExecute(
            array(
                'filename' => 'test.jpg',
                'url'      => 'https://example.com/file.jpg',
            )
        );
    }

    /**
     * Test execute throws exception when remote fetch body is empty.
     *
     * @return void
     */
    public function testExecuteThrowsExceptionWhenRemoteBodyEmpty(): void
    {
        $ability = $this->getAbilityInstance();

        Functions\when('wp_remote_get')->justReturn(array( 'response' => array( 'code' => 200 ) ));
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('wp_remote_retrieve_body')->justReturn('');

        $this->expectException(MediaUploadException::class);
        $this->expectExceptionMessage('Empty response from URL');

        $ability->doExecute(
            array(
                'filename' => 'test.jpg',
                'url'      => 'https://example.com/file.jpg',
            )
        );
    }

    /**
     * Test execute uploads from base64 data and formats response.
     *
     * @return void
     */
    public function testExecuteUploadsFromBase64(): void
    {
        $ability = $this->getAbilityInstance();

        $this->ensureImageIncludesFile();

        $upload_dir = sys_get_temp_dir() . '/fa-wpmcp-upload';
        if (! is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $response_file = sys_get_temp_dir() . '/fa-wpmcp-response.jpg';
        file_put_contents($response_file, '12345');

        Functions\when('wp_upload_dir')->justReturn(array( 'path' => $upload_dir ));
        Functions\when('wp_unique_filename')->justReturn('test.jpg');
        Functions\when('wp_check_filetype')->justReturn(array( 'type' => 'image/jpeg' ));
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('sanitize_textarea_field')->returnArg();
        Functions\when('sanitize_file_name')->returnArg();
        Functions\when('wp_insert_attachment')->justReturn(42);
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('wp_generate_attachment_metadata')->justReturn(array( 'width' => 10 ));
        Functions\when('wp_update_attachment_metadata')->justReturn(true);
        Functions\when('update_post_meta')->justReturn(true);
        Functions\when('wp_delete_file')->justReturn(true);
        Functions\when('get_attached_file')->justReturn($response_file);
        Functions\when('wp_get_attachment_url')->justReturn('https://example.com/test.jpg');
        Functions\when('get_post')->justReturn((object) array( 'post_mime_type' => 'image/jpeg' ));

        $result = $ability->doExecute(
            array(
                'filename'  => 'test.jpg',
                'file_data' => 'data:image/jpeg;base64,' . base64_encode('hello'),
                'title'     => 'Test Title',
                'caption'   => 'Caption',
                'alt_text'  => 'Alt',
            )
        );

        $this->assertSame(42, $result['media_id']);
        $this->assertSame('https://example.com/test.jpg', $result['url']);
        $this->assertSame('image/jpeg', $result['mime_type']);
        $this->assertSame('image', $result['type']);

        $temp_file = $upload_dir . '/test.jpg';
        if (file_exists($temp_file)) {
            unlink($temp_file);
        }
        unlink($response_file);
    }

    /**
     * Test input schema has required fields.
     *
     * @return void
     */
    public function testInputSchemaHasRequiredFields(): void
    {
        $ability = $this->getAbilityInstance();
        $schema  = $ability->getInputSchema();

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('filename', $schema['properties']);
        $this->assertArrayHasKey('file_data', $schema['properties']);
        $this->assertArrayHasKey('url', $schema['properties']);
        $this->assertArrayHasKey('required', $schema);
        $this->assertContains('filename', $schema['required']);
    }

    /**
     * Test output schema structure.
     *
     * @return void
     */
    public function testOutputSchemaStructure(): void
    {
        $ability = $this->getAbilityInstance();
        $schema  = $ability->getOutputSchema();

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('media_id', $schema['properties']);
        $this->assertArrayHasKey('url', $schema['properties']);
        $this->assertArrayHasKey('mime_type', $schema['properties']);
    }

    /**
     * Test SSRF protection blocks invalid URL format.
     *
     * @return void
     */
    public function testSsrfProtectionBlocksInvalidUrlFormat(): void
    {
        $ability = $this->getAbilityInstance();

        $this->expectException(MediaUploadException::class);
        $this->expectExceptionMessage('Invalid URL format');

        $ability->doExecute(
            array(
                'filename' => 'test.jpg',
                'url'      => 'not-a-valid-url',
            )
        );
    }

    /**
     * Test SSRF protection blocks non-http schemes.
     *
     * @return void
     */
    public function testSsrfProtectionBlocksNonHttpSchemes(): void
    {
        $ability = $this->getAbilityInstance();

        $this->expectException(MediaUploadException::class);
        $this->expectExceptionMessage('URL scheme must be http or https');

        // Use gopher scheme with a host to test scheme validation.
        $ability->doExecute(
            array(
                'filename' => 'test.jpg',
                'url'      => 'gopher://example.com/file.jpg',
            )
        );
    }

    /**
     * Test SSRF protection blocks ftp scheme.
     *
     * @return void
     */
    public function testSsrfProtectionBlocksFtpScheme(): void
    {
        $ability = $this->getAbilityInstance();

        $this->expectException(MediaUploadException::class);
        $this->expectExceptionMessage('URL scheme must be http or https');

        $ability->doExecute(
            array(
                'filename' => 'test.jpg',
                'url'      => 'ftp://example.com/file.jpg',
            )
        );
    }

    /**
     * Test SSRF protection blocks localhost.
     *
     * @return void
     */
    public function testSsrfProtectionBlocksLocalhost(): void
    {
        $ability = $this->getAbilityInstance();

        $this->expectException(MediaUploadException::class);
        $this->expectExceptionMessage('URLs pointing to localhost are not allowed');

        $ability->doExecute(
            array(
                'filename' => 'test.jpg',
                'url'      => 'http://localhost/file.jpg',
            )
        );
    }

    /**
     * Test SSRF protection blocks 127.0.0.1.
     *
     * @return void
     */
    public function testSsrfProtectionBlocksLoopbackIp(): void
    {
        $ability = $this->getAbilityInstance();

        $this->expectException(MediaUploadException::class);
        $this->expectExceptionMessage('URLs pointing to localhost are not allowed');

        $ability->doExecute(
            array(
                'filename' => 'test.jpg',
                'url'      => 'http://127.0.0.1/file.jpg',
            )
        );
    }

    /**
     * Test SSRF protection blocks 0.0.0.0.
     *
     * @return void
     */
    public function testSsrfProtectionBlocksZeroIp(): void
    {
        $ability = $this->getAbilityInstance();

        $this->expectException(MediaUploadException::class);
        $this->expectExceptionMessage('URLs pointing to localhost are not allowed');

        $ability->doExecute(
            array(
                'filename' => 'test.jpg',
                'url'      => 'http://0.0.0.0/file.jpg',
            )
        );
    }

    /**
     * Test SSRF protection blocks private IP range 10.x.x.x.
     *
     * @return void
     */
    public function testSsrfProtectionBlocksPrivateIpRange10(): void
    {
        $ability = $this->getAbilityInstance();

        $this->expectException(MediaUploadException::class);
        $this->expectExceptionMessage('URLs pointing to private or reserved IP ranges are not allowed');

        $ability->doExecute(
            array(
                'filename' => 'test.jpg',
                'url'      => 'http://10.0.0.1/file.jpg',
            )
        );
    }

    /**
     * Test SSRF protection blocks private IP range 172.16.x.x.
     *
     * @return void
     */
    public function testSsrfProtectionBlocksPrivateIpRange172(): void
    {
        $ability = $this->getAbilityInstance();

        $this->expectException(MediaUploadException::class);
        $this->expectExceptionMessage('URLs pointing to private or reserved IP ranges are not allowed');

        $ability->doExecute(
            array(
                'filename' => 'test.jpg',
                'url'      => 'http://172.16.0.1/file.jpg',
            )
        );
    }

    /**
     * Test SSRF protection blocks private IP range 192.168.x.x.
     *
     * @return void
     */
    public function testSsrfProtectionBlocksPrivateIpRange192(): void
    {
        $ability = $this->getAbilityInstance();

        $this->expectException(MediaUploadException::class);
        $this->expectExceptionMessage('URLs pointing to private or reserved IP ranges are not allowed');

        $ability->doExecute(
            array(
                'filename' => 'test.jpg',
                'url'      => 'http://192.168.1.1/file.jpg',
            )
        );
    }

    /**
     * Test SSRF protection blocks IPv6 localhost.
     *
     * @return void
     */
    public function testSsrfProtectionBlocksIpv6Localhost(): void
    {
        $ability = $this->getAbilityInstance();

        $this->expectException(MediaUploadException::class);
        $this->expectExceptionMessage('URLs pointing to localhost are not allowed');

        $ability->doExecute(
            array(
                'filename' => 'test.jpg',
                'url'      => 'http://[::1]/file.jpg',
            )
        );
    }
}
