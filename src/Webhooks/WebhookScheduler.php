<?php

/**
 * Webhook queue scheduler.
 *
 * @package FAWpmcp
 */

declare(strict_types=1);

namespace FAWpmcp\Webhooks;

/**
 * Webhook queue scheduler.
 *
 * Schedules recurring background jobs to process webhook queue.
 * Uses Action Scheduler when available, falls back to WP-Cron.
 */
final class WebhookScheduler {

	private const ACTION_HOOK   = 'fa_wpmcp_process_webhook_queue';
	private const ACTION_GROUP  = 'fa-wpmcp-webhooks';
	private const CRON_INTERVAL = 'five_minutes';

	/**
	 * Constructor.
	 *
	 * @param WebhookManager $manager Webhook manager.
	 */
	public function __construct(
		private readonly WebhookManager $manager
	) {
	}

	/**
	 * Initialize scheduler.
	 *
	 * Sets up recurring job to process webhook queue.
	 * Uses Action Scheduler if available, WP-Cron otherwise.
	 */
	public function init(): void {
		// Register action hook for queue processing.
		add_action( self::ACTION_HOOK, array( $this, 'processQueue' ) );

		// Register custom cron interval.
		add_filter( 'cron_schedules', array( $this, 'registerCronInterval' ) );

		// Schedule recurring job.
		add_action( 'init', array( $this, 'scheduleRecurringJob' ) );
	}

	/**
	 * Process webhook queue.
	 *
	 * Called by Action Scheduler or WP-Cron.
	 */
	public function processQueue(): void {
		$this->manager->processQueue();
	}

	/**
	 * Schedule recurring job.
	 *
	 * Uses Action Scheduler if available, WP-Cron otherwise.
	 */
	public function scheduleRecurringJob(): void {
		if ( $this->isActionSchedulerAvailable() ) {
			$this->scheduleWithActionScheduler();
		} else {
			$this->scheduleWithWpCron();
		}
	}

	/**
	 * Check if Action Scheduler is available.
	 *
	 * @return bool True if Action Scheduler is available.
	 */
	private function isActionSchedulerAvailable(): bool {
		return function_exists( 'as_has_scheduled_action' )
			&& function_exists( 'as_schedule_recurring_action' );
	}

	/**
	 * Schedule with Action Scheduler.
	 *
	 * Schedules recurring action every 5 minutes.
	 */
	private function scheduleWithActionScheduler(): void {
		// Check if already scheduled.
		if ( as_has_scheduled_action( self::ACTION_HOOK, array(), self::ACTION_GROUP ) ) {
			return;
		}

		// Schedule recurring action every 5 minutes.
		as_schedule_recurring_action(
			time(),
			300, // 5 minutes in seconds.
			self::ACTION_HOOK,
			array(),
			self::ACTION_GROUP,
			true // Unique.
		);
	}

	/**
	 * Schedule with WP-Cron.
	 *
	 * Schedules WP-Cron event every 5 minutes.
	 */
	private function scheduleWithWpCron(): void {
		// Check if already scheduled.
		if ( wp_next_scheduled( self::ACTION_HOOK ) ) {
			return;
		}

		// Schedule recurring cron event.
		wp_schedule_event( time(), self::CRON_INTERVAL, self::ACTION_HOOK );
	}

	/**
	 * Register custom cron interval.
	 *
	 * Adds 5-minute interval for WP-Cron fallback.
	 *
	 * @param array<string, array<string, mixed>> $schedules Existing schedules.
	 * @return array<string, array<string, mixed>> Modified schedules.
	 */
	public function registerCronInterval( array $schedules ): array {
		if ( ! isset( $schedules[ self::CRON_INTERVAL ] ) ) {
			$schedules[ self::CRON_INTERVAL ] = array(
				'interval' => 300,
				'display'  => __( 'Every 5 Minutes', 'fa-wpmcp' ),
			);
		}

		return $schedules;
	}

	/**
	 * Unschedule all jobs.
	 *
	 * Removes both Action Scheduler and WP-Cron jobs.
	 * Called on plugin deactivation.
	 */
	public function unschedule(): void {
		// Unschedule Action Scheduler actions.
		if ( $this->isActionSchedulerAvailable() ) {
			as_unschedule_all_actions( self::ACTION_HOOK, array(), self::ACTION_GROUP );
		}

		// Unschedule WP-Cron events.
		$timestamp = wp_next_scheduled( self::ACTION_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::ACTION_HOOK );
		}
	}
}
