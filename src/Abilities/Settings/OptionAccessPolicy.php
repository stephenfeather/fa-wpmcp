<?php
/**
 * OptionAccessPolicy - enforces access rules for WordPress options.
 *
 * @package FAWpmcp\Abilities\Settings
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Settings;

use FAWpmcp\Exceptions\OptionException;

/**
 * Policy class for validating option access permissions.
 *
 * Provides allowlist/blocklist functionality to control which WordPress
 * options can be accessed through the MCP server. Protects sensitive
 * options like security keys and core WordPress settings.
 *
 * @package FAWpmcp\Abilities\Settings
 */
final class OptionAccessPolicy {
	/**
	 * Default protected options.
	 *
	 * @var string[]
	 */
	private const DEFAULT_PROTECTED_OPTIONS = array(
		'admin_email',
		'active_plugins',
		'home',
		'siteurl',
		'stylesheet',
		'template',
		'users_can_register',
		'default_role',
		'db_version',
		'cron',
		'auth_key',
		'secure_auth_key',
		'logged_in_key',
		'nonce_key',
		'auth_salt',
		'secure_auth_salt',
		'logged_in_salt',
		'nonce_salt',
	);

	/**
	 * Assert an option is allowed to be accessed.
	 *
	 * @param string $option_name Option name.
	 * @return void
	 * @throws OptionException If the option is protected.
	 */
	public static function assertAllowed( string $option_name ): void {
		if ( ! self::isAllowed( $option_name ) ) {
			throw new OptionException( 'This option is protected and cannot be accessed.' );
		}
	}

	/**
	 * Check if an option is allowed.
	 *
	 * @param string $option_name Option name.
	 * @return bool True if allowed.
	 */
	public static function isAllowed( string $option_name ): bool {
		$option_name = self::normalizeOptionName( $option_name );

		$allowed = self::getAllowedOptions();
		if ( ! empty( $allowed ) ) {
			return in_array( $option_name, $allowed, true );
		}

		$protected = self::getProtectedOptions();
		return ! in_array( $option_name, $protected, true );
	}

	/**
	 * Get allowlist of options (if provided).
	 *
	 * @return string[] Allowed options.
	 */
	public static function getAllowedOptions(): array {
		$allowed = array();
		if ( function_exists( 'apply_filters' ) ) {
			$allowed = apply_filters( 'fa_wpmcp_allowed_options', $allowed );
		}

		return self::normalizeList( $allowed );
	}

	/**
	 * Get protected options list.
	 *
	 * @return string[] Protected options.
	 */
	public static function getProtectedOptions(): array {
		$protected = self::DEFAULT_PROTECTED_OPTIONS;
		if ( function_exists( 'apply_filters' ) ) {
			$protected = apply_filters( 'fa_wpmcp_protected_options', $protected );
		}

		return self::normalizeList( $protected );
	}

	/**
	 * Normalize a list of option names.
	 *
	 * @param mixed $options List of option names.
	 * @return string[] Normalized list.
	 */
	private static function normalizeList( $options ): array {
		if ( ! is_array( $options ) ) {
			return array();
		}

		$normalized = array();
		foreach ( $options as $option ) {
			if ( ! is_string( $option ) ) {
				continue;
			}
			$option = self::normalizeOptionName( $option );
			if ( '' !== $option ) {
				$normalized[ $option ] = true;
			}
		}

		return array_keys( $normalized );
	}

	/**
	 * Normalize option name for comparisons.
	 *
	 * @param string $option_name Option name.
	 * @return string Normalized option name.
	 */
	private static function normalizeOptionName( string $option_name ): string {
		$option_name = trim( $option_name );
		if ( '' === $option_name ) {
			return '';
		}

		if ( function_exists( 'sanitize_key' ) ) {
			return sanitize_key( $option_name );
		}

		return strtolower( $option_name );
	}
}
