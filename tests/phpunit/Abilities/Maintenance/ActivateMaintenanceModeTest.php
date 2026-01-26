<?php

/**
 * Tests for ActivateMaintenanceMode.
 *
 * @package FAWpmcp\Tests\Abilities\Maintenance
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Maintenance;

use FAWpmcp\Abilities\Maintenance\ActivateMaintenanceMode;
use FAWpmcp\Exceptions\MaintenanceModeException;
use PHPUnit\Framework\TestCase;

final class ActivateMaintenanceModeTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * Temporary directory for testing.
     *
     * @var string
     */
    private string $temp_dir;

    protected function setUp(): void
    {
        parent::setUp();
        \Brain\Monkey\setUp();

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

        \Brain\Monkey\tearDown();
        parent::tearDown();
    }

    public function test_ability_metadata(): void
    {
        $ability = new ActivateMaintenanceMode();
        $this->assertEquals('fa-wpmcp/activate-maintenance-mode', $ability->getName());
        $this->assertEquals('maintenance', $ability->getCategory());
        $this->assertEquals('Activate Maintenance Mode', $ability->getLabel());
        $this->assertStringContainsString('activate', strtolower($ability->getDescription()));
        $this->assertEquals('manage_options', $ability->getRequiredCapability());
    }

    public function test_operation_type_is_write(): void
    {
        $ability = new ActivateMaintenanceMode();
        $this->assertEquals('write', $ability->getOperationType());
    }

    public function test_annotations_mark_idempotent(): void
    {
        $ability     = new ActivateMaintenanceMode();
        $annotations = $ability->getAnnotations();

        $this->assertTrue($annotations['idempotent']);
    }

    public function test_input_schema_has_expire_seconds(): void
    {
        $ability = new ActivateMaintenanceMode();
        $schema  = $ability->getInputSchema();

        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('expire_seconds', $schema['properties']);
        $this->assertEquals('integer', $schema['properties']['expire_seconds']['type']);
        $this->assertEquals(600, $schema['properties']['expire_seconds']['default']);
        $this->assertEquals(3600, $schema['properties']['expire_seconds']['maximum']);
    }

    public function test_output_schema_structure(): void
    {
        $ability = new ActivateMaintenanceMode();
        $schema  = $ability->getOutputSchema();

        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('activated', $schema['properties']);
        $this->assertArrayHasKey('activated_at', $schema['properties']);
        $this->assertArrayHasKey('expire_seconds', $schema['properties']);
        $this->assertArrayHasKey('message', $schema['properties']);
    }

    public function test_activates_maintenance_mode_with_default_expiration(): void
    {
        $ability = new ActivateMaintenanceMode();
        $before  = time();
        $result  = $ability->doExecute(array());
        $after   = time();

        $this->assertTrue($result['activated']);
        $this->assertGreaterThanOrEqual($before, $result['activated_at']);
        $this->assertLessThanOrEqual($after, $result['activated_at']);
        $this->assertEquals(600, $result['expire_seconds']);
        $this->assertStringContainsString('activated', $result['message']);
        $this->assertStringContainsString('auto-expire', $result['message']);

        // Verify file was created.
        $this->assertFileExists(ABSPATH . '.maintenance');
    }

    public function test_activates_maintenance_mode_with_custom_expiration(): void
    {
        $ability = new ActivateMaintenanceMode();
        $result  = $ability->doExecute(array( 'expire_seconds' => 1800 ));

        $this->assertTrue($result['activated']);
        $this->assertEquals(1800, $result['expire_seconds']);
        $this->assertStringContainsString('1800 seconds', $result['message']);
    }

    public function test_activates_maintenance_mode_without_expiration(): void
    {
        $ability = new ActivateMaintenanceMode();
        $result  = $ability->doExecute(array( 'expire_seconds' => 0 ));

        $this->assertTrue($result['activated']);
        $this->assertEquals(0, $result['expire_seconds']);
        $this->assertStringContainsString('No auto-expiration', $result['message']);
        $this->assertStringContainsString('deactivate manually', $result['message']);
    }

    public function test_creates_valid_maintenance_file_content(): void
    {
        $ability = new ActivateMaintenanceMode();
        $ability->doExecute(array());

        $content = file_get_contents(ABSPATH . '.maintenance');
        $this->assertStringContainsString('<?php', $content);
        $this->assertStringContainsString('$upgrading =', $content);
    }

    public function test_overwrites_existing_maintenance_file(): void
    {
        // Create existing maintenance file.
        $maintenance_file = ABSPATH . '.maintenance';
        file_put_contents($maintenance_file, "<?php\n\$upgrading = 12345;\n");

        $ability = new ActivateMaintenanceMode();
        $result  = $ability->doExecute(array());

        $this->assertTrue($result['activated']);
        $this->assertNotEquals(12345, $result['activated_at']);

        // Verify file was overwritten.
        $content = file_get_contents($maintenance_file);
        $this->assertStringNotContainsString('12345', $content);
    }
}
