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
	}

	protected function tearDown(): void {
		\Brain\Monkey\tearDown();
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
	 * Test scheduleRecurringJob uses WP-Cron when Action Scheduler unavailable.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 *
	 * @return void
	 */
	public function test_scheduleRecurringJob_uses_wp_cron_when_unavailable(): void {
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

		$scheduler->scheduleRecurringJob();
	}

	/**
	 * Test schedule_with_action_scheduler schedules recurring action.
	 *
	 * @return void
	 */
	public function test_schedule_with_action_scheduler_schedules_action(): void {
		Functions\expect( 'as_has_scheduled_action' )
			->once()
			->andReturn( false );

		Functions\expect( 'as_schedule_recurring_action' )
			->once()
			->with(
				Mockery::type( 'int' ),
				300,
				'fa_wpmcp_process_webhook_queue',
				array(),
				'fa-wpmcp-webhooks',
				true
			)
			->andReturn( 1 );

		$manager = new WebhookManager(
			Mockery::mock( WebhookQueue::class ),
			Mockery::mock( WebhookSender::class ),
			Mockery::mock( WebhookConfig::class ),
		);
		$scheduler = new WebhookScheduler( $manager );

		$scheduler->scheduleRecurringJob();

		$this->assertTrue( true, 'Action Scheduler scheduling invoked' );
	}

	/**
	 * Test schedule_with_action_scheduler exits when already scheduled.
	 *
	 * @return void
	 */
	public function test_schedule_with_action_scheduler_skips_when_scheduled(): void {
		Functions\expect( 'as_has_scheduled_action' )
			->once()
			->andReturn( true );

		Functions\expect( 'as_schedule_recurring_action' )->never();

		$manager = new WebhookManager(
			Mockery::mock( WebhookQueue::class ),
			Mockery::mock( WebhookSender::class ),
			Mockery::mock( WebhookConfig::class ),
		);
		$scheduler = new WebhookScheduler( $manager );

		$scheduler->scheduleRecurringJob();

		$this->assertTrue( true, 'No scheduling when already scheduled' );
	}

	/**
	 * Test registerCronInterval adds missing schedule.
	 *
	 * @return void
	 */
	public function test_registerCronInterval_adds_schedule(): void {
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

		$result = $scheduler->registerCronInterval( array() );

		$this->assertArrayHasKey( 'five_minutes', $result );
		$this->assertSame( 300, $result['five_minutes']['interval'] );
	}

	/**
	 * Test unschedule removes action scheduler and cron jobs.
	 *
	 * @return void
	 */
	public function test_unschedule_removes_jobs(): void {
		Functions\expect( 'as_unschedule_all_actions' )
			->once()
			->with( 'fa_wpmcp_process_webhook_queue', array(), 'fa-wpmcp-webhooks' );
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
		$this->assertTrue( true, 'Unschedule hooks invoked' );
	}
}
