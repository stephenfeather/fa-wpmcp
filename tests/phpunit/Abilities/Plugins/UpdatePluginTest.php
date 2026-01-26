<?php
/**
 * Tests for UpdatePlugin.
 *
 * @package FAWpmcp\Tests\Abilities\Plugins
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Plugins;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Plugins\UpdatePlugin;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;

class UpdatePluginTest extends BrainMonkeyTestCase {
	use AbilityTestTrait;

	/**
	 * Get an instance of the ability being tested.
	 *
	 * @return AbstractAbility
	 */
	protected function getAbilityInstance(): AbstractAbility {
		return new UpdatePlugin();
	}

	/**
	 * Get expected metadata for the ability.
	 *
	 * @return array
	 */
	protected function getExpectedMetadata(): array {
		return array(
			'name'                 => 'fa-wpmcp/update-plugin',
			'category'             => 'plugins',
			'label'                => 'Update Plugin',
			'description_contains' => 'update a wordpress plugin',
			'operation_type'       => 'write',
			'required_capability'  => 'update_plugins',
		);
	}
}
