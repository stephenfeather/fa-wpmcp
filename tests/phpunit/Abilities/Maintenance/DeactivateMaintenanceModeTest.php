<?php

/**
 * Tests for DeactivateMaintenanceMode.
 *
 * @package FAWpmcp\Tests\Abilities\Maintenance
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Maintenance;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Maintenance\DeactivateMaintenanceMode;
use FAWpmcp\Exceptions\MaintenanceModeException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;

final class DeactivateMaintenanceModeTest extends BrainMonkeyTestCase {

	use AbilityTestTrait;

	/**
	 * Temporary directory for testing.
	 *
	 * @var string
	 */
	private string $temp_dir;

	protected function setUp(): void {
		parent::setUp();

		// Create a temporary directory for ABSPATH.
		$this->temp_dir = sys_get_temp_dir() . '/wp_test_' . uniqid();
		mkdir( $this->temp_dir );

		// Define ABSPATH if not defined.
		if ( ! defined( 'ABSPATH' ) ) {
			define( 'ABSPATH', $this->temp_dir . '/' );
		}

		// Clean up any existing maintenance file from previous tests.
		$maintenance_file = ABSPATH . '.maintenance';
		if ( file_exists( $maintenance_file ) ) {
			unlink( $maintenance_file );
		}
	}

	protected function tearDown(): void {
		// Clean up maintenance file if exists (use ABSPATH which may differ from temp_dir).
		$maintenance_file = ABSPATH . '.maintenance';
		if ( file_exists( $maintenance_file ) ) {
			unlink( $maintenance_file );
		}

		// Also clean up temp_dir if it exists and is empty.
		if ( is_dir( $this->temp_dir ) && count( scandir( $this->temp_dir ) ) === 2 ) {
			rmdir( $this->temp_dir );
		}

		parent::tearDown();
	}

	protected function getAbilityInstance(): AbstractAbility {
		return new DeactivateMaintenanceMode();
	}

	protected function getExpectedMetadata(): array {
		return array(
			'name'                  => 'fa-wpmcp/deactivate-maintenance-mode',
			'category'              => 'maintenance',
			'label'                 => 'Deactivate Maintenance Mode',
			'description_contains'  => 'deactivate',
			'operation_type'        => 'write',
			'required_capability'   => 'manage_options',
		);
	}

	public function test_annotations_mark_idempotent(): void {
		$ability     = $this->getAbilityInstance();
		$annotations = $ability->getAnnotations();

		$this->assertTrue( $annotations['idempotent'] );
	}

	public function test_input_schema_has_no_required_fields(): void {
		$ability = $this->getAbilityInstance();
		$schema  = $ability->getInputSchema();

		$this->assertEquals( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertArrayNotHasKey( 'required', $schema );
	}

	public function test_output_schema_structure(): void {
		$ability = $this->getAbilityInstance();
		$schema  = $ability->getOutputSchema();

		$this->assertEquals( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'deactivated', $schema['properties'] );
		$this->assertArrayHasKey( 'was_active', $schema['properties'] );
		$this->assertArrayHasKey( 'message', $schema['properties'] );
	}

	public function test_returns_success_when_not_active(): void {
		$ability = $this->getAbilityInstance();
		$result  = $ability->doExecute( array() );

		$this->assertTrue( $result['deactivated'] );
		$this->assertFalse( $result['was_active'] );
		$this->assertStringContainsString( 'was not active', $result['message'] );
	}

	public function test_deactivates_maintenance_mode(): void {
		// Create maintenance file.
		$maintenance_file = ABSPATH . '.maintenance';
		file_put_contents( $maintenance_file, "<?php\n\$upgrading = " . time() . ";\n" );
		$this->assertFileExists( $maintenance_file );

		$ability = $this->getAbilityInstance();
		$result  = $ability->doExecute( array() );

		$this->assertTrue( $result['deactivated'] );
		$this->assertTrue( $result['was_active'] );
		$this->assertStringContainsString( 'deactivated', $result['message'] );
		$this->assertStringContainsString( 'accessible', $result['message'] );

		// Verify file was removed.
		$this->assertFileDoesNotExist( $maintenance_file );
	}

	public function test_idempotent_multiple_calls(): void {
		// Create maintenance file.
		$maintenance_file = ABSPATH . '.maintenance';
		file_put_contents( $maintenance_file, "<?php\n\$upgrading = " . time() . ";\n" );

		$ability = $this->getAbilityInstance();

		// First call - should deactivate.
		$result1 = $ability->doExecute( array() );
		$this->assertTrue( $result1['deactivated'] );
		$this->assertTrue( $result1['was_active'] );

		// Second call - should succeed but report not active.
		$result2 = $ability->doExecute( array() );
		$this->assertTrue( $result2['deactivated'] );
		$this->assertFalse( $result2['was_active'] );
	}
}
