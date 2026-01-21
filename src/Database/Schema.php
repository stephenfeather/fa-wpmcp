<?php
/**
 * Database schema generation (pure functions).
 *
 * @package FAWpmcp\Database
 */

declare(strict_types=1);

namespace FAWpmcp\Database;

/**
 * Pure functions for generating SQL schemas.
 *
 * All methods are static and pure - they have no side effects and
 * return the same output for the same input. The SQL strings returned
 * are designed for use with WordPress dbDelta().
 *
 * @package FAWpmcp\Database
 */
final class Schema {
    /**
     * Generate activity log table SQL.
     *
     * Pure function: same prefix always produces same SQL.
     *
     * @param string $prefix Database table prefix (e.g., 'wp_').
     * @return string SQL CREATE TABLE statement.
     */
    public static function getActivityLogSchema( string $prefix ): string {
        $table = "{$prefix}fa_wpmcp_activity_log";

        return "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            correlation_id VARCHAR(36) NOT NULL,
            timestamp DATETIME NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            user_login VARCHAR(60) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            ability_name VARCHAR(255) NOT NULL,
            ability_category VARCHAR(100) NOT NULL,
            operation_type ENUM('read', 'write') NOT NULL,
            input_data LONGTEXT,
            output_data LONGTEXT,
            success BOOLEAN NOT NULL,
            error_message TEXT,
            execution_time_ms INT UNSIGNED,
            PRIMARY KEY (id),
            INDEX idx_correlation_id (correlation_id),
            INDEX idx_timestamp (timestamp),
            INDEX idx_user_id (user_id),
            INDEX idx_ability_name (ability_name),
            INDEX idx_success (success)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    }

    /**
     * Generate webhook queue table SQL.
     *
     * Pure function: same prefix always produces same SQL.
     *
     * @param string $prefix Database table prefix (e.g., 'wp_').
     * @return string SQL CREATE TABLE statement.
     */
    public static function getWebhookQueueSchema( string $prefix ): string {
        $table = "{$prefix}fa_wpmcp_webhook_queue";

        return "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            url VARCHAR(2048) NOT NULL,
            event_type VARCHAR(100) NOT NULL,
            payload LONGTEXT NOT NULL,
            signature VARCHAR(64) NOT NULL,
            status ENUM('pending', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'pending',
            retry_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            next_retry_at DATETIME,
            completed_at DATETIME,
            error_message TEXT,
            PRIMARY KEY (id),
            INDEX idx_status (status),
            INDEX idx_next_retry_at (next_retry_at),
            INDEX idx_event_type (event_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    }

    /**
     * Get all schema SQL statements.
     *
     * Pure function: returns array of SQL strings.
     *
     * @param string $prefix Database table prefix (e.g., 'wp_').
     * @return array<int, string> Array of CREATE TABLE statements.
     */
    public static function getAllSchemas( string $prefix ): array {
        return array(
            self::getActivityLogSchema( $prefix ),
            self::getWebhookQueueSchema( $prefix ),
        );
    }
}
