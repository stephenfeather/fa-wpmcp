<?php
/**
 * Tests for ActivatePlugin.
 *
 * @package FAWpmcp\Tests\Abilities\Plugins
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Plugins;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Plugins\ActivatePlugin;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;

class ActivatePluginTest extends BrainMonkeyTestCase {
	use AbilityTestTrait;

	/**
	 * Get an instance of the ability being tested.
	 *
	 * @return AbstractAbility
	 */
	protected function getAbilityInstance(): AbstractAbility {
		return new ActivatePlugin();
	}

	/**
	 * Get expected metadata for the ability.
	 *
	 * @return array
	 */
	protected function getExpectedMetadata(): array {
		return array(
			'name'                 => 'fa-wpmcp/activate-plugin',
			'category'             => 'plugins',
			'label'                => 'Activate Plugin',
			'description_contains' => 'activate a wordpress plugin',
			'operation_type'       => 'write',
			'required_capability'  => 'activate_plugins',
		);
	}

	public function testExecuteActivatesPlugin(): void {
		Functions\expect( 'activate_plugin' )->once()->with( 'test/test.php' )->andReturn( null );
		$result = $this->getAbilityInstance()->doExecute( array( 'plugin' => 'test/test.php' ) );
		$this->assertEquals( 'test/test.php', $result['plugin'] );
		$this->assertTrue( $result['activated'] );
	}
}
