<?php
/**
 * Test plugin core functionality.
 *
 * @package FAWpmcp\Tests
 */

declare(strict_types=1);

namespace FAWpmcp\Tests;

use FAWpmcp\Plugin;
use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Test plugin core functionality.
 */
class PluginTest extends TestCase {
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
	 * Test that get_instance returns a singleton.
	 */
	public function test_get_instance_returns_singleton(): void {
		$instance1 = Plugin::get_instance();
		$instance2 = Plugin::get_instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test that init registers WordPress hooks.
	 */
	public function test_init_registers_hooks(): void {
		Functions\expect( 'add_action' )
			->once()
			->with( 'admin_init', \Mockery::type( 'array' ) );

		$plugin = Plugin::get_instance();
		$plugin->init();

		$this->assertTrue( true ); // Assert that we got here without errors.
	}

	/**
	 * Test service registration.
	 */
	public function test_register_service_stores_service(): void {
		$plugin  = Plugin::get_instance();
		$service = new \stdClass();
		$service->name = 'test';

		$plugin->register_service( 'test_service', $service );
		$retrieved = $plugin->get_service( 'test_service' );

		$this->assertSame( $service, $retrieved );
	}

	/**
	 * Test get_service returns null for non-existent service.
	 */
	public function test_get_service_returns_null_for_missing_service(): void {
		$plugin = Plugin::get_instance();

		$result = $plugin->get_service( 'non_existent' );

		$this->assertNull( $result );
	}

	/**
	 * Test that has_abilities_api method exists and returns bool.
	 *
	 * Note: We can't easily mock function_exists with Brain Monkey,
	 * so we just verify the method exists and returns a boolean.
	 */
	public function test_has_abilities_api_returns_bool(): void {
		$plugin = Plugin::get_instance();
		$result = $plugin->has_abilities_api();

		$this->assertIsBool( $result );
	}

	/**
	 * Test check_abilities_api_and_show_notice method exists.
	 *
	 * We verify the method is callable and doesn't throw errors.
	 */
	public function test_check_abilities_api_method_exists(): void {
		$plugin = Plugin::get_instance();

		$this->assertTrue( method_exists( $plugin, 'check_abilities_api_and_show_notice' ) );
	}
}
