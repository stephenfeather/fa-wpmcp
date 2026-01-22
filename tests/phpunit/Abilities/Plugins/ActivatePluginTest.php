<?php
declare(strict_types=1);
namespace FAWpmcp\Tests\Abilities\Plugins;
use FAWpmcp\Abilities\Plugins\ActivatePlugin;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;
class ActivatePluginTest extends TestCase {
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
		$this->assertEquals( 'fa-wpmcp/activate-plugin', ( new ActivatePlugin() )->getName() );
	}
	public function testGetOperationType(): void {
		$this->assertEquals( 'write', ( new ActivatePlugin() )->getOperationType() );
	}
	public function testExecuteActivatesPlugin(): void {
		Functions\expect( 'activate_plugin' )->once()->with( 'test/test.php' )->andReturn( null );
		$result = ( new ActivatePlugin() )->doExecute( array( 'plugin' => 'test/test.php' ) );
		$this->assertEquals( 'test/test.php', $result['plugin'] );
		$this->assertTrue( $result['activated'] );
	}
}
