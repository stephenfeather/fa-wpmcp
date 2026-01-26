<?php
/**
 * Tests for ability category registration.
 *
 * @package FAWpmcp
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities;

use FAWpmcp\Plugin;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test ability category registration with WordPress Abilities API.
 *
 * @covers FAWpmcp\Plugin::registerAbilityCategories
 */
final class CategoryRegistrationTest extends TestCase {
	/**
	 * Set up Brain\Monkey before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	/**
	 * Tear down Brain\Monkey after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * Test that all fourteen categories are registered.
	 *
	 * @return void
	 */
	public function test_registerAbilityCategories_registers_all_fourteen_categories(): void {
		Functions\expect( 'wp_register_ability_category' )
			->times( 14 );

		Functions\expect( '__' )
			->andReturnUsing( fn( $text ) => $text );

		$plugin = Plugin::getInstance();
		$reflection = new \ReflectionClass( $plugin );
		$method = $reflection->getMethod( 'registerAbilityCategories' );
		$method->invoke( $plugin );

		// Assertion to avoid risky test warning.
		$this->assertTrue( true );
	}

	/**
	 * Test that category slugs follow WordPress naming conventions.
	 *
	 * @return void
	 */
	public function test_registerAbilityCategories_validates_category_slugs(): void {
		$expected_slugs = array(
			'posts-pages',
			'comments',
			'media',
			'taxonomies',
			'post-types',
			'users',
			'settings',
			'plugins',
			'themes',
			'privacy',
			'cache',
			'maintenance',
			'transients',
			'cron',
		);

		foreach ( $expected_slugs as $slug ) {
			Functions\expect( 'wp_register_ability_category' )
				->once()
				->with( $slug, Mockery::type( 'array' ) );
		}

		Functions\expect( '__' )
			->andReturnUsing( fn( $text ) => $text );

		$plugin = Plugin::getInstance();
		$reflection = new \ReflectionClass( $plugin );
		$method = $reflection->getMethod( 'registerAbilityCategories' );
		$method->invoke( $plugin );

		// Assertion to avoid risky test warning.
		$this->assertTrue( true );
	}

	/**
	 * Test that all categories include required fields.
	 *
	 * @return void
	 */
	public function test_registerAbilityCategories_includes_required_fields(): void {
		Functions\expect( 'wp_register_ability_category' )
			->times( 14 )
			->andReturnUsing(
				function ( $slug, $args ) {
					$this->assertIsString( $slug );
					$this->assertIsArray( $args );
					$this->assertArrayHasKey( 'label', $args );
					$this->assertArrayHasKey( 'description', $args );
					$this->assertNotEmpty( $args['label'] );
					$this->assertNotEmpty( $args['description'] );
				}
			);

		Functions\expect( '__' )
			->andReturnUsing( fn( $text ) => $text );

		$plugin = Plugin::getInstance();
		$reflection = new \ReflectionClass( $plugin );
		$method = $reflection->getMethod( 'registerAbilityCategories' );
		$method->invoke( $plugin );
	}

	/**
	 * Test that categories are registered with proper i18n support.
	 *
	 * @return void
	 */
	public function test_registerAbilityCategories_uses_i18n(): void {
		Functions\expect( 'wp_register_ability_category' )
			->times( 14 );

		Functions\expect( '__' )
			->times( 28 ) // 14 labels + 14 descriptions.
			->with( Mockery::type( 'string' ), 'fa-wpmcp' )
			->andReturnUsing( fn( $text ) => $text );

		$plugin = Plugin::getInstance();
		$reflection = new \ReflectionClass( $plugin );
		$method = $reflection->getMethod( 'registerAbilityCategories' );
		$method->invoke( $plugin );

		// Assertion to avoid risky test warning.
		$this->assertTrue( true );
	}
}
