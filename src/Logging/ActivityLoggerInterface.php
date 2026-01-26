<?php
/**
 * Activity logger interface.
 *
 * @package FAWpmcp\Logging
 */

declare(strict_types=1);

namespace FAWpmcp\Logging;

/**
 * Interface for activity logging orchestration.
 *
 * Enables dependency injection and testing of ability execution logging.
 *
 * @package FAWpmcp\Logging
 */
interface ActivityLoggerInterface {
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
	): string;

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
	): void;
}
