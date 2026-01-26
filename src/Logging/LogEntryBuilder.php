<?php

/**
 * Immutable builder for LogEntry.
 *
 * @package FAWpmcp\Logging
 */

declare(strict_types=1);

namespace FAWpmcp\Logging;

use FAWpmcp\ValueObjects\LogEntry;

/**
 * Immutable builder for constructing LogEntry instances.
 *
 * Each method returns a new builder instance (immutability).
 * Follows the builder pattern for complex object construction.
 *
 * @package FAWpmcp\Logging
 */
final class LogEntryBuilder {

	/**
	 * Constructor (private - use create()).
	 *
	 * @param string|null $correlation_id    Correlation ID.
	 * @param int|null    $user_id           User ID.
	 * @param string|null $user_login        User login.
	 * @param string|null $ip_address        IP address.
	 * @param string|null $ability_name      Ability name.
	 * @param string|null $ability_category  Ability category.
	 * @param string|null $operation_type    Operation type.
	 * @param array|null  $input_data        Input data.
	 * @param array|null  $output_data       Output data.
	 * @param bool|null   $success           Success flag.
	 * @param string|null $error_message     Error message.
	 * @param int|null    $execution_time_ms Execution time in ms.
	 */
	private function __construct(
		private readonly ?string $correlation_id = null,
		private readonly ?int $user_id = null,
		private readonly ?string $user_login = null,
		private readonly ?string $ip_address = null,
		private readonly ?string $ability_name = null,
		private readonly ?string $ability_category = null,
		private readonly ?string $operation_type = null,
		private readonly ?array $input_data = null,
		private readonly ?array $output_data = null,
		private readonly ?bool $success = null,
		private readonly ?string $error_message = null,
		private readonly ?int $execution_time_ms = null,
	) {
	}

	/**
	 * Create new builder instance.
	 *
	 * @return self
	 */
	public static function create(): self {
		return new self();
	}

	/**
	 * Set correlation ID.
	 *
	 * @param string $id Correlation ID.
	 * @return self New builder instance.
	 */
	public function withCorrelationId( string $id ): self {
		return new self(
			$id,
			$this->user_id,
			$this->user_login,
			$this->ip_address,
			$this->ability_name,
			$this->ability_category,
			$this->operation_type,
			$this->input_data,
			$this->output_data,
			$this->success,
			$this->error_message,
			$this->execution_time_ms,
		);
	}

	/**
	 * Set user information.
	 *
	 * @param int    $id    User ID.
	 * @param string $login User login.
	 * @return self New builder instance.
	 */
	public function withUser( int $id, string $login ): self {
		return new self(
			$this->correlation_id,
			$id,
			$login,
			$this->ip_address,
			$this->ability_name,
			$this->ability_category,
			$this->operation_type,
			$this->input_data,
			$this->output_data,
			$this->success,
			$this->error_message,
			$this->execution_time_ms,
		);
	}

	/**
	 * Set IP address.
	 *
	 * @param string $ip IP address.
	 * @return self New builder instance.
	 */
	public function withIpAddress( string $ip ): self {
		return new self(
			$this->correlation_id,
			$this->user_id,
			$this->user_login,
			$ip,
			$this->ability_name,
			$this->ability_category,
			$this->operation_type,
			$this->input_data,
			$this->output_data,
			$this->success,
			$this->error_message,
			$this->execution_time_ms,
		);
	}

	/**
	 * Set ability information.
	 *
	 * @param string $name      Ability name.
	 * @param string $category  Ability category.
	 * @param string $operation Operation type ('read' or 'write').
	 * @return self New builder instance.
	 */
	public function withAbility( string $name, string $category, string $operation ): self {
		return new self(
			$this->correlation_id,
			$this->user_id,
			$this->user_login,
			$this->ip_address,
			$name,
			$category,
			$operation,
			$this->input_data,
			$this->output_data,
			$this->success,
			$this->error_message,
			$this->execution_time_ms,
		);
	}

	/**
	 * Set input data.
	 *
	 * @param array|null $input Input data.
	 * @return self New builder instance.
	 */
	public function withInput( ?array $input ): self {
		return new self(
			$this->correlation_id,
			$this->user_id,
			$this->user_login,
			$this->ip_address,
			$this->ability_name,
			$this->ability_category,
			$this->operation_type,
			$input,
			$this->output_data,
			$this->success,
			$this->error_message,
			$this->execution_time_ms,
		);
	}

	/**
	 * Set output data.
	 *
	 * @param array|null $output Output data.
	 * @return self New builder instance.
	 */
	public function withOutput( ?array $output ): self {
		return new self(
			$this->correlation_id,
			$this->user_id,
			$this->user_login,
			$this->ip_address,
			$this->ability_name,
			$this->ability_category,
			$this->operation_type,
			$this->input_data,
			$output,
			$this->success,
			$this->error_message,
			$this->execution_time_ms,
		);
	}

	/**
	 * Set success flag.
	 *
	 * @param bool $success Success flag.
	 * @return self New builder instance.
	 */
	public function withSuccess( bool $success ): self {
		return new self(
			$this->correlation_id,
			$this->user_id,
			$this->user_login,
			$this->ip_address,
			$this->ability_name,
			$this->ability_category,
			$this->operation_type,
			$this->input_data,
			$this->output_data,
			$success,
			$this->error_message,
			$this->execution_time_ms,
		);
	}

	/**
	 * Set error message.
	 *
	 * @param string|null $error Error message.
	 * @return self New builder instance.
	 */
	public function withError( ?string $error ): self {
		return new self(
			$this->correlation_id,
			$this->user_id,
			$this->user_login,
			$this->ip_address,
			$this->ability_name,
			$this->ability_category,
			$this->operation_type,
			$this->input_data,
			$this->output_data,
			$this->success,
			$error,
			$this->execution_time_ms,
		);
	}

	/**
	 * Set execution time.
	 *
	 * @param int $time_ms Execution time in milliseconds.
	 * @return self New builder instance.
	 */
	public function withExecutionTime( int $time_ms ): self {
		return new self(
			$this->correlation_id,
			$this->user_id,
			$this->user_login,
			$this->ip_address,
			$this->ability_name,
			$this->ability_category,
			$this->operation_type,
			$this->input_data,
			$this->output_data,
			$this->success,
			$this->error_message,
			$time_ms,
		);
	}

	/**
	 * Build LogEntry instance.
	 *
	 * Provides defaults for missing fields.
	 *
	 * @return LogEntry
	 */
	public function build(): LogEntry {
		return new LogEntry(
			correlation_id: $this->correlation_id ?? '',
			user_id: $this->user_id ?? 0,
			user_login: $this->user_login ?? '',
			ip_address: $this->ip_address ?? '',
			ability_name: $this->ability_name ?? '',
			ability_category: $this->ability_category ?? '',
			operation_type: $this->operation_type ?? 'read',
			input_data: $this->input_data,
			output_data: $this->output_data,
			success: $this->success ?? false,
			error_message: $this->error_message,
			execution_time_ms: $this->execution_time_ms ?? 0,
		);
	}
}
