<?php

/**
 * Tests for OptionsPermissionSettings.
 *
 * @package FAWpmcp\Tests\Permissions
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Permissions;

use Brain\Monkey\Functions;
use FAWpmcp\Permissions\OptionsPermissionSettings;
use FAWpmcp\ValueObjects\PermissionSettings;
use PHPUnit\Framework\TestCase;

/**
 * Test OptionsPermissionSettings load/save.
 */
final class OptionsPermissionSettingsTest extends TestCase {

	use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

	protected function setUp(): void {
		parent::setUp();
		\Brain\Monkey\setUp();
	}

	protected function tearDown(): void {
		\Brain\Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Test load returns defaults when option invalid.
	 *
	 * @return void
	 */
	public function test_load_returns_defaults_when_option_invalid(): void {
		Functions\expect( 'get_option' )
			->once()
			->with( 'fa_wpmcp_permissions', array() )
			->andReturn( 'invalid' );

		$settings = OptionsPermissionSettings::load();

		$this->assertTrue( $settings->global_read_enabled );
		$this->assertFalse( $settings->global_write_enabled );
		$this->assertSame( array(), $settings->category_settings );
	}

	/**
	 * Test load returns option values when present.
	 *
	 * @return void
	 */
	public function test_load_returns_option_values(): void {
		Functions\expect( 'get_option' )
			->once()
			->andReturn(
				array(
					'global_read_enabled'  => false,
					'global_write_enabled' => true,
					'category_settings'    => array( 'posts-pages' => array( 'enable_read' => false ) ),
					'ability_settings'     => array( 'fa-wpmcp/list-posts' => array( 'enabled' => false ) ),
				)
			);

		$settings = OptionsPermissionSettings::load();

		$this->assertFalse( $settings->global_read_enabled );
		$this->assertTrue( $settings->global_write_enabled );
		$this->assertArrayHasKey( 'posts-pages', $settings->category_settings );
		$this->assertArrayHasKey( 'fa-wpmcp/list-posts', $settings->ability_settings );
	}

	/**
	 * Test save persists settings via update_option.
	 *
	 * @return void
	 */
	public function test_save_persists_settings(): void {
		$settings = new PermissionSettings(
			global_read_enabled: true,
			global_write_enabled: true,
			category_settings: array( 'posts-pages' => array( 'enable_read' => true ) ),
			ability_settings: array( 'fa-wpmcp/list-posts' => array( 'enabled' => true ) ),
		);

		Functions\expect( 'update_option' )
			->once()
			->with(
				'fa_wpmcp_permissions',
				array(
					'global_read_enabled'  => true,
					'global_write_enabled' => true,
					'category_settings'    => array( 'posts-pages' => array( 'enable_read' => true ) ),
					'ability_settings'     => array( 'fa-wpmcp/list-posts' => array( 'enabled' => true ) ),
				)
			)
			->andReturn( true );

		OptionsPermissionSettings::save( $settings );
	}
}
