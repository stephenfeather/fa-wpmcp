<?php
declare(strict_types=1);
namespace FAWpmcp\Tests\Abilities\Plugins;

use FAWpmcp\Abilities\Plugins\GetPlugin;
use FAWpmcp\Exceptions\PluginNotFoundException;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

class GetPluginTest extends TestCase {
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
		$this->assertEquals( 'fa-wpmcp/get-plugin', ( new GetPlugin() )->getName() );
	}

	public function testGetCategory(): void {
		$this->assertEquals( 'plugins', ( new GetPlugin() )->getCategory() );
	}

	public function testExecuteReturnsPluginDetails(): void {
		$ability = new GetPlugin();

		Functions\expect( 'get_plugins' )->once()->andReturn(
			array( 'test/test.php' => array( 'Name' => 'Test Plugin', 'Version' => '1.0' ) )
		);
		Functions\expect( 'is_plugin_active' )->once()->with( 'test/test.php' )->andReturn( true );

		$result = $ability->doExecute( array( 'plugin' => 'test/test.php' ) );

		$this->assertEquals( 'test/test.php', $result['plugin'] );
		$this->assertEquals( 'Test Plugin', $result['name'] );
		$this->assertTrue( $result['active'] );
	}

	public function testExecuteThrowsWhenPluginNotFound(): void {
		$this->expectException( PluginNotFoundException::class );

		Functions\expect( 'get_plugins' )->once()->andReturn( array() );

		( new GetPlugin() )->doExecute( array( 'plugin' => 'missing/missing.php' ) );
	}
}
