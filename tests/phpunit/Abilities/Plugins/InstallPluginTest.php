<?php

/**
 * Tests for InstallPlugin.
 *
 * @package FAWpmcp\Tests\Abilities\Plugins
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Plugins;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Plugins\InstallPlugin;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;

class InstallPluginTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get an instance of the ability being tested.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new InstallPlugin();
    }

    /**
     * Get expected metadata for the ability.
     *
     * @return array
     */
    protected function getExpectedMetadata(): array
    {
        return array(
            'name'                 => 'fa-wpmcp/install-plugin',
            'category'             => 'plugins',
            'label'                => 'Install Plugin',
            'description_contains' => 'install a wordpress plugin',
            'operation_type'       => 'write',
            'required_capability'  => 'install_plugins',
        );
    }
}
