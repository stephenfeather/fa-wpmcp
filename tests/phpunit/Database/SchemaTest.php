<?php

/**
 * Test database schema generation.
 *
 * @package FAWpmcp\Tests\Database
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Database;

use FAWpmcp\Database\Schema;
use PHPUnit\Framework\TestCase;

/**
 * Test database schema generation.
 */
class SchemaTest extends TestCase
{
    /**
     * Test activity log schema generation.
     */
    public function test_get_activity_log_schema_returns_valid_sql(): void
    {
        $sql = Schema::getActivityLogSchema('wp_');

        $this->assertStringContainsString('CREATE TABLE', $sql);
        $this->assertStringContainsString('wp_fa_wpmcp_activity_log', $sql);
        $this->assertStringContainsString('correlation_id', $sql);
        $this->assertStringContainsString('PRIMARY KEY', $sql);
        $this->assertStringContainsString('user_id', $sql);
        $this->assertStringContainsString('ability_name', $sql);
    }

    /**
     * Test webhook queue schema generation.
     */
    public function test_get_webhook_queue_schema_returns_valid_sql(): void
    {
        $sql = Schema::getWebhookQueueSchema('wp_');

        $this->assertStringContainsString('CREATE TABLE', $sql);
        $this->assertStringContainsString('wp_fa_wpmcp_webhook_queue', $sql);
        $this->assertStringContainsString('PRIMARY KEY', $sql);
    }

    /**
     * Test schema uses provided prefix.
     */
    public function test_schema_uses_provided_prefix(): void
    {
        $sql = Schema::getActivityLogSchema('custom_prefix_');

        $this->assertStringContainsString('custom_prefix_fa_wpmcp_activity_log', $sql);
        $this->assertStringNotContainsString('wp_fa_wpmcp_activity_log', $sql);
    }

    /**
     * Test get all schemas returns array.
     */
    public function test_get_all_schemas_returns_array(): void
    {
        $schemas = Schema::getAllSchemas('wp_');

        $this->assertIsArray($schemas);
        $this->assertCount(2, $schemas);
        $this->assertStringContainsString('CREATE TABLE', $schemas[0]);
        $this->assertStringContainsString('CREATE TABLE', $schemas[1]);
    }

    /**
     * Test activity log schema includes all required columns.
     */
    public function test_activity_log_schema_includes_required_columns(): void
    {
        $sql = Schema::getActivityLogSchema('wp_');

        $required_columns = array(
            'id',
            'correlation_id',
            'timestamp',
            'user_id',
            'user_login',
            'ip_address',
            'ability_name',
            'ability_category',
            'operation_type',
            'input_data',
            'output_data',
            'success',
            'error_message',
            'execution_time_ms',
        );

        foreach ($required_columns as $column) {
            $this->assertStringContainsString($column, $sql, "Missing column: {$column}");
        }
    }

    /**
     * Test activity log schema includes indexes.
     */
    public function test_activity_log_schema_includes_indexes(): void
    {
        $sql = Schema::getActivityLogSchema('wp_');

        $this->assertStringContainsString('INDEX', $sql);
        $this->assertStringContainsString('idx_correlation_id', $sql);
        $this->assertStringContainsString('idx_timestamp', $sql);
        $this->assertStringContainsString('idx_user_id', $sql);
    }

    /**
     * Test webhook queue schema includes required columns.
     */
    public function test_webhook_queue_schema_includes_required_columns(): void
    {
        $sql = Schema::getWebhookQueueSchema('wp_');

        $required_columns = array(
            'id',
            'event_type',
            'payload',
            'status',
            'retry_count',
            'created_at',
            'next_retry_at',
        );

        foreach ($required_columns as $column) {
            $this->assertStringContainsString($column, $sql, "Missing column: {$column}");
        }
    }

    /**
     * Test schema is deterministic (pure function).
     */
    public function test_schema_is_deterministic(): void
    {
        $sql1 = Schema::getActivityLogSchema('wp_');
        $sql2 = Schema::getActivityLogSchema('wp_');

        $this->assertSame($sql1, $sql2, 'Schema generation should be deterministic');
    }
}
