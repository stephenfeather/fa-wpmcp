<?php

/**
 * Log repository for database operations.
 *
 * @package FAWpmcp\Logging
 */

declare(strict_types=1);

namespace FAWpmcp\Logging;

use FAWpmcp\ValueObjects\LogEntry;

/**
 * Repository for activity log database operations.
 *
 * Handles side effects (database reads/writes).
 * Separates pure business logic from persistence layer.
 *
 * @package FAWpmcp\Logging
 */
class LogRepository
{
    /**
     * Table name.
     *
     * @var string
     */
    private string $tableName;

    /**
     * WordPress database instance.
     *
     * @var \wpdb
     */
    private \wpdb $wpdb;

    /**
     * Constructor.
     *
     * @param \wpdb $wpdb WordPress database instance.
     */
    public function __construct(\wpdb $wpdb)
    {
        $this->wpdb      = $wpdb;
        $this->tableName = $wpdb->prefix . 'fa_wpmcp_activity_log';
    }

    /**
     * Insert a log entry.
     *
     * Side effect: database write.
     *
     * @param LogEntry $entry Log entry to insert.
     * @return int|false Insert ID or false on failure.
     */
    public function insert(LogEntry $entry): int|false
    {
        $ip_address = $this->maybeAnonymizeIp($entry->ip_address);

        $result = $this->wpdb->insert(
            $this->tableName,
            array(
                'correlation_id'    => $entry->correlation_id,
                'timestamp'         => current_time('mysql'),
                'user_id'           => $entry->user_id,
                'user_login'        => $entry->user_login,
                'ip_address'        => $ip_address,
                'ability_name'      => $entry->ability_name,
                'ability_category'  => $entry->ability_category,
                'operation_type'    => $entry->operation_type,
                'input_data'        => null !== $entry->input_data ? wp_json_encode($entry->input_data) : null,
                'output_data'       => null !== $entry->output_data ? wp_json_encode($entry->output_data) : null,
                'success'           => $entry->success ? 1 : 0,
                'error_message'     => $entry->error_message,
                'execution_time_ms' => $entry->execution_time_ms,
            ),
            array(
                '%s', // correlation_id.
                '%s', // timestamp.
                '%d', // user_id.
                '%s', // user_login.
                '%s', // ip_address.
                '%s', // ability_name.
                '%s', // ability_category.
                '%s', // operation_type.
                '%s', // input_data.
                '%s', // output_data.
                '%d', // success.
                '%s', // error_message.
                '%d', // execution_time_ms.
            )
        );

        return false !== $result ? (int) $this->wpdb->insert_id : false;
    }

    /**
     * Update entry by correlation ID.
     *
     * Side effect: database write.
     *
     * @param string $correlation_id Correlation ID to find entry.
     * @param array  $data           Data to update (keys: output_data, success, error_message, execution_time_ms).
     * @return int|false Number of rows updated or false on failure.
     */
    public function updateByCorrelationId(string $correlation_id, array $data): int|false
    {
        $update_data = array();
        $format      = array();

        if (isset($data['output_data'])) {
            $update_data['output_data'] = null !== $data['output_data'] ? wp_json_encode($data['output_data']) : null;
            $format[]                   = '%s';
        }

        if (isset($data['success'])) {
            $update_data['success'] = $data['success'] ? 1 : 0;
            $format[]               = '%d';
        }

        if (isset($data['error_message'])) {
            $update_data['error_message'] = $data['error_message'];
            $format[]                     = '%s';
        }

        if (isset($data['execution_time_ms'])) {
            $update_data['execution_time_ms'] = $data['execution_time_ms'];
            $format[]                         = '%d';
        }

        if (empty($update_data)) {
            return false;
        }

        return $this->wpdb->update(
            $this->tableName,
            $update_data,
            array( 'correlation_id' => $correlation_id ),
            $format,
            array( '%s' ) // Where format.
        );
    }

    /**
     * Get entry by correlation ID.
     *
     * Side effect: database read.
     *
     * @param string $correlation_id Correlation ID.
     * @return object|null Database row object or null if not found.
     */
    public function getByCorrelationId(string $correlation_id): ?object
    {
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
        // Table name is a property set in constructor, not user input.
        $result = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->tableName} WHERE correlation_id = %s",
                $correlation_id
            )
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

        return $result ? $result : null;
    }

    /**
     * Delete old log entries.
     *
     * Side effect: database write.
     *
     * @param int $days Number of days to retain.
     * @return int|false Number of rows deleted or false on failure.
     */
    public function deleteOlderThan(int $days): int|false
    {
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
        // Table name is a property set in constructor, not user input.
        return $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM {$this->tableName} WHERE timestamp < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days
            )
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
    }

    /**
     * Anonymize IP address if option is enabled.
     *
     * @param string $ip_address IP address to potentially anonymize.
     * @return string Original or anonymized IP address.
     */
    private function maybeAnonymizeIp(string $ip_address): string
    {
        $should_anonymize = get_option('fa_wpmcp_anonymize_ip', false);

        if (! $should_anonymize) {
            return $ip_address;
        }

        return $this->anonymizeIp($ip_address);
    }

    /**
     * Anonymize IP address for GDPR compliance.
     *
     * IPv4: Mask last octet (192.168.1.100 → 192.168.1.0)
     * IPv6: Mask last 80 bits (2001:db8::1 → 2001:db8::)
     *
     * @param string $ip_address IP address to anonymize.
     * @return string Anonymized IP address.
     */
    private function anonymizeIp(string $ip_address): string
    {
        // Handle empty or invalid input.
        if (empty($ip_address)) {
            return $ip_address;
        }

        // Check if IPv6.
        if (false !== strpos($ip_address, ':')) {
            return $this->anonymizeIpv6($ip_address);
        }

        // Assume IPv4.
        return $this->anonymizeIpv4($ip_address);
    }

    /**
     * Anonymize IPv4 address by masking last octet.
     *
     * @param string $ip IPv4 address.
     * @return string Anonymized IPv4 address.
     */
    private function anonymizeIpv4(string $ip): string
    {
        $binary = @inet_pton($ip);

        if (false === $binary || 4 !== strlen($binary)) {
            // Invalid IPv4, return as-is.
            return $ip;
        }

        // Mask last octet (set to 0).
        $binary[3] = "\0";

        $anonymized = inet_ntop($binary);

        return false !== $anonymized ? $anonymized : $ip;
    }

    /**
     * Anonymize IPv6 address by masking last 80 bits (keep /48 prefix).
     *
     * @param string $ip IPv6 address.
     * @return string Anonymized IPv6 address.
     */
    private function anonymizeIpv6(string $ip): string
    {
        $binary = @inet_pton($ip);

        if (false === $binary || 16 !== strlen($binary)) {
            // Invalid IPv6, return as-is.
            return $ip;
        }

        // Keep first 48 bits (6 bytes), zero out last 80 bits (10 bytes).
        for ($i = 6; $i < 16; $i++) {
            $binary[ $i ] = "\0";
        }

        $anonymized = inet_ntop($binary);

        return false !== $anonymized ? $anonymized : $ip;
    }
}
