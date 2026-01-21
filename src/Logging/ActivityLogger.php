<?php
/**
 * Activity logger orchestration.
 *
 * @package FAWpmcp\Logging
 */

declare(strict_types=1);

namespace FAWpmcp\Logging;

use FAWpmcp\Http\PrivacyRedactor;

/**
 * Orchestrates activity logging for ability executions.
 *
 * Coordinates between LogEntryBuilder and LogRepository.
 * Handles correlation ID generation and timing.
 *
 * @package FAWpmcp\Logging
 */
final class ActivityLogger implements ActivityLoggerInterface {
    /**
     * UUID generator callable.
     *
     * @var callable():string
     */
    private $uuid_generator;

    /**
     * Constructor.
     *
     * @param LogRepository $repository     Log repository for database operations.
     * @param callable      $uuid_generator UUID generator function.
     */
    public function __construct(
        private readonly LogRepository $repository,
        callable $uuid_generator
    ) {
        $this->uuid_generator = $uuid_generator;
    }

    /**
     * Log before ability execution.
     *
     * Creates initial log entry with input data.
     * Returns correlation ID for tracking.
     *
     * @param string     $ability_name     Ability name.
     * @param string     $ability_category Ability category.
     * @param string     $operation_type   Operation type ('read' or 'write').
     * @param int        $user_id          User ID.
     * @param string     $user_login       User login.
     * @param string     $ip_address       IP address.
     * @param array|null $input            Input data.
     * @return string Correlation ID.
     */
    public function logBeforeExecute(
        string $ability_name,
        string $ability_category,
        string $operation_type,
        int $user_id,
        string $user_login,
        string $ip_address,
        ?array $input = null
    ): string {
        $correlation_id = ( $this->uuid_generator )();

        // Redact sensitive fields from input before logging (pure function).
        $redacted_input = null !== $input ? PrivacyRedactor::redact( $input ) : null;

        $entry = LogEntryBuilder::create()
            ->withCorrelationId( $correlation_id )
            ->withUser( $user_id, $user_login )
            ->withIpAddress( $ip_address )
            ->withAbility( $ability_name, $ability_category, $operation_type )
            ->withInput( $redacted_input )
            ->build();

        // Side effect: database write.
        $this->repository->insert( $entry );

        return $correlation_id;
    }

    /**
     * Log after ability execution.
     *
     * Updates existing log entry with output data and result.
     * Calculates execution time from start time.
     *
     * @param string      $correlation_id Correlation ID from logBeforeExecute.
     * @param array|null  $output         Output data.
     * @param bool        $success        Whether execution succeeded.
     * @param string|null $error_message  Error message if failed.
     * @param float       $start_time     Start time from microtime(true).
     * @return void
     */
    public function logAfterExecute(
        string $correlation_id,
        ?array $output,
        bool $success,
        ?string $error_message = null,
        float $start_time = 0.0
    ): void {
        $execution_time_ms = $this->calculateExecutionTime( $start_time );

        // Redact sensitive fields from output before logging (pure function).
        $redacted_output = null !== $output ? PrivacyRedactor::redact( $output ) : null;

        $update_data = array(
            'output_data'       => $redacted_output,
            'success'           => $success,
            'error_message'     => $error_message,
            'execution_time_ms' => $execution_time_ms,
        );

        // Side effect: database write.
        $this->repository->updateByCorrelationId( $correlation_id, $update_data );
    }

    /**
     * Calculate execution time in milliseconds.
     *
     * Pure function: calculates time difference.
     *
     * @param float $start_time Start time from microtime(true).
     * @return int Execution time in milliseconds.
     */
    private function calculateExecutionTime( float $start_time ): int {
        if ( 0.0 === $start_time ) {
            return 0;
        }

        $end_time = microtime( true );
        $diff     = $end_time - $start_time;

        return (int) round( $diff * 1000 ); // Convert to milliseconds.
    }
}
