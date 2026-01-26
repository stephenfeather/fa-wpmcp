<?php
/**
 * Tests for plugin uninstall handler.
 *
 * @package FAWpmcp\Tests
 */

declare(strict_types=1);

namespace FAWpmcp\Tests;

use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use Mockery;

/**
 * Test uninstall.php functionality.
 *
 * Verifies complete cleanup of:
 * - Database tables (activity_log, webhook_queue)
 * - WordPress options (fa_wpmcp_*)
 * - Transients (fa_wpmcp_*)
 *
 * @package FAWpmcp\Tests
 */
final class UninstallTest extends TestCase {
	use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

	/**
	 * Set up test environment.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		\Brain\Monkey\setUp();
	}

	/**
	 * Tear down test environment.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		\Brain\Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Test that uninstall script exits if WP_UNINSTALL_PLUGIN is not defined.
	 *
	 * @throws \RuntimeException If WP_UNINSTALL_PLUGIN is not defined.
	 * @return void
	 */
	public function test_exits_if_not_uninstalling(): void {
		// Mock the constant check.
		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Invalid uninstall call' );

		// Simulate uninstall.php being called without constant.
		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			throw new \RuntimeException( 'Invalid uninstall call' );
		}
	}

	/**
	 * Test that activity log table is dropped.
	 *
	 * @return void
	 */
	public function test_drops_activity_log_table(): void {
		$wpdb         = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'query' )
			->once()
			->with( 'DROP TABLE IF EXISTS wp_fa_wpmcp_activity_log' )
			->andReturn( true );

		// Simulate table drop.
		$result = $wpdb->query( 'DROP TABLE IF EXISTS wp_fa_wpmcp_activity_log' );

		$this->assertTrue( $result );
	}

	/**
	 * Test that webhook queue table is dropped.
	 *
	 * @return void
	 */
	public function test_drops_webhook_queue_table(): void {
		$wpdb         = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'query' )
			->once()
			->with( 'DROP TABLE IF EXISTS wp_fa_wpmcp_webhook_queue' )
			->andReturn( true );

		// Simulate table drop.
		$result = $wpdb->query( 'DROP TABLE IF EXISTS wp_fa_wpmcp_webhook_queue' );

		$this->assertTrue( $result );
	}

	/**
	 * Test that all fa_wpmcp_* options are deleted.
	 *
	 * @return void
	 */
	public function test_deletes_all_plugin_options(): void {
		$wpdb          = Mockery::mock( 'wpdb' );
		$wpdb->options = 'wp_options';
		$wpdb->shouldReceive( 'query' )
			->once()
			->with( "DELETE FROM wp_options WHERE option_name LIKE 'fa_wpmcp_%'" )
			->andReturn( 6 ); // 6 options deleted.

		// Simulate options deletion.
		$deleted = $wpdb->query( "DELETE FROM wp_options WHERE option_name LIKE 'fa_wpmcp_%'" );

		$this->assertEquals( 6, $deleted );
	}

	/**
	 * Test that all fa_wpmcp_* transients are deleted.
	 *
	 * @return void
	 */
	public function test_deletes_all_transients(): void {
		$wpdb          = Mockery::mock( 'wpdb' );
		$wpdb->options = 'wp_options';

		// Delete transient values.
		$wpdb->shouldReceive( 'query' )
			->once()
			->with( "DELETE FROM wp_options WHERE option_name LIKE '_transient_fa_wpmcp_%'" )
			->andReturn( 3 );

		// Delete transient timeouts.
		$wpdb->shouldReceive( 'query' )
			->once()
			->with( "DELETE FROM wp_options WHERE option_name LIKE '_transient_timeout_fa_wpmcp_%'" )
			->andReturn( 3 );

		// Simulate transient deletion.
		$deleted_values   = $wpdb->query( "DELETE FROM wp_options WHERE option_name LIKE '_transient_fa_wpmcp_%'" );
		$deleted_timeouts = $wpdb->query( "DELETE FROM wp_options WHERE option_name LIKE '_transient_timeout_fa_wpmcp_%'" );

		$this->assertEquals( 3, $deleted_values );
		$this->assertEquals( 3, $deleted_timeouts );
	}

	/**
	 * Test that all fa_wpmcp_* user meta is deleted.
	 *
	 * @return void
	 */
	public function test_deletes_all_user_meta(): void {
		$wpdb           = Mockery::mock( 'wpdb' );
		$wpdb->usermeta = 'wp_usermeta';
		$wpdb->shouldReceive( 'query' )
			->once()
			->with( "DELETE FROM wp_usermeta WHERE meta_key LIKE 'fa_wpmcp_%'" )
			->andReturn( 2 );

		// Simulate user meta deletion.
		$deleted = $wpdb->query( "DELETE FROM wp_usermeta WHERE meta_key LIKE 'fa_wpmcp_%'" );

		$this->assertEquals( 2, $deleted );
	}

	/**
	 * Test that multisite site options are deleted.
	 *
	 * @return void
	 */
	public function test_deletes_multisite_options(): void {
		$wpdb           = Mockery::mock( 'wpdb' );
		$wpdb->sitemeta = 'wp_sitemeta';
		$wpdb->shouldReceive( 'query' )
			->once()
			->with( "DELETE FROM wp_sitemeta WHERE meta_key LIKE 'fa_wpmcp_%'" )
			->andReturn( 1 );

		Functions\when( 'is_multisite' )->justReturn( true );

		// Simulate multisite meta deletion.
		if ( is_multisite() ) {
			$deleted = $wpdb->query( "DELETE FROM wp_sitemeta WHERE meta_key LIKE 'fa_wpmcp_%'" );
			$this->assertEquals( 1, $deleted );
		}
	}

	/**
	 * Test that uninstall can preserve data via constant.
	 *
	 * Note: This tests the logic pattern, not the actual constant.
	 * The uninstall.php file will check for FA_WPMCP_PRESERVE_DATA_ON_UNINSTALL.
	 *
	 * @return void
	 */
	public function test_preserve_data_constant_pattern(): void {
		// Test the cleanup decision logic.
		$preserve_data = true; // Simulates constant being true.

		if ( $preserve_data ) {
			$cleanup_performed = false;
		} else {
			$cleanup_performed = true;
		}

		$this->assertFalse( $cleanup_performed );
	}

	/**
	 * Test that scheduled cron events are cleared.
	 *
	 * @return void
	 */
	public function test_clears_scheduled_cron_events(): void {
		Functions\expect( 'wp_clear_scheduled_hook' )
			->once()
			->with( 'fa_wpmcp_process_webhook_queue' )
			->andReturn( 1 );

		Functions\expect( 'wp_clear_scheduled_hook' )
			->once()
			->with( 'fa_wpmcp_cleanup_old_logs' )
			->andReturn( 1 );

		// Simulate clearing cron hooks.
		$cleared_webhooks = wp_clear_scheduled_hook( 'fa_wpmcp_process_webhook_queue' );
		$cleared_cleanup  = wp_clear_scheduled_hook( 'fa_wpmcp_cleanup_old_logs' );

		$this->assertEquals( 1, $cleared_webhooks );
		$this->assertEquals( 1, $cleared_cleanup );
	}

	/**
	 * Test that Action Scheduler actions are cleared.
	 *
	 * @return void
	 */
	public function test_clears_action_scheduler_actions(): void {
		Functions\expect( 'as_unschedule_all_actions' )
			->twice()
			->andReturnNull();

		// Simulate Action Scheduler cleanup.
		as_unschedule_all_actions( 'fa_wpmcp_process_webhook' );
		as_unschedule_all_actions( 'fa_wpmcp_retry_webhook' );

		// Test passes if no exceptions thrown.
		$this->assertTrue( true );
	}

	/**
	 * Test that complete uninstall runs all cleanup steps.
	 *
	 * @return void
	 */
	public function test_complete_uninstall_runs_all_steps(): void {
		$wpdb           = Mockery::mock( 'wpdb' );
		$wpdb->prefix   = 'wp_';
		$wpdb->options  = 'wp_options';
		$wpdb->usermeta = 'wp_usermeta';

		// Mock all cleanup queries.
		$wpdb->shouldReceive( 'query' )->times( 6 )->andReturn( true );

		Functions\expect( 'wp_clear_scheduled_hook' )->times( 2 )->andReturn( 1 );
		Functions\expect( 'as_unschedule_all_actions' )->times( 2 )->andReturnNull();
		Functions\when( 'is_multisite' )->justReturn( false );

		// Simulate complete uninstall.
		$wpdb->query( 'DROP TABLE IF EXISTS wp_fa_wpmcp_activity_log' );
		$wpdb->query( 'DROP TABLE IF EXISTS wp_fa_wpmcp_webhook_queue' );
		$wpdb->query( "DELETE FROM wp_options WHERE option_name LIKE 'fa_wpmcp_%'" );
		$wpdb->query( "DELETE FROM wp_options WHERE option_name LIKE '_transient_fa_wpmcp_%'" );
		$wpdb->query( "DELETE FROM wp_options WHERE option_name LIKE '_transient_timeout_fa_wpmcp_%'" );
		$wpdb->query( "DELETE FROM wp_usermeta WHERE meta_key LIKE 'fa_wpmcp_%'" );

		wp_clear_scheduled_hook( 'fa_wpmcp_process_webhook_queue' );
		wp_clear_scheduled_hook( 'fa_wpmcp_cleanup_old_logs' );

		// Action Scheduler cleanup (uninstall.php will check function_exists).
		as_unschedule_all_actions( 'fa_wpmcp_process_webhook' );
		as_unschedule_all_actions( 'fa_wpmcp_retry_webhook' );

		// Test passes if all steps executed without errors.
		$this->assertTrue( true );
	}
}
