<?php
/**
 * Tests for DeletePlugin.
 *
 * @package FAWpmcp\Tests\Abilities\Plugins
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Plugins;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Plugins\DeletePlugin;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;

class DeletePluginTest extends BrainMonkeyTestCase {
	use AbilityTestTrait;

	/**
	 * Get an instance of the ability being tested.
	 *
	 * @return AbstractAbility
	 */
	protected function getAbilityInstance(): AbstractAbility {
		return new DeletePlugin();
	}

	/**
	 * Get expected metadata for the ability.
	 *
	 * @return array
	 */
	protected function getExpectedMetadata(): array {
		return array(
			'name'                 => 'fa-wpmcp/delete-plugin',
			'category'             => 'plugins',
			'label'                => 'Delete Plugin',
			'description_contains' => 'delete a wordpress plugin',
			'operation_type'       => 'write',
			'required_capability'  => 'delete_plugins',
		);
	}
}
