<?php
/**
 * HTTP response formatting utility.
 *
 * Provides static methods for formatting consistent API responses
 * in both success and error scenarios.
 *
 * @package FAWpmcp\Http
 */

declare(strict_types=1);

namespace FAWpmcp\Http;

/**
 * Response formatting utility for consistent API responses.
 *
 * All methods are pure functions - they do not modify state and
 * return deterministic results for the same inputs (except timestamp).
 */
final class ResponseFormatter {

	/**
	 * Format a successful response.
	 *
	 * @param array<string, mixed> $data              The response data.
	 * @param string               $correlation_id    The correlation ID for request tracking.
	 * @param int                  $execution_time_ms The execution time in milliseconds.
	 * @return array<string, mixed> The formatted success response.
	 */
	public static function success( array $data, string $correlation_id, int $execution_time_ms ): array {
		return array(
			'success' => true,
			'data'    => $data,
			'meta'    => array(
				'correlation_id'    => $correlation_id,
				'execution_time_ms' => $execution_time_ms,
				'timestamp'         => gmdate( 'c' ),
			),
		);
	}

	/**
	 * Format an error response.
	 *
	 * @param string            $code        The error code.
	 * @param string            $message     The error message.
	 * @param array<mixed>|null $details     Optional additional error details.
	 * @param int|null          $retry_after Optional retry-after value in seconds.
	 * @return array<string, mixed> The formatted error response.
	 */
	public static function error(
		string $code,
		string $message,
		?array $details = null,
		?int $retry_after = null
	): array {
		$error = array(
			'code'    => $code,
			'message' => $message,
		);

		if ( null !== $details ) {
			$error['details'] = $details;
		}

		$meta = array(
			'timestamp' => gmdate( 'c' ),
		);

		if ( null !== $retry_after ) {
			$meta['retry_after'] = $retry_after;
		}

		return array(
			'success' => false,
			'error'   => $error,
			'meta'    => $meta,
		);
	}

	/**
	 * Get the HTTP status code for an error code.
	 *
	 * Method name follows MCP protocol conventions (camelCase).
	 *
	 * @param string $code The error code.
	 * @return int The HTTP status code (defaults to 500 for unknown codes).
	 *
	 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
	 */
	public static function getHttpStatus( string $code ): int {
		// phpcs:enable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		return ErrorCodes::HTTP_STATUS_MAP[ $code ] ?? 500;
	}
}
