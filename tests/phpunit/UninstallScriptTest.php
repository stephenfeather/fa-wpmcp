<?php
/**
 * Tests for uninstall.php execution paths.
 *
 * @package FAWpmcp\Tests
 */

declare(strict_types=1);

namespace FAWpmcp\Tests;

use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Execute uninstall.php to cover cleanup branches.
 */
final class UninstallScriptTest extends TestCase {

	use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

	protected function setUp(): void {
		parent::setUp();
		\Brain\Monkey\setUp();
	}

	protected function tearDown(): void {
		\Brain\Monkey\tearDown();
		unset( $GLOBALS['wpdb'] );
		parent::tearDown();
	}

	/**
	 * Test uninstall returns early when preservation constant is set.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 *
	 * @return void
	 */
	public function test_uninstall_returns_when_preserve_enabled(): void {
		define( 'WP_UNINSTALL_PLUGIN', true );
		define( 'FA_WPMCP_PRESERVE_DATA_ON_UNINSTALL', true );

		$wpdb = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->options = 'wp_options';
		$wpdb->usermeta = 'wp_usermeta';
		$wpdb->sitemeta = 'wp_sitemeta';
		$wpdb->shouldNotReceive( 'query' );
		$GLOBALS['wpdb'] = $wpdb;

		Functions\expect( 'wp_clear_scheduled_hook' )->never();
		Functions\expect( 'is_multisite' )->never();

		require dirname( __DIR__, 2 ) . '/uninstall.php';

		$this->assertTrue( true );
	}

	/**
	 * Test uninstall cleanup on single site without Action Scheduler.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 *
	 * @return void
	 */
	public function test_uninstall_cleans_single_site_without_action_scheduler(): void {
		define( 'WP_UNINSTALL_PLUGIN', true );

		$wpdb = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->options = 'wp_options';
		$wpdb->usermeta = 'wp_usermeta';
		$wpdb->sitemeta = 'wp_sitemeta';

		$wpdb->shouldReceive( 'query' )
			->once()
			->with( 'DROP TABLE IF EXISTS wp_fa_wpmcp_activity_log' )
			->andReturn( true );
		$wpdb->shouldReceive( 'query' )
			->once()
			->with( 'DROP TABLE IF EXISTS wp_fa_wpmcp_webhook_queue' )
			->andReturn( true );
		$wpdb->shouldReceive( 'query' )
			->once()
			->with( "DELETE FROM wp_options WHERE option_name LIKE 'fa_wpmcp_%'" )
			->andReturn( true );
		$wpdb->shouldReceive( 'query' )
			->once()
			->with( "DELETE FROM wp_options WHERE option_name LIKE '_transient_fa_wpmcp_%'" )
			->andReturn( true );
		$wpdb->shouldReceive( 'query' )
			->once()
			->with( "DELETE FROM wp_options WHERE option_name LIKE '_transient_timeout_fa_wpmcp_%'" )
			->andReturn( true );
		$wpdb->shouldReceive( 'query' )
			->once()
			->with( "DELETE FROM wp_usermeta WHERE meta_key LIKE 'fa_wpmcp_%'" )
			->andReturn( true );

		$GLOBALS['wpdb'] = $wpdb;

		Functions\expect( 'is_multisite' )
			->once()
			->andReturn( false );

		Functions\expect( 'wp_clear_scheduled_hook' )
			->once()
			->with( 'fa_wpmcp_process_webhook_queue' )
			->andReturn( 1 );
		Functions\expect( 'wp_clear_scheduled_hook' )
			->once()
			->with( 'fa_wpmcp_cleanup_old_logs' )
			->andReturn( 1 );

		require dirname( __DIR__, 2 ) . '/uninstall.php';

		$this->assertTrue( true );
	}

	/**
	 * Test uninstall cleanup on multisite with Action Scheduler available.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 *
	 * @return void
	 */
	public function test_uninstall_cleans_multisite_with_action_scheduler(): void {
		define( 'WP_UNINSTALL_PLUGIN', true );

		// Define Action Scheduler function so function_exists() returns true.
		if ( ! function_exists( 'as_unschedule_all_actions' ) ) {
			eval(
				'namespace { function as_unschedule_all_actions( $hook ) { $GLOBALS["as_calls"][] = $hook; } }'
			);
		}
		$GLOBALS['as_calls'] = array();

		$wpdb = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->options = 'wp_options';
		$wpdb->usermeta = 'wp_usermeta';
		$wpdb->sitemeta = 'wp_sitemeta';

		$wpdb->shouldReceive( 'query' )
			->once()
			->with( 'DROP TABLE IF EXISTS wp_fa_wpmcp_activity_log' )
			->andReturn( true );
		$wpdb->shouldReceive( 'query' )
			->once()
			->with( 'DROP TABLE IF EXISTS wp_fa_wpmcp_webhook_queue' )
			->andReturn( true );
		$wpdb->shouldReceive( 'query' )
			->once()
			->with( "DELETE FROM wp_options WHERE option_name LIKE 'fa_wpmcp_%'" )
			->andReturn( true );
		$wpdb->shouldReceive( 'query' )
			->once()
			->with( "DELETE FROM wp_options WHERE option_name LIKE '_transient_fa_wpmcp_%'" )
			->andReturn( true );
		$wpdb->shouldReceive( 'query' )
			->once()
			->with( "DELETE FROM wp_options WHERE option_name LIKE '_transient_timeout_fa_wpmcp_%'" )
			->andReturn( true );
		$wpdb->shouldReceive( 'query' )
			->once()
			->with( "DELETE FROM wp_usermeta WHERE meta_key LIKE 'fa_wpmcp_%'" )
			->andReturn( true );
		$wpdb->shouldReceive( 'query' )
			->once()
			->with( "DELETE FROM wp_sitemeta WHERE meta_key LIKE 'fa_wpmcp_%'" )
			->andReturn( true );

		$GLOBALS['wpdb'] = $wpdb;

		Functions\expect( 'is_multisite' )
			->once()
			->andReturn( true );

		Functions\expect( 'wp_clear_scheduled_hook' )
			->once()
			->with( 'fa_wpmcp_process_webhook_queue' )
			->andReturn( 1 );
		Functions\expect( 'wp_clear_scheduled_hook' )
			->once()
			->with( 'fa_wpmcp_cleanup_old_logs' )
			->andReturn( 1 );

		require dirname( __DIR__, 2 ) . '/uninstall.php';

		$this->assertSame(
			array( 'fa_wpmcp_process_webhook', 'fa_wpmcp_retry_webhook' ),
			$GLOBALS['as_calls']
		);
	}
}
