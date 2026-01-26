<?php

/**
 * Tests for ListPlugins ability.
 *
 * @package FAWpmcp\Tests\Abilities\Plugins
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Plugins;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Plugins\ListPlugins;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;

/**
 * Test ListPlugins ability functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Plugins
 */
class ListPluginsTest extends BrainMonkeyTestCase {
	use AbilityTestTrait;

	/**
	 * Get an instance of the ability being tested.
	 *
	 * @return AbstractAbility
	 */
	protected function getAbilityInstance(): AbstractAbility {
		return new ListPlugins();
	}

	/**
	 * Get expected metadata for the ability.
	 *
	 * @return array
	 */
	protected function getExpectedMetadata(): array {
		return array(
			'name'                 => 'fa-wpmcp/list-plugins',
			'category'             => 'plugins',
			'label'                => 'List Plugins',
			'description_contains' => 'list installed wordpress plugins',
			'operation_type'       => 'read',
			'required_capability'  => 'activate_plugins',
		);
	}

	public function testExecuteListsAllPlugins(): void {
		$ability = $this->getAbilityInstance();

		Functions\expect( 'get_plugins' )
			->once()
			->andReturn(
				array(
					'plugin1/plugin1.php' => array(
						'Name'    => 'Plugin One',
						'Version' => '1.0',
					),
					'plugin2/plugin2.php' => array(
						'Name'    => 'Plugin Two',
						'Version' => '2.0',
					),
				)
			);

		Functions\expect( 'is_plugin_active' )
			->twice()
			->andReturnUsing( fn( $plugin ) => $plugin === 'plugin1/plugin1.php' );

		$result = $ability->doExecute( array() );

		$this->assertCount( 2, $result['plugins'] );
		$this->assertEquals( 'plugin1/plugin1.php', $result['plugins'][0]['plugin'] );
		$this->assertTrue( $result['plugins'][0]['active'] );
		$this->assertEquals( 'plugin2/plugin2.php', $result['plugins'][1]['plugin'] );
		$this->assertFalse( $result['plugins'][1]['active'] );
	}
}
