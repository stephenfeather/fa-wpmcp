<?php
/**
 * Privacy redaction utility for sensitive data.
 *
 * Provides a pure function for recursively redacting sensitive fields
 * from data structures before logging or transmission.
 *
 * @package FAWpmcp\Http
 */

declare(strict_types=1);

namespace FAWpmcp\Http;

/**
 * Privacy redactor for sensitive field masking.
 *
 * All methods are pure functions - they do not modify the input
 * and return new arrays with sensitive data redacted.
 */
final class PrivacyRedactor {

    /**
     * The redaction placeholder string.
     *
     * @var string
     */
    private const REDACTED = '[REDACTED]';

    /**
     * List of sensitive field names to redact (lowercase for comparison).
     *
     * @var array<string>
     */
    private const SENSITIVE_FIELDS = array(
        'password',
        'token',
        'api_key',
        'secret',
        'user_pass',
        'apikey',
        'access_token',
        'refresh_token',
    );

    /**
     * Redact sensitive fields from data.
     *
     * This is a pure function - it does NOT modify the input array,
     * but returns a new array with sensitive values replaced.
     *
     * @param array<mixed> $data The data to redact.
     * @return array<mixed> A new array with sensitive fields redacted.
     */
    public static function redact( array $data ): array {
        $result = array();

        foreach ( $data as $key => $value ) {
            if ( is_array( $value ) ) {
                // Recursively redact nested arrays.
                $result[ $key ] = self::redact( $value );
            } elseif ( self::is_sensitive_field( $key ) ) {
                // Redact sensitive field values.
                $result[ $key ] = self::REDACTED;
            } else {
                // Preserve non-sensitive values.
                $result[ $key ] = $value;
            }
        }

        return $result;
    }

    /**
     * Check if a field name is sensitive.
     *
     * Performs case-insensitive exact match against sensitive field list.
     *
     * @param string|int $field_name The field name to check.
     * @return bool True if the field is sensitive.
     */
    private static function is_sensitive_field( string|int $field_name ): bool {
        // Numeric keys are never sensitive field names.
        if ( is_int( $field_name ) ) {
            return false;
        }

        return in_array( strtolower( $field_name ), self::SENSITIVE_FIELDS, true );
    }
}
