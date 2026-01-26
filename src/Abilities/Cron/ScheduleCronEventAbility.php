<?php
/**
 * ScheduleCronEventAbility - schedules a new WP-Cron event.
 *
 * @package FAWpmcp\Abilities\Cron
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Cron;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Ability to schedule a new WordPress cron event.
 *
 * Supports both recurring events (with recurrence) and single events.
 *
 * @package FAWpmcp\Abilities\Cron
 */
final class ScheduleCronEventAbility extends AbstractAbility {
	/**
	 * Get the unique ability name.
	 *
	 * @return string Ability name.
	 */
	public function getName(): string {
		return 'fa-wpmcp/schedule-cron-event';
	}

	/**
	 * Get the ability category.
	 *
	 * @return string Category name.
	 */
	public function getCategory(): string {
		return 'cron';
	}

	/**
	 * Get the human-readable label.
	 *
	 * @return string Ability label.
	 */
	public function getLabel(): string {
		return 'Schedule Cron Event';
	}

	/**
	 * Get the ability description.
	 *
	 * @return string Description.
	 */
	public function getDescription(): string {
		return 'Schedule a new WordPress cron event. Supports recurring events (with recurrence) or single events.';
	}

	/**
	 * Get the input schema.
	 *
	 * @return array<string, mixed> JSON Schema array.
	 */
	public function getInputSchema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'hook'       => array(
					'type'        => 'string',
					'description' => 'The hook name for the cron event.',
				),
				'timestamp'  => array(
					'type'        => 'integer',
					'description' => 'Unix timestamp when the event should first run.',
				),
				'recurrence' => array(
					'type'        => 'string',
					'description' => 'How often the event should repeat (e.g., hourly, twicedaily, daily). Omit for single events.',
				),
				'args'       => array(
					'type'        => 'array',
					'description' => 'Arguments to pass to the hook callback.',
					'default'     => array(),
				),
			),
			'required'   => array( 'hook', 'timestamp' ),
		);
	}

	/**
	 * Get the output schema.
	 *
	 * @return array<string, mixed> JSON Schema array.
	 */
	public function getOutputSchema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'success' => array(
					'type'        => 'boolean',
					'description' => 'Whether the event was scheduled successfully.',
				),
				'error'   => array(
					'type'        => 'string',
					'description' => 'Error message if scheduling failed.',
				),
			),
		);
	}

	/**
	 * Get the required WordPress capability.
	 *
	 * @return string WordPress capability name.
	 */
	public function getRequiredCapability(): string {
		return 'manage_options';
	}

	/**
	 * Get the operation type.
	 *
	 * @return string 'write' for this ability.
	 */
	public function getOperationType(): string {
		return 'write';
	}

	/**
	 * Execute the ability.
	 *
	 * @param array<string, mixed> $input Validated input data.
	 * @return array<string, mixed> Schedule result.
	 */
	public function doExecute( array $input ): array {
		$hook       = (string) $input['hook'];
		$timestamp  = (int) $input['timestamp'];
		$recurrence = $input['recurrence'] ?? '';
		$args       = $input['args'] ?? array();

		// Schedule recurring or single event.
		if ( ! empty( $recurrence ) ) {
			$result = wp_schedule_event( $timestamp, $recurrence, $hook, $args );
		} else {
			$result = wp_schedule_single_event( $timestamp, $hook, $args );
		}

		// Check for errors.
		if ( is_wp_error( $result ) ) {
			return array(
				'success' => false,
				'error'   => $result->get_error_message(),
			);
		}

		return array(
			'success' => true,
		);
	}
}
