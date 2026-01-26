<?php
/**
 * Immutable log entry value object.
 *
 * @package FAWpmcp\ValueObjects
 */

declare(strict_types=1);

namespace FAWpmcp\ValueObjects;

/**
 * Immutable log entry representing an ability execution.
 *
 * Uses readonly properties (PHP 8.1+) to enforce immutability.
 * No methods - pure data container.
 *
 * @package FAWpmcp\ValueObjects
 */
final readonly class LogEntry {

	/**
	 * Constructor.
	 *
	 * @param string      $correlation_id    Unique correlation ID (UUID).
	 * @param int         $user_id           WordPress user ID.
	 * @param string      $user_login        WordPress user login.
	 * @param string      $ip_address        IP address of request.
	 * @param string      $ability_name      Ability name (e.g., 'fa-wpmcp/list-posts').
	 * @param string      $ability_category  Ability category (e.g., 'posts-pages').
	 * @param string      $operation_type    Operation type ('read' or 'write').
	 * @param array|null  $input_data        Input data (nullable).
	 * @param array|null  $output_data       Output data (nullable).
	 * @param bool        $success           Whether execution succeeded.
	 * @param string|null $error_message     Error message if failed (nullable).
	 * @param int         $execution_time_ms Execution time in milliseconds.
	 */
	public function __construct(
		public string $correlation_id,
		public int $user_id,
		public string $user_login,
		public string $ip_address,
		public string $ability_name,
		public string $ability_category,
		public string $operation_type,
		public ?array $input_data,
		public ?array $output_data,
		public bool $success,
		public ?string $error_message,
		public int $execution_time_ms,
	) {
	}
}
