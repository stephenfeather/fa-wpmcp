<?php

/**
 * Tests for UpdateDatabaseAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Core
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Core;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Core\UpdateDatabaseAbility;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;

/**
 * Test UpdateDatabaseAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Core
 */
class UpdateDatabaseAbilityTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get the ability instance to test.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new UpdateDatabaseAbility();
    }

    /**
     * Get the expected metadata for this ability.
     *
     * @return array<string, mixed>
     */
    protected function getExpectedMetadata(): array
    {
        return array(
            'name'                 => 'fa-wpmcp/update-database',
            'category'             => 'core',
            'label'                => 'Update Database',
            'description_contains' => 'database',
            'operation_type'       => 'write',
            'required_capability'  => 'manage_options',
        );
    }

    /**
     * Test ability returns input schema with dry_run/network options.
     *
     * @return void
     */
    public function testGetInputSchema(): void
    {
        $ability = $this->getAbilityInstance();
        $schema  = $ability->getInputSchema();

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('dry_run', $schema['properties']);
        $this->assertArrayHasKey('network', $schema['properties']);
        // Default should be true for safety.
        $this->assertTrue($schema['properties']['dry_run']['default']);
    }

    /**
     * Test ability returns output schema with update properties.
     *
     * @return void
     */
    public function testGetOutputSchema(): void
    {
        $ability = $this->getAbilityInstance();
        $schema  = $ability->getOutputSchema();

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('update_required', $schema['properties']);
        $this->assertArrayHasKey('dry_run', $schema['properties']);
        $this->assertArrayHasKey('current_db_version', $schema['properties']);
        $this->assertArrayHasKey('target_db_version', $schema['properties']);
        $this->assertArrayHasKey('updated', $schema['properties']);
    }

    /**
     * Test execute returns no update required when up to date.
     *
     * @return void
     */
    public function testExecuteReturnsNoUpdateRequiredWhenUpToDate(): void
    {
        $ability = $this->getAbilityInstance();

        Functions\when('get_option')->justReturn('58975');

        // Set global wp_db_version.
        $GLOBALS['wp_db_version'] = 58975;

        $result = $ability->doExecute(array());

        $this->assertFalse($result['update_required']);
        $this->assertFalse($result['updated']);
        $this->assertEquals('Database is already up to date.', $result['message']);
    }

    /**
     * Test execute returns dry run message when update required.
     *
     * @return void
     */
    public function testExecuteReturnsDryRunMessageWhenUpdateRequired(): void
    {
        $ability = $this->getAbilityInstance();

        Functions\when('get_option')->justReturn('58970');

        // Set global wp_db_version to a higher value.
        $GLOBALS['wp_db_version'] = 58975;

        $result = $ability->doExecute(array( 'dry_run' => true ));

        $this->assertTrue($result['update_required']);
        $this->assertTrue($result['dry_run']);
        $this->assertFalse($result['updated']);
        $this->assertStringContainsString('dry_run=false', $result['message']);
    }

    /**
     * Test execute performs update when dry_run is false.
     *
     * @return void
     */
    public function testExecutePerformsUpdateWhenNotDryRun(): void
    {
        $ability = $this->getAbilityInstance();

        Functions\when('get_option')->justReturn('58970');
        Functions\when('wp_upgrade')->justReturn(null);
        Functions\when('is_multisite')->justReturn(false);

        // Mock file inclusion - ABSPATH should already be defined in bootstrap.
        if (! defined('ABSPATH')) {
            define('ABSPATH', sys_get_temp_dir() . '/');
        }

        // Set global wp_db_version.
        $GLOBALS['wp_db_version'] = 58975;

        $result = $ability->doExecute(array( 'dry_run' => false ));

        $this->assertTrue($result['update_required']);
        $this->assertFalse($result['dry_run']);
        $this->assertTrue($result['updated']);
        $this->assertEquals(1, $result['sites_updated']);
        $this->assertStringContainsString('Database updated', $result['message']);
    }

    /**
     * Test execute updates network when multisite and network flag is true.
     *
     * @return void
     */
    public function testExecuteUpdatesNetworkWhenMultisite(): void
    {
        $ability = $this->getAbilityInstance();

        Functions\when('get_option')->justReturn('58970');
        Functions\when('wp_upgrade')->justReturn(null);
        Functions\when('is_multisite')->justReturn(true);

        // Mock get_sites to return 3 sites.
        Functions\when('get_sites')->justReturn(
            array(
                (object) array( 'blog_id' => 1 ),
                (object) array( 'blog_id' => 2 ),
                (object) array( 'blog_id' => 3 ),
            )
        );
        Functions\when('switch_to_blog')->justReturn(true);
        Functions\when('restore_current_blog')->justReturn(true);

        $GLOBALS['wp_db_version'] = 58975;

        $result = $ability->doExecute(
            array(
                'dry_run' => false,
                'network' => true,
            )
        );

        $this->assertTrue($result['updated']);
        $this->assertEquals(3, $result['sites_updated']);
        $this->assertStringContainsString('3 sites', $result['message']);
    }

    /**
     * Test operation type is write.
     *
     * @return void
     */
    public function testOperationTypeIsWrite(): void
    {
        $ability = new UpdateDatabaseAbility();

        $this->assertEquals('write', $ability->getOperationType());
    }

    /**
     * Test annotations indicate destructive operation.
     *
     * @return void
     */
    public function testGetAnnotationsIndicatesDestructive(): void
    {
        $ability     = new UpdateDatabaseAbility();
        $annotations = $ability->getAnnotations();

        $this->assertFalse($annotations['readonly']);
        $this->assertTrue($annotations['destructive']);
        $this->assertTrue($annotations['idempotent']);
    }

    /**
     * Test default dry_run is true for safety.
     *
     * @return void
     */
    public function testDefaultDryRunIsTrue(): void
    {
        $ability = $this->getAbilityInstance();

        Functions\when('get_option')->justReturn('58970');
        $GLOBALS['wp_db_version'] = 58975;

        // Execute without specifying dry_run - should default to true.
        $result = $ability->doExecute(array());

        $this->assertTrue($result['dry_run']);
        $this->assertFalse($result['updated']);
    }
}
