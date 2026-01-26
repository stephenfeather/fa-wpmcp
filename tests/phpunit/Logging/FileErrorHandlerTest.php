<?php

/**
 * Tests for FileErrorHandler.
 *
 * @package FAWpmcp\Tests\Logging
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Logging;

use FAWpmcp\Logging\FileErrorHandler;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Test FileErrorHandler functionality.
 *
 * @package FAWpmcp\Tests\Logging
 */
class FileErrorHandlerTest extends TestCase
{
    /**
     * Temporary log file path for testing.
     *
     * @var string
     */
    private string $temp_log_file;

    /**
     * Set up Brain\Monkey and temp file before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        // Create a temporary log file for testing.
        $this->temp_log_file = sys_get_temp_dir() . '/mcp-errors-test-' . uniqid() . '.log';
    }

    /**
     * Tear down Brain\Monkey and temp file after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Monkey\tearDown();

        // Clean up temp file.
        if (file_exists($this->temp_log_file)) {
            unlink($this->temp_log_file);
        }

        parent::tearDown();
    }

    // =========================================================================
    // Enable/Disable Tests
    // =========================================================================

    /**
     * Test does not log when disabled.
     *
     * @return void
     */
    public function test_does_not_log_when_disabled(): void
    {
        Functions\expect('get_option')
            ->once()
            ->with('fa_wpmcp_settings', array())
            ->andReturn(array( 'file_error_logging_enabled' => false ));

        $handler = new FileErrorHandler($this->temp_log_file);
        $handler->log('Test error message', array( 'key' => 'value' ));

        $this->assertFileDoesNotExist($this->temp_log_file);
    }

    /**
     * Test logs when enabled.
     *
     * @return void
     */
    public function test_logs_when_enabled(): void
    {
        Functions\expect('get_option')
            ->once()
            ->with('fa_wpmcp_settings', array())
            ->andReturn(array( 'file_error_logging_enabled' => true ));

        Functions\expect('wp_json_encode')
            ->once()
            ->andReturnUsing(fn($data) => json_encode($data));

        $handler = new FileErrorHandler($this->temp_log_file);
        $handler->log('Test error message', array( 'key' => 'value' ));

        $this->assertFileExists($this->temp_log_file);
        $content = file_get_contents($this->temp_log_file);
        $this->assertStringContainsString('Test error message', $content);
    }

    /**
     * Test isEnabled returns false when setting is missing.
     *
     * @return void
     */
    public function test_is_enabled_returns_false_when_setting_missing(): void
    {
        Functions\expect('get_option')
            ->once()
            ->with('fa_wpmcp_settings', array())
            ->andReturn(array());

        $handler = new FileErrorHandler($this->temp_log_file);

        $this->assertFalse($handler->isEnabled());
    }

    /**
     * Test isEnabled returns true when setting is true.
     *
     * @return void
     */
    public function test_is_enabled_returns_true_when_setting_true(): void
    {
        Functions\expect('get_option')
            ->once()
            ->with('fa_wpmcp_settings', array())
            ->andReturn(array( 'file_error_logging_enabled' => true ));

        $handler = new FileErrorHandler($this->temp_log_file);

        $this->assertTrue($handler->isEnabled());
    }

    /**
     * Test isEnabled caches the result.
     *
     * @return void
     */
    public function test_is_enabled_caches_result(): void
    {
        Functions\expect('get_option')
            ->once() // Only called once despite multiple isEnabled() calls.
            ->with('fa_wpmcp_settings', array())
            ->andReturn(array( 'file_error_logging_enabled' => true ));

        $handler = new FileErrorHandler($this->temp_log_file);

        // Call multiple times.
        $handler->isEnabled();
        $handler->isEnabled();
        $handler->isEnabled();

        $this->assertTrue($handler->isEnabled());
    }

    // =========================================================================
    // Log Format Tests
    // =========================================================================

    /**
     * Test log entry contains timestamp.
     *
     * @return void
     */
    public function test_log_entry_contains_timestamp(): void
    {
        Functions\expect('get_option')
            ->once()
            ->andReturn(array( 'file_error_logging_enabled' => true ));

        Functions\expect('wp_json_encode')
            ->once()
            ->andReturnUsing(fn($data) => json_encode($data));

        $handler = new FileErrorHandler($this->temp_log_file);
        $handler->log('Test message');

        $content = file_get_contents($this->temp_log_file);

        // Should contain a timestamp in Y-m-d H:i:s format.
        $this->assertMatchesRegularExpression('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/', $content);
    }

    /**
     * Test log entry contains uppercase type.
     *
     * @return void
     */
    public function test_log_entry_contains_uppercase_type(): void
    {
        Functions\expect('get_option')
            ->once()
            ->andReturn(array( 'file_error_logging_enabled' => true ));

        Functions\expect('wp_json_encode')
            ->once()
            ->andReturnUsing(fn($data) => json_encode($data));

        $handler = new FileErrorHandler($this->temp_log_file);
        $handler->log('Test message', array(), 'warning');

        $content = file_get_contents($this->temp_log_file);

        $this->assertStringContainsString('[WARNING]', $content);
    }

    /**
     * Test log entry contains message.
     *
     * @return void
     */
    public function test_log_entry_contains_message(): void
    {
        Functions\expect('get_option')
            ->once()
            ->andReturn(array( 'file_error_logging_enabled' => true ));

        Functions\expect('wp_json_encode')
            ->once()
            ->andReturnUsing(fn($data) => json_encode($data));

        $handler = new FileErrorHandler($this->temp_log_file);
        $handler->log('Specific error occurred in module X');

        $content = file_get_contents($this->temp_log_file);

        $this->assertStringContainsString('Specific error occurred in module X', $content);
    }

    /**
     * Test log entry contains JSON context.
     *
     * @return void
     */
    public function test_log_entry_contains_json_context(): void
    {
        Functions\expect('get_option')
            ->once()
            ->andReturn(array( 'file_error_logging_enabled' => true ));

        Functions\expect('wp_json_encode')
            ->once()
            ->andReturnUsing(fn($data) => json_encode($data));

        $handler = new FileErrorHandler($this->temp_log_file);
        $handler->log(
            'Test message',
            array(
                'user_id' => 123,
                'action' => 'delete',
            )
        );

        $content = file_get_contents($this->temp_log_file);

        $this->assertStringContainsString('Context:', $content);
        $this->assertStringContainsString('"user_id":123', $content);
        $this->assertStringContainsString('"action":"delete"', $content);
    }

    /**
     * Test default type is error.
     *
     * @return void
     */
    public function test_default_type_is_error(): void
    {
        Functions\expect('get_option')
            ->once()
            ->andReturn(array( 'file_error_logging_enabled' => true ));

        Functions\expect('wp_json_encode')
            ->once()
            ->andReturnUsing(fn($data) => json_encode($data));

        $handler = new FileErrorHandler($this->temp_log_file);
        $handler->log('Test message');

        $content = file_get_contents($this->temp_log_file);

        $this->assertStringContainsString('[ERROR]', $content);
    }

    // =========================================================================
    // Log Types Tests
    // =========================================================================

    /**
     * Test supports error type.
     *
     * @return void
     */
    public function test_supports_error_type(): void
    {
        Functions\expect('get_option')
            ->once()
            ->andReturn(array( 'file_error_logging_enabled' => true ));

        Functions\expect('wp_json_encode')
            ->once()
            ->andReturnUsing(fn($data) => json_encode($data));

        $handler = new FileErrorHandler($this->temp_log_file);
        $handler->log('Test', array(), 'error');

        $content = file_get_contents($this->temp_log_file);

        $this->assertStringContainsString('[ERROR]', $content);
    }

    /**
     * Test supports info type.
     *
     * @return void
     */
    public function test_supports_info_type(): void
    {
        Functions\expect('get_option')
            ->once()
            ->andReturn(array( 'file_error_logging_enabled' => true ));

        Functions\expect('wp_json_encode')
            ->once()
            ->andReturnUsing(fn($data) => json_encode($data));

        $handler = new FileErrorHandler($this->temp_log_file);
        $handler->log('Test', array(), 'info');

        $content = file_get_contents($this->temp_log_file);

        $this->assertStringContainsString('[INFO]', $content);
    }

    /**
     * Test supports debug type.
     *
     * @return void
     */
    public function test_supports_debug_type(): void
    {
        Functions\expect('get_option')
            ->once()
            ->andReturn(array( 'file_error_logging_enabled' => true ));

        Functions\expect('wp_json_encode')
            ->once()
            ->andReturnUsing(fn($data) => json_encode($data));

        $handler = new FileErrorHandler($this->temp_log_file);
        $handler->log('Test', array(), 'debug');

        $content = file_get_contents($this->temp_log_file);

        $this->assertStringContainsString('[DEBUG]', $content);
    }

    // =========================================================================
    // File Operations Tests
    // =========================================================================

    /**
     * Test appends to existing log file.
     *
     * @return void
     */
    public function test_appends_to_existing_log_file(): void
    {
        Functions\expect('get_option')
            ->andReturn(array( 'file_error_logging_enabled' => true ));

        Functions\expect('wp_json_encode')
            ->andReturnUsing(fn($data) => json_encode($data));

        $handler = new FileErrorHandler($this->temp_log_file);

        $handler->log('First message');
        $handler->log('Second message');
        $handler->log('Third message');

        $content = file_get_contents($this->temp_log_file);

        $this->assertStringContainsString('First message', $content);
        $this->assertStringContainsString('Second message', $content);
        $this->assertStringContainsString('Third message', $content);
    }

    /**
     * Test each log entry ends with newline.
     *
     * @return void
     */
    public function test_each_log_entry_ends_with_newline(): void
    {
        Functions\expect('get_option')
            ->andReturn(array( 'file_error_logging_enabled' => true ));

        Functions\expect('wp_json_encode')
            ->andReturnUsing(fn($data) => json_encode($data));

        $handler = new FileErrorHandler($this->temp_log_file);

        $handler->log('First message');
        $handler->log('Second message');

        $content = file_get_contents($this->temp_log_file);
        $lines   = explode("\n", trim($content));

        $this->assertCount(2, $lines);
    }

    // =========================================================================
    // Log File Path Tests
    // =========================================================================

    /**
     * Test getLogFilePath returns injected path.
     *
     * @return void
     */
    public function test_get_log_file_path_returns_injected_path(): void
    {
        $handler = new FileErrorHandler('/custom/path/errors.log');

        $this->assertEquals('/custom/path/errors.log', $handler->getLogFilePath());
    }

    /**
     * Test getLogFilePath uses WP_CONTENT_DIR when no path injected.
     *
     * @return void
     */
    public function test_get_log_file_path_uses_wp_content_dir(): void
    {
        // WP_CONTENT_DIR is not defined in tests, so path will be '/mcp-errors.log'.
        $handler = new FileErrorHandler();

        $path = $handler->getLogFilePath();

        $this->assertStringEndsWith('mcp-errors.log', $path);
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    /**
     * Test handles empty context array.
     *
     * @return void
     */
    public function test_handles_empty_context_array(): void
    {
        Functions\expect('get_option')
            ->once()
            ->andReturn(array( 'file_error_logging_enabled' => true ));

        Functions\expect('wp_json_encode')
            ->once()
            ->andReturnUsing(fn($data) => json_encode($data));

        $handler = new FileErrorHandler($this->temp_log_file);
        $handler->log('Test message', array());

        $content = file_get_contents($this->temp_log_file);

        $this->assertStringContainsString('Context: []', $content);
    }

    /**
     * Test handles nested context data.
     *
     * @return void
     */
    public function test_handles_nested_context_data(): void
    {
        Functions\expect('get_option')
            ->once()
            ->andReturn(array( 'file_error_logging_enabled' => true ));

        Functions\expect('wp_json_encode')
            ->once()
            ->andReturnUsing(fn($data) => json_encode($data));

        $handler = new FileErrorHandler($this->temp_log_file);
        $handler->log(
            'Test message',
            array(
                'user'  => array(
                    'id'   => 1,
                    'name' => 'admin',
                ),
                'trace' => array( 'file1.php', 'file2.php' ),
            )
        );

        $content = file_get_contents($this->temp_log_file);

        $this->assertStringContainsString('"user":', $content);
        $this->assertStringContainsString('"id":1', $content);
    }

    /**
     * Test handles non-array settings option.
     *
     * @return void
     */
    public function test_handles_non_array_settings_option(): void
    {
        Functions\expect('get_option')
            ->once()
            ->with('fa_wpmcp_settings', array())
            ->andReturn('invalid');

        $handler = new FileErrorHandler($this->temp_log_file);

        $this->assertFalse($handler->isEnabled());
    }

    /**
     * Test implements McpErrorHandlerInterface.
     *
     * @return void
     */
    public function test_implements_mcp_error_handler_interface(): void
    {
        $handler = new FileErrorHandler($this->temp_log_file);

        $this->assertInstanceOf(
            \WP\MCP\Infrastructure\ErrorHandling\Contracts\McpErrorHandlerInterface::class,
            $handler
        );
    }
}
