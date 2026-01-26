<?php

/**
 * Tests for ActivityLogger.
 *
 * @package FAWpmcp\Tests\Logging
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Logging;

use FAWpmcp\Logging\ActivityLogger;
use FAWpmcp\Logging\LogRepository;
use FAWpmcp\Http\PrivacyRedactor;
use FAWpmcp\ValueObjects\LogEntry;
use PHPUnit\Framework\TestCase;
use Mockery;

/**
 * Test ActivityLogger orchestration.
 *
 * @package FAWpmcp\Tests\Logging
 */
class ActivityLoggerTest extends TestCase
{
    /**
     * Tear down Mockery after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test logBeforeExecute returns correlation ID.
     *
     * @return void
     */
    public function test_log_before_execute_returns_correlation_id(): void
    {
        $repository = Mockery::mock(LogRepository::class);
        $repository->shouldReceive('insert')->once();

        $uuid_generator = fn() => 'mock-uuid-123';
        $logger         = new ActivityLogger($repository, $uuid_generator);

        $correlation_id = $logger->logBeforeExecute(
            'fa-wpmcp/list-posts',
            'posts-pages',
            'read',
            1,
            'admin',
            '127.0.0.1',
            array( 'page' => 1 )
        );

        $this->assertEquals('mock-uuid-123', $correlation_id);
    }

    /**
     * Test logBeforeExecute inserts entry into repository.
     *
     * @return void
     */
    public function test_log_before_execute_inserts_entry(): void
    {
        $repository = Mockery::mock(LogRepository::class);
        $repository->shouldReceive('insert')
            ->once()
            ->with(Mockery::on(fn($entry) => $entry instanceof LogEntry && 'test-uuid' === $entry->correlation_id))
            ->andReturn(1);

        $uuid_generator = fn() => 'test-uuid';
        $logger         = new ActivityLogger($repository, $uuid_generator);

        $correlation_id = $logger->logBeforeExecute(
            'fa-wpmcp/create-post',
            'posts-pages',
            'write',
            1,
            'admin',
            '192.168.1.1',
            array( 'title' => 'Test Post' )
        );

        $this->assertEquals('test-uuid', $correlation_id);
    }

    /**
     * Test logAfterExecute updates entry.
     *
     * @return void
     */
    public function test_log_after_execute_updates_entry(): void
    {
        $repository = Mockery::mock(LogRepository::class);
        $repository->shouldReceive('insert')->once()->andReturn(1);
        $repository->shouldReceive('updateByCorrelationId')
            ->once()
            ->with(
                'mock-uuid',
                Mockery::on(fn($data) => true === $data['success'] && $data['execution_time_ms'] > 0)
            )
            ->andReturn(1);

        $uuid_generator = fn() => 'mock-uuid';
        $logger         = new ActivityLogger($repository, $uuid_generator);

        $start_time     = microtime(true);
        $correlation_id = $logger->logBeforeExecute(
            'fa-wpmcp/list-posts',
            'posts-pages',
            'read',
            1,
            'admin',
            '127.0.0.1',
            array()
        );

        // Simulate some execution time.
        usleep(1000); // 1ms.

        $logger->logAfterExecute(
            $correlation_id,
            array( 'posts' => array() ),
            true,
            null,
            $start_time
        );

        // Verify correlation ID was returned correctly.
        $this->assertEquals('mock-uuid', $correlation_id);
    }

    /**
     * Test logAfterExecute with failure.
     *
     * @return void
     */
    public function test_log_after_execute_with_failure(): void
    {
        $repository = Mockery::mock(LogRepository::class);
        $repository->shouldReceive('insert')->once()->andReturn(1);
        $repository->shouldReceive('updateByCorrelationId')
            ->once()
            ->with(
                'fail-uuid',
                Mockery::on(fn($data) => false === $data['success'] && 'Permission denied' === $data['error_message'])
            )
            ->andReturn(1);

        $uuid_generator = fn() => 'fail-uuid';
        $logger         = new ActivityLogger($repository, $uuid_generator);

        $start_time     = microtime(true);
        $correlation_id = $logger->logBeforeExecute(
            'fa-wpmcp/delete-post',
            'posts-pages',
            'write',
            1,
            'admin',
            '127.0.0.1',
            array( 'post_id' => 123 )
        );

        $logger->logAfterExecute(
            $correlation_id,
            null,
            false,
            'Permission denied',
            $start_time
        );

        // Verify correlation ID was returned correctly.
        $this->assertEquals('fail-uuid', $correlation_id);
    }

    /**
     * Test execution time is measured correctly.
     *
     * @return void
     */
    public function test_execution_time_measured(): void
    {
        $repository = Mockery::mock(LogRepository::class);
        $repository->shouldReceive('insert')->once()->andReturn(1);
        $repository->shouldReceive('updateByCorrelationId')
            ->once()
            ->with(
                'time-uuid',
                Mockery::on(fn($data) => $data['execution_time_ms'] >= 10) // At least 10ms.
            )
            ->andReturn(1);

        $uuid_generator = fn() => 'time-uuid';
        $logger         = new ActivityLogger($repository, $uuid_generator);

        $start_time     = microtime(true);
        $correlation_id = $logger->logBeforeExecute(
            'fa-wpmcp/list-posts',
            'posts-pages',
            'read',
            1,
            'admin',
            '127.0.0.1',
            array()
        );

        // Simulate 10ms execution.
        usleep(10000);

        $logger->logAfterExecute(
            $correlation_id,
            array( 'posts' => array() ),
            true,
            null,
            $start_time
        );

        // Verify correlation ID was returned correctly.
        $this->assertEquals('time-uuid', $correlation_id);
    }

    /**
     * Test log_before_execute redacts password field in input data.
     *
     * @return void
     */
    public function test_redacts_password_in_input_data(): void
    {
        $repository = Mockery::mock(LogRepository::class);
        $repository->shouldReceive('insert')
            ->once()
            ->with(
                Mockery::on(
                    function ($entry) {
                        // Verify the input data has password redacted.
                        $input = $entry->input_data;
                        return '[REDACTED]' === $input['password']
                            && 'testuser' === $input['username'];
                    }
                )
            )
            ->andReturn(1);

        $uuid_generator = fn() => 'redact-uuid';
        $logger         = new ActivityLogger($repository, $uuid_generator);

        $correlation_id = $logger->logBeforeExecute(
            'fa-wpmcp/create-user',
            'users',
            'write',
            1,
            'admin',
            '127.0.0.1',
            array(
                'username' => 'testuser',
                'password' => 'secret123',
            )
        );

        $this->assertSame('redact-uuid', $correlation_id);
    }

    /**
     * Test log_after_execute redacts token field in output data.
     *
     * @return void
     */
    public function test_redacts_token_in_output_data(): void
    {
        $repository = Mockery::mock(LogRepository::class);
        $repository->shouldReceive('insert')->once()->andReturn(1);
        $repository->shouldReceive('updateByCorrelationId')
            ->once()
            ->with(
                'token-uuid',
                Mockery::on(
                    function ($data) {
                        // Verify the output data has token redacted.
                        $output = $data['output_data'];
                        return '[REDACTED]' === $output['token']
                            && 'user-123' === $output['user_id'];
                    }
                )
            )
            ->andReturn(1);

        $uuid_generator = fn() => 'token-uuid';
        $logger         = new ActivityLogger($repository, $uuid_generator);

        $start_time     = microtime(true);
        $correlation_id = $logger->logBeforeExecute(
            'fa-wpmcp/login',
            'auth',
            'write',
            1,
            'admin',
            '127.0.0.1',
            array()
        );

        $logger->logAfterExecute(
            $correlation_id,
            array(
                'user_id' => 'user-123',
                'token'   => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9',
            ),
            true,
            null,
            $start_time
        );

        $this->assertSame('token-uuid', $correlation_id);
    }

    /**
     * Test that non-sensitive fields are preserved unchanged.
     *
     * @return void
     */
    public function test_preserves_non_sensitive_fields(): void
    {
        $repository = Mockery::mock(LogRepository::class);
        $repository->shouldReceive('insert')
            ->once()
            ->with(
                Mockery::on(
                    function ($entry) {
                        // Verify non-sensitive fields are preserved.
                        $input = $entry->input_data;
                        return 'Test Post' === $input['title']
                            && 'This is content' === $input['content']
                            && 'published' === $input['status'];
                    }
                )
            )
            ->andReturn(1);

        $uuid_generator = fn() => 'preserve-uuid';
        $logger         = new ActivityLogger($repository, $uuid_generator);

        $correlation_id = $logger->logBeforeExecute(
            'fa-wpmcp/create-post',
            'posts-pages',
            'write',
            1,
            'admin',
            '127.0.0.1',
            array(
                'title'   => 'Test Post',
                'content' => 'This is content',
                'status'  => 'published',
            )
        );

        $this->assertSame('preserve-uuid', $correlation_id);
    }

    /**
     * Test redacts nested sensitive fields in input data.
     *
     * @return void
     */
    public function test_redacts_nested_sensitive_fields(): void
    {
        $repository = Mockery::mock(LogRepository::class);
        $repository->shouldReceive('insert')
            ->once()
            ->with(
                Mockery::on(
                    function ($entry) {
                        // Verify nested sensitive fields are redacted.
                        $input = $entry->input_data;
                        return '[REDACTED]' === $input['credentials']['password']
                            && '[REDACTED]' === $input['credentials']['api_key']
                            && 'testuser' === $input['credentials']['username'];
                    }
                )
            )
            ->andReturn(1);

        $uuid_generator = fn() => 'nested-uuid';
        $logger         = new ActivityLogger($repository, $uuid_generator);

        $correlation_id = $logger->logBeforeExecute(
            'fa-wpmcp/auth',
            'auth',
            'write',
            1,
            'admin',
            '127.0.0.1',
            array(
                'credentials' => array(
                    'username' => 'testuser',
                    'password' => 'secret123',
                    'api_key'  => 'key-abc-123',
                ),
            )
        );

        $this->assertSame('nested-uuid', $correlation_id);
    }
}
