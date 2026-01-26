<?php
/**
 * Tests for InstallTheme.
 *
 * @package FAWpmcp\Tests\Abilities\Themes
 */

declare(strict_types=1);
namespace FAWpmcp\Tests\Abilities\Themes;

use FAWpmcp\Abilities\Themes\InstallTheme;
use Brain\Monkey;
use PHPUnit\Framework\TestCase;

class InstallThemeTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function testGetName(): void {
		$this->assertEquals( 'fa-wpmcp/install-theme', ( new InstallTheme() )->getName() );
	}

	public function testGetOperationType(): void {
		$this->assertEquals( 'write', ( new InstallTheme() )->getOperationType() );
	}

	public function testExecuteThrowsNotImplementedException(): void {
		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Theme installation is not yet implemented' );

		( new InstallTheme() )->doExecute( array( 'slug' => 'twentytwentyfour' ) );
	}
}
