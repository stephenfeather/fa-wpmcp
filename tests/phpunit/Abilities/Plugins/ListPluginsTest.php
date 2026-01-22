<?php
/**
 * Tests for ListPlugins ability.
 *
 * @package FAWpmcp\Tests\Abilities\Plugins
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Plugins;

use FAWpmcp\Abilities\Plugins\ListPlugins;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test ListPlugins ability functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Plugins
 */
class ListPluginsTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		Mockery::close();
		parent::tearDown();
	}

	public function testGetName(): void {
		$ability = new ListPlugins();
		$this->assertEquals( 'fa-wpmcp/list-plugins', $ability->getName() );
	}

	public function testGetCategory(): void {
		$ability = new ListPlugins();
		$this->assertEquals( 'plugins', $ability->getCategory() );
	}

	public function testGetOperationType(): void {
		$ability = new ListPlugins();
		$this->assertEquals( 'read', $ability->getOperationType() );
	}

	public function testGetRequiredCapability(): void {
		$ability = new ListPlugins();
		$this->assertEquals( 'activate_plugins', $ability->getRequiredCapability() );
	}

	public function testExecuteListsAllPlugins(): void {
		$ability = new ListPlugins();

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
