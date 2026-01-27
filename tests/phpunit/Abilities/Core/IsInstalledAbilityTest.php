<?php

/**
 * Tests for IsInstalledAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Core
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Core;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Core\IsInstalledAbility;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test IsInstalledAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Core
 */
class IsInstalledAbilityTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get the ability instance to test.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new IsInstalledAbility();
    }

    /**
     * Get the expected metadata for this ability.
     *
     * @return array<string, mixed>
     */
    protected function getExpectedMetadata(): array
    {
        return array(
            'name'                 => 'fa-wpmcp/is-installed',
            'category'             => 'core',
            'label'                => 'Is Installed',
            'description_contains' => 'installed',
            'operation_type'       => 'read',
            'required_capability'  => 'manage_options',
        );
    }

    /**
     * Test ability returns input schema with network option.
     *
     * @return void
     */
    public function testGetInputSchema(): void
    {
        $ability = $this->getAbilityInstance();
        $schema  = $ability->getInputSchema();

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('network', $schema['properties']);
    }

    /**
     * Test ability returns output schema with installation properties.
     *
     * @return void
     */
    public function testGetOutputSchema(): void
    {
        $ability = $this->getAbilityInstance();
        $schema  = $ability->getOutputSchema();

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('installed', $schema['properties']);
        $this->assertArrayHasKey('database_ready', $schema['properties']);
        $this->assertArrayHasKey('has_admin_user', $schema['properties']);
        $this->assertArrayHasKey('is_multisite', $schema['properties']);
    }

    /**
     * Test execute returns installed status.
     *
     * @return void
     */
    public function testExecuteReturnsInstalledStatus(): void
    {
        $ability = $this->getAbilityInstance();

        // Mock WordPress functions.
        Functions\when('is_blog_installed')->justReturn(true);
        Functions\when('is_multisite')->justReturn(false);
        Functions\when('site_url')->justReturn('https://example.com');
        Functions\when('get_users')->justReturn(
            array(
                (object) array( 'ID' => 1, 'user_login' => 'admin' ),
            )
        );

        // Mock wpdb.
        $mock_wpdb = Mockery::mock('wpdb');
        $mock_wpdb->prefix      = 'wp_';
        $mock_wpdb->base_prefix = 'wp_';
        $mock_wpdb->shouldReceive('get_var')
            ->andReturn('wp_posts'); // Simulate table exists.
        $mock_wpdb->shouldReceive('prepare')
            ->andReturnUsing(function ($query, $table) {
                return str_replace('%s', "'$table'", $query);
            });

        $GLOBALS['wpdb'] = $mock_wpdb;

        $result = $ability->doExecute(array());

        $this->assertIsArray($result);
        $this->assertTrue($result['installed']);
        $this->assertTrue($result['database_ready']);
        $this->assertTrue($result['has_admin_user']);
        $this->assertFalse($result['is_multisite']);
        $this->assertEquals('https://example.com', $result['site_url']);
        $this->assertEquals('wp_', $result['db_prefix']);
    }

    /**
     * Test execute returns not installed when blog not installed.
     *
     * @return void
     */
    public function testExecuteReturnsNotInstalledWhenBlogNotInstalled(): void
    {
        $ability = $this->getAbilityInstance();

        Functions\when('is_blog_installed')->justReturn(false);
        Functions\when('is_multisite')->justReturn(false);
        Functions\when('site_url')->justReturn('');
        Functions\when('get_users')->justReturn(array());

        $mock_wpdb = Mockery::mock('wpdb');
        $mock_wpdb->prefix      = 'wp_';
        $mock_wpdb->base_prefix = 'wp_';
        $mock_wpdb->shouldReceive('get_var')->andReturn(null); // No tables.
        $mock_wpdb->shouldReceive('prepare')->andReturn('');

        $GLOBALS['wpdb'] = $mock_wpdb;

        $result = $ability->doExecute(array());

        $this->assertFalse($result['installed']);
        $this->assertFalse($result['database_ready']);
        $this->assertFalse($result['has_admin_user']);
    }

    /**
     * Test execute checks network tables when network flag is true.
     *
     * @return void
     */
    public function testExecuteChecksNetworkTablesWhenRequested(): void
    {
        $ability = $this->getAbilityInstance();

        Functions\when('is_blog_installed')->justReturn(true);
        Functions\when('is_multisite')->justReturn(true);
        Functions\when('site_url')->justReturn('https://example.com');
        Functions\when('get_users')->justReturn(
            array(
                (object) array( 'ID' => 1, 'user_login' => 'admin' ),
            )
        );

        $mock_wpdb = Mockery::mock('wpdb');
        $mock_wpdb->prefix      = 'wp_';
        $mock_wpdb->base_prefix = 'wp_';
        $mock_wpdb->shouldReceive('get_var')
            ->andReturn('wp_posts'); // All tables exist.
        $mock_wpdb->shouldReceive('prepare')->andReturnUsing(
            function ($query, $table) {
                return str_replace('%s', "'$table'", $query);
            }
        );

        $GLOBALS['wpdb'] = $mock_wpdb;

        $result = $ability->doExecute(array( 'network' => true ));

        $this->assertTrue($result['is_multisite']);
        $this->assertTrue($result['network_installed']);
    }

    /**
     * Test execute handles null wpdb gracefully.
     *
     * @return void
     */
    public function testExecuteHandlesNullWpdb(): void
    {
        $ability = $this->getAbilityInstance();

        Functions\when('is_blog_installed')->justReturn(true);
        Functions\when('is_multisite')->justReturn(false);
        Functions\when('site_url')->justReturn('https://example.com');
        Functions\when('get_users')->justReturn(array());

        $GLOBALS['wpdb'] = null;

        $result = $ability->doExecute(array());

        $this->assertFalse($result['database_ready']);
        $this->assertEquals('', $result['db_prefix']);
    }

    /**
     * Test annotations are correct for read-only ability.
     *
     * @return void
     */
    public function testGetAnnotations(): void
    {
        $ability     = new IsInstalledAbility();
        $annotations = $ability->getAnnotations();

        $this->assertTrue($annotations['readonly']);
        $this->assertFalse($annotations['destructive']);
    }
}
