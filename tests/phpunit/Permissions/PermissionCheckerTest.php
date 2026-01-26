<?php
/**
 * Test PermissionChecker logic.
 *
 * @package FAWpmcp\Tests\Permissions
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Permissions;

use FAWpmcp\Permissions\PermissionChecker;
use FAWpmcp\ValueObjects\PermissionSettings;
use PHPUnit\Framework\TestCase;

/**
 * Test PermissionChecker logic.
 */
class PermissionCheckerTest extends TestCase {

	/**
	 * Test global read disabled blocks all reads.
	 */
	public function test_global_read_disabled_blocks_all_reads(): void {
		$settings = new PermissionSettings(
			global_read_enabled: false,
			global_write_enabled: true,
			category_settings: array(),
			ability_settings: array(),
		);

		$result = PermissionChecker::check( $settings, 'fa-wpmcp/list-posts', 'read' );

		$this->assertFalse( $result->is_success );
		$this->assertSame( 'ability_disabled', $result->error_code );
	}

	/**
	 * Test global write disabled blocks all writes.
	 */
	public function test_global_write_disabled_blocks_all_writes(): void {
		$settings = new PermissionSettings(
			global_read_enabled: true,
			global_write_enabled: false,
			category_settings: array(),
			ability_settings: array(),
		);

		$result = PermissionChecker::check( $settings, 'fa-wpmcp/create-post', 'write' );

		$this->assertFalse( $result->is_success );
		$this->assertSame( 'ability_disabled', $result->error_code );
	}

	/**
	 * Test category write disabled blocks category writes.
	 */
	public function test_category_write_disabled_blocks_category_writes(): void {
		$settings = new PermissionSettings(
			global_read_enabled: true,
			global_write_enabled: true,
			category_settings: array(
				'posts-pages' => array(
					'enable_read'  => true,
					'enable_write' => false,
				),
			),
			ability_settings: array(),
		);

		$result = PermissionChecker::checkCategory( $settings, 'posts-pages', 'write' );

		$this->assertFalse( $result->is_success );
	}

	/**
	 * Test ability level override works.
	 */
	public function test_ability_level_override_works(): void {
		$settings = new PermissionSettings(
			global_read_enabled: true,
			global_write_enabled: true,
			category_settings: array(
				'posts-pages' => array(
					'enable_read'  => true,
					'enable_write' => true,
				),
			),
			ability_settings: array(
				'fa-wpmcp/create-post' => array( 'enabled' => false ),
			),
		);

		$result = PermissionChecker::checkAbility( $settings, 'fa-wpmcp/create-post' );

		$this->assertFalse( $result->is_success );
	}

	/**
	 * Test hierarchy check follows correct order.
	 */
	public function test_hierarchy_check_follows_correct_order(): void {
		$settings = new PermissionSettings(
			global_read_enabled: true,
			global_write_enabled: true,
			category_settings: array(),
			ability_settings: array(),
		);

		$result = PermissionChecker::check( $settings, 'fa-wpmcp/list-posts', 'read' );

		$this->assertTrue( $result->is_success );
	}

	/**
	 * Test check_global allows enabled operations.
	 */
	public function test_check_global_allows_enabled_operations(): void {
		$settings = new PermissionSettings(
			global_read_enabled: true,
			global_write_enabled: true,
			category_settings: array(),
			ability_settings: array(),
		);

		$read_result  = PermissionChecker::checkGlobal( $settings, 'read' );
		$write_result = PermissionChecker::checkGlobal( $settings, 'write' );

		$this->assertTrue( $read_result->is_success );
		$this->assertTrue( $write_result->is_success );
	}

	/**
	 * Test check_global blocks disabled operations.
	 */
	public function test_check_global_blocks_disabled_operations(): void {
		$settings = new PermissionSettings(
			global_read_enabled: false,
			global_write_enabled: false,
			category_settings: array(),
			ability_settings: array(),
		);

		$read_result  = PermissionChecker::checkGlobal( $settings, 'read' );
		$write_result = PermissionChecker::checkGlobal( $settings, 'write' );

		$this->assertFalse( $read_result->is_success );
		$this->assertFalse( $write_result->is_success );
	}

	/**
	 * Test check_category inherits from global when no category settings.
	 */
	public function test_check_category_inherits_when_no_settings(): void {
		$settings = new PermissionSettings(
			global_read_enabled: true,
			global_write_enabled: true,
			category_settings: array(),
			ability_settings: array(),
		);

		$result = PermissionChecker::checkCategory( $settings, 'posts-pages', 'read' );

		$this->assertTrue( $result->is_success );
	}

	/**
	 * Test check_ability allows when no specific settings.
	 */
	public function test_check_ability_allows_when_no_settings(): void {
		$settings = new PermissionSettings(
			global_read_enabled: true,
			global_write_enabled: true,
			category_settings: array(),
			ability_settings: array(),
		);

		$result = PermissionChecker::checkAbility( $settings, 'fa-wpmcp/list-posts' );

		$this->assertTrue( $result->is_success );
	}

	/**
	 * Test full hierarchy with all levels enabled.
	 */
	public function test_full_hierarchy_all_enabled(): void {
		$settings = new PermissionSettings(
			global_read_enabled: true,
			global_write_enabled: true,
			category_settings: array(
				'posts-pages' => array(
					'enable_read'  => true,
					'enable_write' => true,
				),
			),
			ability_settings: array(
				'fa-wpmcp/list-posts' => array( 'enabled' => true ),
			),
		);

		$result = PermissionChecker::check( $settings, 'fa-wpmcp/list-posts', 'read', 'posts-pages' );

		$this->assertTrue( $result->is_success );
	}

	/**
	 * Test full hierarchy blocks at first failure.
	 */
	public function test_full_hierarchy_blocks_at_first_failure(): void {
		$settings = new PermissionSettings(
			global_read_enabled: false,
			global_write_enabled: false,
			category_settings: array(
				'posts-pages' => array(
					'enable_read'  => true,
					'enable_write' => true,
				),
			),
			ability_settings: array(
				'fa-wpmcp/list-posts' => array( 'enabled' => true ),
			),
		);

		$result = PermissionChecker::check( $settings, 'fa-wpmcp/list-posts', 'read', 'posts-pages' );

		$this->assertFalse( $result->is_success );
		$this->assertStringContainsString( 'Global', $result->error_message );
	}
}
