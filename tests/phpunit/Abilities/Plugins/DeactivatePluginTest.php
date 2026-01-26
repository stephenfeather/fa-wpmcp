<?php

/**
 * Tests for DeactivatePlugin.
 *
 * @package FAWpmcp\Tests\Abilities\Plugins
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Plugins;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Plugins\DeactivatePlugin;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;

class DeactivatePluginTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get an instance of the ability being tested.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new DeactivatePlugin();
    }

    /**
     * Get expected metadata for the ability.
     *
     * @return array
     */
    protected function getExpectedMetadata(): array
    {
        return array(
            'name'                 => 'fa-wpmcp/deactivate-plugin',
            'category'             => 'plugins',
            'label'                => 'Deactivate Plugin',
            'description_contains' => 'deactivate a wordpress plugin',
            'operation_type'       => 'write',
            'required_capability'  => 'activate_plugins',
        );
    }

    public function testExecuteDeactivatesPlugin(): void
    {
        Functions\expect('deactivate_plugins')->once()->with('test/test.php');
        $result = $this->getAbilityInstance()->doExecute(array( 'plugin' => 'test/test.php' ));
        $this->assertEquals('test/test.php', $result['plugin']);
        $this->assertTrue($result['deactivated']);
    }
}
