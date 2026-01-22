<?php
/**
 * Tests for GetTheme ability.
 *
 * @package FAWpmcp\Tests\Abilities\Themes
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Themes;

use FAWpmcp\Abilities\Themes\GetTheme;
use FAWpmcp\Exceptions\ThemeNotFoundException;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test GetTheme ability functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Themes
 */
class GetThemeTest extends TestCase {
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
		$ability = new GetTheme();
		$this->assertEquals( 'fa-wpmcp/get-theme', $ability->getName() );
	}

	public function testGetCategory(): void {
		$ability = new GetTheme();
		$this->assertEquals( 'themes', $ability->getCategory() );
	}

	public function testGetOperationType(): void {
		$ability = new GetTheme();
		$this->assertEquals( 'read', $ability->getOperationType() );
	}

	public function testExecuteReturnsThemeDetails(): void {
		$ability = new GetTheme();

		$theme = Mockery::mock( 'WP_Theme' );
		$theme->shouldReceive( 'get_stylesheet' )->andReturn( 'twentytwentyfour' );
		$theme->shouldReceive( 'get' )->with( 'Name' )->andReturn( 'Twenty Twenty-Four' );
		$theme->shouldReceive( 'get' )->with( 'Version' )->andReturn( '1.0' );
		$theme->shouldReceive( 'exists' )->andReturn( true );

		Functions\expect( 'wp_get_theme' )
			->once()
			->with( 'twentytwentyfour' )
			->andReturn( $theme );

		Functions\expect( 'get_option' )
			->with( 'stylesheet' )
			->once()
			->andReturn( 'twentytwentyfour' );

		$result = $ability->doExecute( array( 'stylesheet' => 'twentytwentyfour' ) );

		$this->assertEquals( 'twentytwentyfour', $result['stylesheet'] );
		$this->assertEquals( 'Twenty Twenty-Four', $result['name'] );
		$this->assertEquals( '1.0', $result['version'] );
		$this->assertTrue( $result['active'] );
	}

	public function testExecuteThrowsWhenThemeNotFound(): void {
		$this->expectException( ThemeNotFoundException::class );

		$theme = Mockery::mock( 'WP_Theme' );
		$theme->shouldReceive( 'exists' )->andReturn( false );

		Functions\expect( 'wp_get_theme' )
			->once()
			->with( 'nonexistent' )
			->andReturn( $theme );

		( new GetTheme() )->doExecute( array( 'stylesheet' => 'nonexistent' ) );
	}
}
