<?php
/**
 * Tests for DeletePlugin.
 *
 * @package FAWpmcp\Tests\Abilities\Plugins
 */

declare(strict_types=1);
namespace FAWpmcp\Tests\Abilities\Plugins;

use FAWpmcp\Abilities\Plugins\DeletePlugin;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;
class DeletePluginTest extends TestCase {
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
		$this->assertEquals( 'fa-wpmcp/delete-plugin', ( new DeletePlugin() )->getName() );
	}
	public function testGetOperationType(): void {
		$this->assertEquals( 'write', ( new DeletePlugin() )->getOperationType() );
	}
}
