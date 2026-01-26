<?php

/**
 * Tests for GetMaintenanceModeStatus.
 *
 * @package FAWpmcp\Tests\Abilities\Maintenance
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Maintenance;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Maintenance\GetMaintenanceModeStatus;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;

final class GetMaintenanceModeStatusTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Temporary directory for testing.
     *
     * @var string
     */
    private string $temp_dir;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a temporary directory for ABSPATH.
        $this->temp_dir = sys_get_temp_dir() . '/wp_test_' . uniqid();
        mkdir($this->temp_dir);

        // Define ABSPATH if not defined.
        if (! defined('ABSPATH')) {
            define('ABSPATH', $this->temp_dir . '/');
        }

        // Clean up any existing maintenance file from previous tests.
        $maintenance_file = ABSPATH . '.maintenance';
        if (file_exists($maintenance_file)) {
            unlink($maintenance_file);
        }
    }

    protected function tearDown(): void
    {
        // Clean up maintenance file if exists (use ABSPATH which may differ from temp_dir).
        $maintenance_file = ABSPATH . '.maintenance';
        if (file_exists($maintenance_file)) {
            unlink($maintenance_file);
        }

        // Also clean up temp_dir if it exists and is empty.
        if (is_dir($this->temp_dir) && count(scandir($this->temp_dir)) === 2) {
            rmdir($this->temp_dir);
        }

        parent::tearDown();
    }

    protected function getAbilityInstance(): AbstractAbility
    {
        return new GetMaintenanceModeStatus();
    }

    protected function getExpectedMetadata(): array
    {
        return array(
            'name'                  => 'fa-wpmcp/get-maintenance-mode-status',
            'category'              => 'maintenance',
            'label'                 => 'Get Maintenance Mode Status',
            'description_contains'  => 'maintenance',
            'operation_type'        => 'read',
            'required_capability'   => 'manage_options',
        );
    }


    public function test_input_schema_has_no_required_fields(): void
    {
        $ability = $this->getAbilityInstance();
        $schema  = $ability->getInputSchema();

        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayNotHasKey('required', $schema);
    }

    public function test_output_schema_structure(): void
    {
        $ability = $this->getAbilityInstance();
        $schema  = $ability->getOutputSchema();

        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('active', $schema['properties']);
        $this->assertArrayHasKey('activated_at', $schema['properties']);
        $this->assertArrayHasKey('duration', $schema['properties']);
        $this->assertArrayHasKey('message', $schema['properties']);
    }

    public function test_returns_inactive_when_no_maintenance_file(): void
    {
        $ability = $this->getAbilityInstance();
        $result  = $ability->doExecute(array());

        $this->assertFalse($result['active']);
        $this->assertEquals(0, $result['activated_at']);
        $this->assertEquals(0, $result['duration']);
        $this->assertStringContainsString('not active', $result['message']);
    }

    public function test_returns_active_when_maintenance_file_exists(): void
    {
        // Create maintenance file.
        $timestamp        = time() - 60; // 60 seconds ago.
        $maintenance_file = ABSPATH . '.maintenance';
        file_put_contents($maintenance_file, "<?php\n\$upgrading = {$timestamp};\n");

        $ability = $this->getAbilityInstance();
        $result  = $ability->doExecute(array());

        $this->assertTrue($result['active']);
        $this->assertEquals($timestamp, $result['activated_at']);
        $this->assertGreaterThanOrEqual(60, $result['duration']);
        $this->assertStringContainsString('active since', $result['message']);
    }

    public function test_handles_malformed_maintenance_file(): void
    {
        // Create malformed maintenance file.
        $maintenance_file = ABSPATH . '.maintenance';
        file_put_contents($maintenance_file, "<?php\n// Invalid content\n");

        $ability = $this->getAbilityInstance();
        $result  = $ability->doExecute(array());

        $this->assertTrue($result['active']);
        $this->assertEquals(0, $result['activated_at']);
        $this->assertEquals(0, $result['duration']);
    }
}
