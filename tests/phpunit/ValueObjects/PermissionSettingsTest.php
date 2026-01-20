<?php
/**
 * Test PermissionSettings value object.
 *
 * @package FAWpmcp\Tests\ValueObjects
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\ValueObjects;

use FAWpmcp\ValueObjects\PermissionSettings;
use PHPUnit\Framework\TestCase;

/**
 * Test PermissionSettings value object.
 */
class PermissionSettingsTest extends TestCase {
	/**
	 * Test PermissionSettings is immutable.
	 */
	public function test_is_immutable(): void {
		$settings = new PermissionSettings(
			global_read_enabled: true,
			global_write_enabled: false,
			category_settings: array(),
			ability_settings: array(),
		);

		// Attempting to modify should create new instance.
		$new_settings = $settings->with_global_read( false );

		$this->assertTrue( $settings->global_read_enabled );
		$this->assertFalse( $new_settings->global_read_enabled );
		$this->assertNotSame( $settings, $new_settings );
	}

	/**
	 * Test with_global_read creates new instance.
	 */
	public function test_with_global_read_creates_new_instance(): void {
		$settings = new PermissionSettings(
			global_read_enabled: true,
			global_write_enabled: true,
			category_settings: array(),
			ability_settings: array(),
		);

		$new_settings = $settings->with_global_read( false );

		$this->assertFalse( $new_settings->global_read_enabled );
		$this->assertTrue( $new_settings->global_write_enabled );
	}

	/**
	 * Test with_global_write creates new instance.
	 */
	public function test_with_global_write_creates_new_instance(): void {
		$settings = new PermissionSettings(
			global_read_enabled: true,
			global_write_enabled: true,
			category_settings: array(),
			ability_settings: array(),
		);

		$new_settings = $settings->with_global_write( false );

		$this->assertTrue( $new_settings->global_read_enabled );
		$this->assertFalse( $new_settings->global_write_enabled );
	}

	/**
	 * Test properties are readonly.
	 */
	public function test_properties_are_readonly(): void {
		$settings = new PermissionSettings(
			global_read_enabled: true,
			global_write_enabled: false,
			category_settings: array(),
			ability_settings: array(),
		);

		$this->expectException( \Error::class );
		// @phpstan-ignore-next-line - Intentionally testing immutability.
		$settings->global_read_enabled = false;
	}

	/**
	 * Test category settings are stored correctly.
	 */
	public function test_category_settings_stored_correctly(): void {
		$category_settings = array(
			'posts-pages' => array(
				'enable_read'  => true,
				'enable_write' => false,
			),
		);

		$settings = new PermissionSettings(
			global_read_enabled: true,
			global_write_enabled: true,
			category_settings: $category_settings,
			ability_settings: array(),
		);

		$this->assertSame( $category_settings, $settings->category_settings );
	}

	/**
	 * Test ability settings are stored correctly.
	 */
	public function test_ability_settings_stored_correctly(): void {
		$ability_settings = array(
			'fa-wpmcp/create-post' => array( 'enabled' => false ),
		);

		$settings = new PermissionSettings(
			global_read_enabled: true,
			global_write_enabled: true,
			category_settings: array(),
			ability_settings: $ability_settings,
		);

		$this->assertSame( $ability_settings, $settings->ability_settings );
	}
}
