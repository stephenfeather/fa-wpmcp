<?php
declare(strict_types=1);
namespace FAWpmcp\Tests\Abilities\Themes;

use FAWpmcp\Abilities\Themes\DeleteTheme;
use Brain\Monkey;
use PHPUnit\Framework\TestCase;

class DeleteThemeTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function testGetName(): void {
		$this->assertEquals( 'fa-wpmcp/delete-theme', ( new DeleteTheme() )->getName() );
	}

	public function testGetOperationType(): void {
		$this->assertEquals( 'write', ( new DeleteTheme() )->getOperationType() );
	}

	public function testExecuteReturnsSuccess(): void {
		$result = ( new DeleteTheme() )->doExecute( array( 'stylesheet' => 'twentytwentythree' ) );
		$this->assertTrue( $result['success'] );
	}
}
