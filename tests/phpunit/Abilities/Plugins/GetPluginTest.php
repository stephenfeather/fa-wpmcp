<?php

/**
 * Tests for GetPlugin.
 *
 * @package FAWpmcp\Tests\Abilities\Plugins
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Plugins;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Plugins\GetPlugin;
use FAWpmcp\Exceptions\PluginNotFoundException;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;

class GetPluginTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get an instance of the ability being tested.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new GetPlugin();
    }

    /**
     * Get expected metadata for the ability.
     *
     * @return array
     */
    protected function getExpectedMetadata(): array
    {
        return array(
            'name'                 => 'fa-wpmcp/get-plugin',
            'category'             => 'plugins',
            'label'                => 'Get Plugin',
            'description_contains' => 'get details about a specific wordpress plugin',
            'operation_type'       => 'read',
            'required_capability'  => 'activate_plugins',
        );
    }

    public function testExecuteReturnsPluginDetails(): void
    {
        $ability = $this->getAbilityInstance();

        Functions\expect('get_plugins')->once()->andReturn(
            array(
                'test/test.php' => array(
                    'Name'    => 'Test Plugin',
                    'Version' => '1.0',
                ),
            )
        );
        Functions\expect('is_plugin_active')->once()->with('test/test.php')->andReturn(true);

        $result = $ability->doExecute(array( 'plugin' => 'test/test.php' ));

        $this->assertEquals('test/test.php', $result['plugin']);
        $this->assertEquals('Test Plugin', $result['name']);
        $this->assertTrue($result['active']);
    }

    public function testExecuteThrowsWhenPluginNotFound(): void
    {
        $this->expectException(PluginNotFoundException::class);

        Functions\expect('get_plugins')->once()->andReturn(array());

        $this->getAbilityInstance()->doExecute(array( 'plugin' => 'missing/missing.php' ));
    }
}
