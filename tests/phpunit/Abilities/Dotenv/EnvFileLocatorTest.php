<?php

/**
 * Tests for EnvFileLocator.
 *
 * @package FAWpmcp\Tests\Abilities\Dotenv
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Dotenv;

use Brain\Monkey\Functions;
use FAWpmcp\Abilities\Dotenv\EnvFileLocator;
use FAWpmcp\Exceptions\DotenvException;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;

/**
 * Test EnvFileLocator functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Dotenv
 */
class EnvFileLocatorTest extends BrainMonkeyTestCase
{
    /**
     * The locator instance under test.
     *
     * @var EnvFileLocator
     */
    private EnvFileLocator $locator;

    /**
     * Temporary directory for tests.
     *
     * @var string
     */
    private string $tempDir;

    /**
     * Set up test fixtures.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->locator = new EnvFileLocator();

        // Create temp directory structure.
        $this->tempDir = sys_get_temp_dir() . '/envlocator_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
    }

    /**
     * Tear down test fixtures.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        // Clean up temp files.
        $this->recursiveDelete($this->tempDir);
        parent::tearDown();
    }

    /**
     * Recursively delete a directory.
     *
     * @param string $dir Directory path.
     * @return void
     */
    private function recursiveDelete(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir) ?: array(), array('.', '..'));
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->recursiveDelete($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    /**
     * Test locate uses filter override when provided.
     *
     * @return void
     */
    public function testLocateUsesFilterOverrideWhenProvided(): void
    {
        $env_file = $this->tempDir . '/.env';
        file_put_contents($env_file, 'TEST=value');

        Functions\expect('apply_filters')
            ->once()
            ->with('fa_wpmcp_dotenv_file_path', '')
            ->andReturn($env_file);

        $result = $this->locator->locate();

        $this->assertEquals($env_file, $result);
    }

    /**
     * Test locate detects Bedrock structure via filter override.
     *
     * Note: ABSPATH detection cannot be tested easily since ABSPATH is defined
     * at runtime. We test the filter mechanism which is the recommended
     * approach for custom .env locations.
     *
     * @return void
     */
    public function testLocateDetectsBedrockStructureViaFilter(): void
    {
        // Create Bedrock-like structure.
        $project_root = $this->tempDir . '/project';
        $web_wp = $project_root . '/web/wp';
        mkdir($web_wp, 0755, true);

        $env_file = $project_root . '/.env';
        file_put_contents($env_file, 'WP_ENV=development');

        // Use filter to specify the .env path (recommended approach).
        Functions\expect('apply_filters')
            ->once()
            ->with('fa_wpmcp_dotenv_file_path', '')
            ->andReturn($env_file);

        $result = $this->locator->locate();
        $this->assertEquals($env_file, $result);
    }

    /**
     * Test locate throws exception when file not found.
     *
     * @return void
     */
    public function testLocateThrowsExceptionWhenFileNotFound(): void
    {
        Functions\expect('apply_filters')
            ->once()
            ->with('fa_wpmcp_dotenv_file_path', '')
            ->andReturn('');

        $this->expectException(DotenvException::class);
        $this->expectExceptionMessage('Could not locate .env file');

        $this->locator->locate();
    }

    /**
     * Test exists returns true when file can be located.
     *
     * @return void
     */
    public function testExistsReturnsTrueWhenFileCanBeLocated(): void
    {
        $env_file = $this->tempDir . '/.env';
        file_put_contents($env_file, 'TEST=value');

        Functions\expect('apply_filters')
            ->once()
            ->with('fa_wpmcp_dotenv_file_path', '')
            ->andReturn($env_file);

        $this->assertTrue($this->locator->exists());
    }

    /**
     * Test exists returns false when file cannot be located.
     *
     * @return void
     */
    public function testExistsReturnsFalseWhenFileCannotBeLocated(): void
    {
        Functions\expect('apply_filters')
            ->once()
            ->with('fa_wpmcp_dotenv_file_path', '')
            ->andReturn('');

        $this->assertFalse($this->locator->exists());
    }

    /**
     * Test locate ignores empty filter return.
     *
     * @return void
     */
    public function testLocateIgnoresEmptyFilterReturn(): void
    {
        Functions\expect('apply_filters')
            ->once()
            ->with('fa_wpmcp_dotenv_file_path', '')
            ->andReturn('');

        $this->expectException(DotenvException::class);
        $this->locator->locate();
    }

    /**
     * Test locate ignores non-existent filter path.
     *
     * @return void
     */
    public function testLocateIgnoresNonExistentFilterPath(): void
    {
        Functions\expect('apply_filters')
            ->once()
            ->with('fa_wpmcp_dotenv_file_path', '')
            ->andReturn('/non/existent/path/.env');

        $this->expectException(DotenvException::class);
        $this->locator->locate();
    }
}
