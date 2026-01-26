<?php
/**
 * Tests for StatusTheme.
 *
 * @package FAWpmcp\Tests\Abilities\Themes
 */

declare(strict_types=1);
namespace FAWpmcp\Tests\Abilities\Themes;

use FAWpmcp\Abilities\Themes\StatusTheme;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

class StatusThemeTest extends TestCase {
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
		$this->assertEquals( 'fa-wpmcp/status-theme', ( new StatusTheme() )->getName() );
	}

	public function testGetCategory(): void {
		$this->assertEquals( 'themes', ( new StatusTheme() )->getCategory() );
	}

	public function testGetOperationType(): void {
		$this->assertEquals( 'read', ( new StatusTheme() )->getOperationType() );
	}

	public function testExecuteReturnsThemeStatus(): void {
		$theme = Mockery::mock( 'WP_Theme' );
		$theme->shouldReceive( 'get_stylesheet' )->andReturn( 'twentytwentyfour' );
		$theme->shouldReceive( 'get' )->with( 'Name' )->andReturn( 'Twenty Twenty-Four' );
		$theme->shouldReceive( 'get' )->with( 'Version' )->andReturn( '1.0' );
		$theme->shouldReceive( 'get' )->with( 'Author' )->andReturn( 'WordPress Team' );

		Functions\expect( 'wp_get_theme' )->once()->with( 'twentytwentyfour' )->andReturn( $theme );
		Functions\expect( 'get_option' )->with( 'stylesheet' )->once()->andReturn( 'twentytwentyfour' );

		$result = ( new StatusTheme() )->doExecute( array( 'stylesheet' => 'twentytwentyfour' ) );

		$this->assertEquals( 'Twenty Twenty-Four', $result['name'] );
		$this->assertEquals( 'Active', $result['status'] );
	}
}
