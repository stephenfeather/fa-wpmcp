<?php
/**
 * Result value object for functional programming patterns.
 *
 * @package FAWpmcp\ValueObjects
 */

declare(strict_types=1);

namespace FAWpmcp\ValueObjects;

use FAWpmcp\Http\ResponseFormatter;

/**
 * Immutable Result container (Success or Failure).
 *
 * This class provides a functional way to handle operations that may fail,
 * allowing chaining of operations with map() and flatMap() without
 * throwing exceptions.
 *
 * @package FAWpmcp\ValueObjects
 */
final readonly class Result {
    /**
     * Constructor.
     *
     * @param bool        $is_success     Whether the result is successful.
     * @param mixed       $value          The success value (null on failure).
     * @param string|null $error_code     The error code (null on success).
     * @param string|null $error_message  The error message (null on success).
     */
    private function __construct(
        public bool $is_success,
        public mixed $value,
        public ?string $error_code,
        public ?string $error_message,
    ) {
    }

    /**
     * Create a success result.
     *
     * @param mixed $value The success value.
     * @return self
     */
    public static function success( mixed $value ): self {
        return new self( true, $value, null, null );
    }

    /**
     * Create a failure result.
     *
     * @param string $code    The error code.
     * @param string $message The error message.
     * @return self
     */
    public static function failure( string $code, string $message ): self {
        return new self( false, null, $code, $message );
    }

    /**
     * Transform the success value if result is successful.
     *
     * If the result is a failure, the failure is preserved.
     *
     * @param callable $fn Transformation function: (mixed) => mixed.
     * @return self
     */
    public function map( callable $fn ): self {
        return $this->is_success
            ? self::success( $fn( $this->value ) )
            : $this;
    }

    /**
     * Chain operations that return Results.
     *
     * If the result is a failure, the failure is preserved.
     *
     * @param callable $fn Chaining function: (mixed) => Result.
     * @return self
     */
    public function flatMap( callable $fn ): self {
        return $this->is_success ? $fn( $this->value ) : $this;
    }

    /**
     * Convert result to API response format.
     *
     * Uses ResponseFormatter to create a consistent response structure.
     *
     * @param string $correlation_id    The correlation ID for request tracking.
     * @param int    $execution_time_ms The execution time in milliseconds.
     * @return array<string, mixed> The formatted response array.
     */
    public function toResponse( string $correlation_id, int $execution_time_ms ): array {
        if ( $this->is_success ) {
            // Ensure value is an array for ResponseFormatter.
            $data = is_array( $this->value ) ? $this->value : array();
            return ResponseFormatter::success( $data, $correlation_id, $execution_time_ms );
        }

        return ResponseFormatter::error(
            $this->error_code ?? 'internal_error',
            $this->error_message ?? 'An error occurred'
        );
    }
}
