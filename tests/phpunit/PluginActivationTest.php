<?php

/**
 * Test plugin activation and basic scaffolding.
 *
 * @package FAWpmcp\Tests
 */

declare(strict_types=1);

namespace FAWpmcp\Tests;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;

/**
 * Test plugin activation and basic scaffolding.
 *
 * @package FAWpmcp\Tests
 */
class PluginActivationTest extends TestCase {

	/**
	 * Set up test environment.
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	/**
	 * Tear down test environment.
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Test that plugin defines version constant.
	 */
	public function test_plugin_defines_version_constant(): void {
		// After including main plugin file, FA_WPMCP_VERSION should be defined.
		$this->assertTrue( defined( 'FA_WPMCP_VERSION' ) );
	}

	/**
	 * Test that plugin defines path constant.
	 */
	public function test_plugin_defines_path_constant(): void {
		$this->assertTrue( defined( 'FA_WPMCP_PATH' ) );
	}

	/**
	 * Test that autoloader resolves plugin class.
	 */
	public function test_autoloader_resolves_plugin_class(): void {
		$this->assertTrue( class_exists( 'FAWpmcp\\Plugin' ) );
	}
}
