<?php

/**
 * Test database migration logic.
 *
 * @package FAWpmcp\Tests\Database
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Database;

use FAWpmcp\Database\Migrator;
use PHPUnit\Framework\TestCase;

/**
 * Test database migration logic.
 */
class MigratorTest extends TestCase {

	/**
	 * Test should_migrate returns true when version is lower.
	 */
	public function test_should_migrate_returns_true_when_version_lower(): void {
		$result = Migrator::shouldMigrate( '0.9.0', '1.0.0' );
		$this->assertTrue( $result );
	}

	/**
	 * Test should_migrate returns false when version is equal.
	 */
	public function test_should_migrate_returns_false_when_version_equal(): void {
		$result = Migrator::shouldMigrate( '1.0.0', '1.0.0' );
		$this->assertFalse( $result );
	}

	/**
	 * Test should_migrate returns false when version is higher.
	 */
	public function test_should_migrate_returns_false_when_version_higher(): void {
		$result = Migrator::shouldMigrate( '1.1.0', '1.0.0' );
		$this->assertFalse( $result );
	}

	/**
	 * Test get_migrations returns array.
	 */
	public function test_get_migrations_returns_array(): void {
		$migrations = Migrator::getMigrations( '0.9.0', '1.1.0' );

		$this->assertIsArray( $migrations );
	}

	/**
	 * Test get_migrations filters by version range.
	 */
	public function test_get_migrations_filters_by_version_range(): void {
		$migrations = Migrator::getMigrations( '0.9.0', '1.0.0' );

		// Should only include migrations between 0.9.0 and 1.0.0.
		foreach ( $migrations as $migration ) {
			$this->assertIsArray( $migration );
			$this->assertArrayHasKey( 'version', $migration );
			$this->assertArrayHasKey( 'callback', $migration );
		}
	}

	/**
	 * Test get_migrations returns empty array when no migrations needed.
	 */
	public function test_get_migrations_returns_empty_when_no_migrations_needed(): void {
		$migrations = Migrator::getMigrations( '2.0.0', '2.0.0' );

		$this->assertIsArray( $migrations );
		$this->assertEmpty( $migrations );
	}

	/**
	 * Test get_migrations is deterministic (pure function).
	 */
	public function test_get_migrations_is_deterministic(): void {
		$migrations1 = Migrator::getMigrations( '0.9.0', '1.1.0' );
		$migrations2 = Migrator::getMigrations( '0.9.0', '1.1.0' );

		$this->assertSame( $migrations1, $migrations2, 'get_migrations should be deterministic' );
	}

	/**
	 * Test get_all_migrations returns array.
	 */
	public function test_get_all_migrations_returns_array(): void {
		$migrations = Migrator::getAllMigrations();

		$this->assertIsArray( $migrations );
		$this->assertNotEmpty( $migrations );
	}

	/**
	 * Test migrations are ordered by version.
	 */
	public function test_migrations_are_ordered_by_version(): void {
		$migrations = Migrator::getAllMigrations();

		$versions        = array_column( $migrations, 'version' );
		$sorted_versions = $versions;
		usort( $sorted_versions, 'version_compare' );

		$this->assertSame( $sorted_versions, $versions, 'Migrations should be ordered by version' );
	}
}
