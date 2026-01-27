<?php

/**
 * Tests for CheckCoreUpdatesAbility.
 *
 * @package FAWpmcp\Tests\Abilities\Core
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Core;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Core\CheckCoreUpdatesAbility;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;

/**
 * Test CheckCoreUpdatesAbility functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Core
 */
class CheckCoreUpdatesAbilityTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get the ability instance to test.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new CheckCoreUpdatesAbility();
    }

    /**
     * Get the expected metadata for this ability.
     *
     * @return array<string, mixed>
     */
    protected function getExpectedMetadata(): array
    {
        return array(
            'name'                 => 'fa-wpmcp/check-core-updates',
            'category'             => 'core',
            'label'                => 'Check Core Updates',
            'description_contains' => 'update',
            'operation_type'       => 'read',
            'required_capability'  => 'update_core',
        );
    }

    /**
     * Test ability returns input schema with minor/major options.
     *
     * @return void
     */
    public function testGetInputSchema(): void
    {
        $ability = $this->getAbilityInstance();
        $schema  = $ability->getInputSchema();

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('minor', $schema['properties']);
        $this->assertArrayHasKey('major', $schema['properties']);
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
        $this->assertArrayHasKey('current_version', $schema['properties']);
        $this->assertArrayHasKey('updates', $schema['properties']);
        $this->assertArrayHasKey('update_available', $schema['properties']);
        $this->assertArrayHasKey('last_checked', $schema['properties']);
    }

    /**
     * Test execute returns no updates when up to date.
     *
     * @return void
     */
    public function testExecuteReturnsNoUpdatesWhenUpToDate(): void
    {
        $ability = $this->getAbilityInstance();

        Functions\when('wp_version_check')->justReturn(null);
        Functions\when('get_bloginfo')->justReturn('6.9.1');

        // Create update data with only 'latest' response.
        $update_data = (object) array(
            'updates'      => array(
                (object) array(
                    'response' => 'latest',
                    'version'  => '6.9.1',
                ),
            ),
            'last_checked' => time(),
        );

        Functions\when('get_site_transient')->justReturn($update_data);

        $result = $ability->doExecute(array());

        $this->assertIsArray($result);
        $this->assertEquals('6.9.1', $result['current_version']);
        $this->assertEmpty($result['updates']);
        $this->assertFalse($result['update_available']);
    }

    /**
     * Test execute returns updates when available.
     *
     * @return void
     */
    public function testExecuteReturnsUpdatesWhenAvailable(): void
    {
        $ability = $this->getAbilityInstance();

        Functions\when('wp_version_check')->justReturn(null);
        Functions\when('get_bloginfo')->justReturn('6.9.0');

        $update_data = (object) array(
            'updates'      => array(
                (object) array(
                    'response' => 'upgrade',
                    'version'  => '6.9.1',
                    'download' => 'https://downloads.wordpress.org/release/wordpress-6.9.1.zip',
                    'locale'   => 'en_US',
                    'packages' => (object) array(
                        'full' => 'https://downloads.wordpress.org/release/wordpress-6.9.1.zip',
                    ),
                ),
                (object) array(
                    'response' => 'latest',
                    'version'  => '6.9.0',
                ),
            ),
            'last_checked' => time(),
        );

        Functions\when('get_site_transient')->justReturn($update_data);

        $result = $ability->doExecute(array());

        $this->assertTrue($result['update_available']);
        $this->assertCount(1, $result['updates']);
        $this->assertEquals('6.9.1', $result['updates'][0]['version']);
        $this->assertEquals('upgrade', $result['updates'][0]['response']);
    }

    /**
     * Test execute filters minor updates only.
     *
     * @return void
     */
    public function testExecuteFiltersMinorUpdatesOnly(): void
    {
        $ability = $this->getAbilityInstance();

        Functions\when('wp_version_check')->justReturn(null);
        Functions\when('get_bloginfo')->justReturn('6.9.0');

        $update_data = (object) array(
            'updates'      => array(
                (object) array(
                    'response' => 'upgrade',
                    'version'  => '6.9.1',  // Minor update.
                ),
                (object) array(
                    'response' => 'upgrade',
                    'version'  => '7.0.0',  // Major update.
                ),
            ),
            'last_checked' => time(),
        );

        Functions\when('get_site_transient')->justReturn($update_data);

        $result = $ability->doExecute(array('minor' => true));

        $this->assertCount(1, $result['updates']);
        $this->assertEquals('6.9.1', $result['updates'][0]['version']);
    }

    /**
     * Test execute filters major updates only.
     *
     * @return void
     */
    public function testExecuteFiltersMajorUpdatesOnly(): void
    {
        $ability = $this->getAbilityInstance();

        Functions\when('wp_version_check')->justReturn(null);
        Functions\when('get_bloginfo')->justReturn('6.9.0');

        $update_data = (object) array(
            'updates'      => array(
                (object) array(
                    'response' => 'upgrade',
                    'version'  => '6.9.1',  // Minor update.
                ),
                (object) array(
                    'response' => 'upgrade',
                    'version'  => '7.0.0',  // Major update.
                ),
            ),
            'last_checked' => time(),
        );

        Functions\when('get_site_transient')->justReturn($update_data);

        $result = $ability->doExecute(array('major' => true));

        $this->assertCount(1, $result['updates']);
        $this->assertEquals('7.0.0', $result['updates'][0]['version']);
    }

    /**
     * Test execute handles empty update transient.
     *
     * @return void
     */
    public function testExecuteHandlesEmptyUpdateTransient(): void
    {
        $ability = $this->getAbilityInstance();

        Functions\when('wp_version_check')->justReturn(null);
        Functions\when('get_bloginfo')->justReturn('6.9.1');
        Functions\when('get_site_transient')->justReturn(false);

        $result = $ability->doExecute(array());

        $this->assertFalse($result['update_available']);
        $this->assertEmpty($result['updates']);
        $this->assertEquals('', $result['last_checked']);
    }

    /**
     * Test annotations are correct for read-only ability.
     *
     * @return void
     */
    public function testGetAnnotations(): void
    {
        $ability     = new CheckCoreUpdatesAbility();
        $annotations = $ability->getAnnotations();

        $this->assertTrue($annotations['readonly']);
        $this->assertFalse($annotations['destructive']);
    }
}
