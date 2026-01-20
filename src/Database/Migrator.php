<?php
/**
 * Database migration logic (pure functions).
 *
 * @package FAWpmcp\Database
 */

declare(strict_types=1);

namespace FAWpmcp\Database;

/**
 * Pure functions for database migrations.
 *
 * All methods are static and pure - they determine migration logic
 * without side effects. Actual migration execution happens elsewhere.
 *
 * @package FAWpmcp\Database
 */
final class Migrator {
	/**
	 * Determine if migration is needed.
	 *
	 * Pure function: compares version strings.
	 *
	 * @param string $current_version Current database version.
	 * @param string $target_version  Target database version.
	 * @return bool True if migration is needed.
	 */
	public static function should_migrate( string $current_version, string $target_version ): bool {
		return version_compare( $current_version, $target_version, '<' );
	}

	/**
	 * Get migrations between versions.
	 *
	 * Pure function: filters migrations by version range.
	 *
	 * @param string $from_version Starting version (exclusive).
	 * @param string $to_version   Target version (inclusive).
	 * @return array<int, array<string, string>> Array of migrations to run.
	 */
	public static function get_migrations( string $from_version, string $to_version ): array {
		$all_migrations = self::get_all_migrations();

		return array_values(
			array_filter(
				$all_migrations,
				function ( array $migration ) use ( $from_version, $to_version ): bool {
					return version_compare( $from_version, $migration['version'], '<' )
						&& version_compare( $migration['version'], $to_version, '<=' );
				}
			)
		);
	}

	/**
	 * Get all available migrations.
	 *
	 * Pure function: returns complete migration list.
	 *
	 * @return array<int, array<string, string>> Array of all migrations.
	 */
	public static function get_all_migrations(): array {
		return array(
			array(
				'version'  => '1.0.0',
				'callback' => 'migrate_1_0_0',
			),
		);
	}
}
