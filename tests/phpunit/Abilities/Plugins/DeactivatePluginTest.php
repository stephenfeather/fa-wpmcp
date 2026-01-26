<?php
/**
 * Tests for DeactivatePlugin.
 *
 * @package FAWpmcp\Tests\Abilities\Plugins
 */

declare(strict_types=1);
namespace FAWpmcp\Tests\Abilities\Plugins;

use FAWpmcp\Abilities\Plugins\DeactivatePlugin;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;
class DeactivatePluginTest extends TestCase {
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
		$this->assertEquals( 'fa-wpmcp/deactivate-plugin', ( new DeactivatePlugin() )->getName() );
	}
	public function testExecuteDeactivatesPlugin(): void {
		Functions\expect( 'deactivate_plugins' )->once()->with( 'test/test.php' );
		$result = ( new DeactivatePlugin() )->doExecute( array( 'plugin' => 'test/test.php' ) );
		$this->assertEquals( 'test/test.php', $result['plugin'] );
		$this->assertTrue( $result['deactivated'] );
	}
}
