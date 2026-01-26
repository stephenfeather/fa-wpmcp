<?php
/**
 * Tests for InstallPlugin.
 *
 * @package FAWpmcp\Tests\Abilities\Plugins
 */

declare(strict_types=1);
namespace FAWpmcp\Tests\Abilities\Plugins;

use FAWpmcp\Abilities\Plugins\InstallPlugin;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;
class InstallPluginTest extends TestCase {
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
		$this->assertEquals( 'fa-wpmcp/install-plugin', ( new InstallPlugin() )->getName() );
	}
	public function testGetOperationType(): void {
		$this->assertEquals( 'write', ( new InstallPlugin() )->getOperationType() );
	}
}
