<?php
/**
 * File-based MCP error handler.
 *
 * @package FAWpmcp\Logging
 */

declare(strict_types=1);

namespace FAWpmcp\Logging;

use WP\MCP\Infrastructure\ErrorHandling\Contracts\McpErrorHandlerInterface;

/**
 * MCP error handler that logs to a file in wp-content.
 *
 * Logs MCP errors to wp-content/mcp-errors.log with timestamps,
 * log types, and JSON-encoded context data. Can be enabled/disabled
 * via plugin settings.
 *
 * @package FAWpmcp\Logging
 */
final class FileErrorHandler implements McpErrorHandlerInterface {
	/**
	 * Settings option name.
	 *
	 * @var string
	 */
	private const SETTINGS_OPTION = 'fa_wpmcp_settings';

	/**
	 * Settings key for enabling file logging.
	 *
	 * @var string
	 */
	private const SETTING_KEY = 'file_error_logging_enabled';

	/**
	 * Log file name.
	 *
	 * @var string
	 */
	private const LOG_FILE = 'mcp-errors.log';

	/**
	 * Whether file error logging is enabled.
	 *
	 * @var bool|null Cached enabled state, null if not yet loaded.
	 */
	private ?bool $enabled = null;

	/**
	 * Log file path (injectable for testing).
	 *
	 * @var string|null
	 */
	private ?string $log_path = null;

	/**
	 * Constructor.
	 *
	 * @param string|null $log_path Optional log file path for testing.
	 */
	public function __construct( ?string $log_path = null ) {
		$this->log_path = $log_path;
	}

	/**
	 * Log an error message with optional context and type.
	 *
	 * Writes to wp-content/mcp-errors.log if file logging is enabled.
	 * Each entry includes timestamp, log type, message, and JSON context.
	 *
	 * @param string $message The log message.
	 * @param array  $context Additional context data.
	 * @param string $type    The log type (e.g., 'error', 'info', 'debug').
	 *
	 * @return void
	 */
	public function log( string $message, array $context = array(), string $type = 'error' ): void {
		if ( ! $this->isEnabled() ) {
			return;
		}

		$log_entry = $this->formatLogEntry( $message, $context, $type );
		$log_file  = $this->getLogFilePath();

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $log_file, $log_entry . "\n", FILE_APPEND | LOCK_EX );
	}

	/**
	 * Check if file error logging is enabled.
	 *
	 * @return bool True if enabled, false otherwise.
	 */
	public function isEnabled(): bool {
		if ( null === $this->enabled ) {
			$this->enabled = $this->loadEnabledSetting();
		}

		return $this->enabled;
	}

	/**
	 * Get the log file path.
	 *
	 * @return string Full path to the log file.
	 */
	public function getLogFilePath(): string {
		if ( null !== $this->log_path ) {
			return $this->log_path;
		}

		return ( defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR : '' ) . '/' . self::LOG_FILE;
	}

	/**
	 * Load the enabled setting from WordPress options.
	 *
	 * @return bool True if file logging is enabled.
	 */
	private function loadEnabledSetting(): bool {
		if ( ! function_exists( 'get_option' ) ) {
			return false;
		}

		$settings = get_option( self::SETTINGS_OPTION, array() );

		if ( ! is_array( $settings ) ) {
			return false;
		}

		return ! empty( $settings[ self::SETTING_KEY ] );
	}

	/**
	 * Format a log entry string.
	 *
	 * @param string $message The log message.
	 * @param array  $context Additional context data.
	 * @param string $type    The log type.
	 *
	 * @return string Formatted log entry.
	 */
	private function formatLogEntry( string $message, array $context, string $type ): string {
		$timestamp = gmdate( 'Y-m-d H:i:s' );
		$json_context = function_exists( 'wp_json_encode' )
			? wp_json_encode( $context )
			: json_encode( $context );

		return sprintf(
			'[%s] [%s] %s | Context: %s',
			$timestamp,
			strtoupper( $type ),
			$message,
			$json_context
		);
	}
}
