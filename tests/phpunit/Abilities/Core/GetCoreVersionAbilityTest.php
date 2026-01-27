<?php

/**
 * Tests for GetCoreVersionAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Core
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Core;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Core\GetCoreVersionAbility;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test GetCoreVersionAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Core
 */
class GetCoreVersionAbilityTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get the ability instance to test.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new GetCoreVersionAbility();
    }

    /**
     * Get the expected metadata for this ability.
     *
     * @return array<string, mixed>
     */
    protected function getExpectedMetadata(): array
    {
        return array(
            'name'                 => 'fa-wpmcp/get-core-version',
            'category'             => 'core',
            'label'                => 'Get Core Version',
            'description_contains' => 'version',
            'operation_type'       => 'read',
            'required_capability'  => 'manage_options',
        );
    }

    /**
     * Test ability returns input schema with extra property.
     *
     * @return void
     */
    public function testGetInputSchema(): void
    {
        $ability = $this->getAbilityInstance();
        $schema  = $ability->getInputSchema();

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('type', $schema);
        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('extra', $schema['properties']);
    }

    /**
     * Test ability returns output schema with version properties.
     *
     * @return void
     */
    public function testGetOutputSchema(): void
    {
        $ability = $this->getAbilityInstance();
        $schema  = $ability->getOutputSchema();

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('wordpress_version', $schema['properties']);
        $this->assertArrayHasKey('php_version', $schema['properties']);
        $this->assertArrayHasKey('mysql_version', $schema['properties']);
        $this->assertArrayHasKey('is_multisite', $schema['properties']);
    }

    /**
     * Test execute returns version information.
     *
     * @return void
     */
    public function testExecuteReturnsVersionInfo(): void
    {
        $ability = $this->getAbilityInstance();

        // Mock WordPress functions.
        Functions\when('get_bloginfo')->justReturn('6.9.1');
        Functions\when('is_multisite')->justReturn(false);

        // Mock wpdb.
        $mock_wpdb = Mockery::mock('wpdb');
        $mock_wpdb->shouldReceive('db_version')->andReturn('8.0.32');
        $GLOBALS['wpdb'] = $mock_wpdb;

        // Define constants if not defined.
        if (! defined('DB_CHARSET')) {
            define('DB_CHARSET', 'utf8mb4');
        }
        if (! defined('DB_COLLATE')) {
            define('DB_COLLATE', 'utf8mb4_unicode_ci');
        }

        $result = $ability->doExecute(array());

        $this->assertIsArray($result);
        $this->assertArrayHasKey('wordpress_version', $result);
        $this->assertArrayHasKey('php_version', $result);
        $this->assertArrayHasKey('mysql_version', $result);
        $this->assertArrayHasKey('is_multisite', $result);
        $this->assertEquals('6.9.1', $result['wordpress_version']);
        $this->assertEquals(PHP_VERSION, $result['php_version']);
        $this->assertFalse($result['is_multisite']);
    }

    /**
     * Test execute with extra flag returns additional info.
     *
     * @return void
     */
    public function testExecuteWithExtraReturnsAdditionalInfo(): void
    {
        $ability = $this->getAbilityInstance();

        Functions\when('get_bloginfo')->justReturn('6.9.1');
        Functions\when('is_multisite')->justReturn(false);
        Functions\when('get_locale')->justReturn('en_US');
        Functions\when('site_url')->justReturn('https://example.com');
        Functions\when('home_url')->justReturn('https://example.com');

        $mock_wpdb = Mockery::mock('wpdb');
        $mock_wpdb->shouldReceive('db_version')->andReturn('8.0.32');
        $GLOBALS['wpdb'] = $mock_wpdb;

        $result = $ability->doExecute(array('extra' => true));

        $this->assertArrayHasKey('wp_local_package', $result);
        $this->assertArrayHasKey('site_url', $result);
        $this->assertArrayHasKey('home_url', $result);
        $this->assertEquals('en_US', $result['wp_local_package']);
    }

    /**
     * Test execute handles null wpdb gracefully.
     *
     * @return void
     */
    public function testExecuteHandlesNullWpdb(): void
    {
        $ability = $this->getAbilityInstance();

        Functions\when('get_bloginfo')->justReturn('6.9.1');
        Functions\when('is_multisite')->justReturn(false);

        $GLOBALS['wpdb'] = null;

        $result = $ability->doExecute(array());

        $this->assertEquals('unknown', $result['mysql_version']);
    }

    /**
     * Test annotations are correct for read-only ability.
     *
     * @return void
     */
    public function testGetAnnotations(): void
    {
        $ability     = new GetCoreVersionAbility();
        $annotations = $ability->getAnnotations();

        $this->assertTrue($annotations['readonly']);
        $this->assertFalse($annotations['destructive']);
        $this->assertTrue($annotations['idempotent']);
    }
}
