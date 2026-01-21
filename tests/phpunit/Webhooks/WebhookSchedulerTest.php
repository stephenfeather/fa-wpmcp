<?php
/**
 * Tests for WebhookScheduler.
 *
 * @package FAWpmcp\Tests\Webhooks
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Webhooks;

use Brain\Monkey\Functions;
use FAWpmcp\Webhooks\WebhookConfig;
use FAWpmcp\Webhooks\WebhookManager;
use FAWpmcp\Webhooks\WebhookQueue;
use FAWpmcp\Webhooks\WebhookScheduler;
use FAWpmcp\Webhooks\WebhookSender;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test WebhookScheduler behavior.
 */
final class WebhookSchedulerTest extends TestCase {
	use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

	protected function setUp(): void {
		parent::setUp();
		\Brain\Monkey\setUp();
		$GLOBALS['as_has_scheduled_action_return'] = false;
		$GLOBALS['as_schedule_recurring_action_calls'] = array();
		$GLOBALS['as_unschedule_all_actions_calls'] = array();
	}

	protected function tearDown(): void {
		\Brain\Monkey\tearDown();
		unset(
			$GLOBALS['as_has_scheduled_action_return'],
			$GLOBALS['as_schedule_recurring_action_calls'],
			$GLOBALS['as_unschedule_all_actions_calls']
		);
		parent::tearDown();
	}

	/**
	 * Test init registers hooks.
	 *
	 * @return void
	 */
	public function test_init_registers_hooks(): void {
		$manager = new WebhookManager(
			Mockery::mock( WebhookQueue::class ),
			Mockery::mock( WebhookSender::class ),
			Mockery::mock( WebhookConfig::class ),
		);
		$scheduler = new WebhookScheduler( $manager );

		Functions\expect( 'add_action' )
			->once()
			->with( 'fa_wpmcp_process_webhook_queue', Mockery::type( 'array' ) );

		Functions\expect( 'add_filter' )
			->once()
			->with( 'cron_schedules', Mockery::type( 'array' ) );

		Functions\expect( 'add_action' )
			->once()
			->with( 'init', Mockery::type( 'array' ) );

		$scheduler->init();
	}

	/**
	 * Test schedule_recurring_job uses WP-Cron when Action Scheduler unavailable.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 *
	 * @return void
	 */
	public function test_schedule_recurring_job_uses_wp_cron_when_unavailable(): void {
		$manager = new WebhookManager(
			Mockery::mock( WebhookQueue::class ),
			Mockery::mock( WebhookSender::class ),
			Mockery::mock( WebhookConfig::class ),
		);
		$scheduler = new WebhookScheduler( $manager );

		Functions\expect( 'wp_next_scheduled' )
			->once()
			->with( 'fa_wpmcp_process_webhook_queue' )
			->andReturn( false );

		Functions\expect( 'wp_schedule_event' )
			->once()
			->with( Mockery::type( 'int' ), 'five_minutes', 'fa_wpmcp_process_webhook_queue' )
			->andReturn( true );

		$scheduler->schedule_recurring_job();
	}

	/**
	 * Test schedule_with_action_scheduler schedules recurring action.
	 *
	 * @return void
	 */
	public function test_schedule_with_action_scheduler_schedules_action(): void {
		$this->ensure_action_scheduler_stubs();
		$GLOBALS['as_has_scheduled_action_return'] = false;

		$manager = new WebhookManager(
			Mockery::mock( WebhookQueue::class ),
			Mockery::mock( WebhookSender::class ),
			Mockery::mock( WebhookConfig::class ),
		);
		$scheduler = new WebhookScheduler( $manager );

		$scheduler->schedule_recurring_job();

		$this->assertCount( 1, $GLOBALS['as_schedule_recurring_action_calls'] );
		$this->assertSame(
			array(
				'fa_wpmcp_process_webhook_queue',
				array(),
				'fa-wpmcp-webhooks',
				true,
			),
			array_slice( $GLOBALS['as_schedule_recurring_action_calls'][0], 2 )
		);
	}

	/**
	 * Test schedule_with_action_scheduler exits when already scheduled.
	 *
	 * @return void
	 */
	public function test_schedule_with_action_scheduler_skips_when_scheduled(): void {
		$this->ensure_action_scheduler_stubs();
		$GLOBALS['as_has_scheduled_action_return'] = true;

		$manager = new WebhookManager(
			Mockery::mock( WebhookQueue::class ),
			Mockery::mock( WebhookSender::class ),
			Mockery::mock( WebhookConfig::class ),
		);
		$scheduler = new WebhookScheduler( $manager );

		$scheduler->schedule_recurring_job();

		$this->assertSame( array(), $GLOBALS['as_schedule_recurring_action_calls'] );
	}

	/**
	 * Test register_cron_interval adds missing schedule.
	 *
	 * @return void
	 */
	public function test_register_cron_interval_adds_schedule(): void {
		Functions\expect( '__' )
			->once()
			->with( 'Every 5 Minutes', 'fa-wpmcp' )
			->andReturn( 'Every 5 Minutes' );

		$manager = new WebhookManager(
			Mockery::mock( WebhookQueue::class ),
			Mockery::mock( WebhookSender::class ),
			Mockery::mock( WebhookConfig::class ),
		);
		$scheduler = new WebhookScheduler( $manager );

		$result = $scheduler->register_cron_interval( array() );

		$this->assertArrayHasKey( 'five_minutes', $result );
		$this->assertSame( 300, $result['five_minutes']['interval'] );
	}

	/**
	 * Test unschedule removes action scheduler and cron jobs.
	 *
	 * @return void
	 */
	public function test_unschedule_removes_jobs(): void {
		$this->ensure_action_scheduler_stubs();
		Functions\expect( 'wp_next_scheduled' )
			->once()
			->with( 'fa_wpmcp_process_webhook_queue' )
			->andReturn( 123 );

		Functions\expect( 'wp_unschedule_event' )
			->once()
			->with( 123, 'fa_wpmcp_process_webhook_queue' )
			->andReturn( true );

		$manager = new WebhookManager(
			Mockery::mock( WebhookQueue::class ),
			Mockery::mock( WebhookSender::class ),
			Mockery::mock( WebhookConfig::class ),
		);
		$scheduler = new WebhookScheduler( $manager );

		$scheduler->unschedule();

		$this->assertSame(
			array(
				array( 'fa_wpmcp_process_webhook_queue', array(), 'fa-wpmcp-webhooks' ),
			),
			$GLOBALS['as_unschedule_all_actions_calls']
		);
	}

	/**
	 * Define Action Scheduler stubs for function_exists checks.
	 *
	 * @return void
	 */
	private function ensure_action_scheduler_stubs(): void {
		if ( ! function_exists( 'as_has_scheduled_action' ) ) {
			eval(
				'namespace { function as_has_scheduled_action() { return $GLOBALS["as_has_scheduled_action_return"] ?? false; } }'
			);
		}

		if ( ! function_exists( 'as_schedule_recurring_action' ) ) {
			eval(
				'namespace { function as_schedule_recurring_action() { $GLOBALS["as_schedule_recurring_action_calls"][] = func_get_args(); return 1; } }'
			);
		}

		if ( ! function_exists( 'as_unschedule_all_actions' ) ) {
			eval(
				'namespace { function as_unschedule_all_actions() { $GLOBALS["as_unschedule_all_actions_calls"][] = func_get_args(); return null; } }'
			);
		}
	}
}
