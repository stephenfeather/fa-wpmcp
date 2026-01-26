<?php
/**
 * ListCronEventsAbility - lists all scheduled WP-Cron events.
 *
 * @package FAWpmcp\Abilities\Cron
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Cron;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Ability to list all scheduled WordPress cron events.
 *
 * Returns an array of cron events with hook names, timestamps, schedules, and arguments.
 * Supports optional filtering by hook name pattern.
 *
 * @package FAWpmcp\Abilities\Cron
 */
final class ListCronEventsAbility extends AbstractAbility {
	/**
	 * Get the unique ability name.
	 *
	 * @return string Ability name.
	 */
	public function getName(): string {
		return 'fa-wpmcp/list-cron-events';
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
		return 'List Cron Events';
	}

	/**
	 * Get the ability description.
	 *
	 * @return string Description.
	 */
	public function getDescription(): string {
		return 'List all scheduled WordPress cron events with optional hook name filtering.';
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
				'hook' => array(
					'type'        => 'string',
					'description' => 'Optional hook name pattern to filter events (partial match).',
				),
			),
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
				'events' => array(
					'type'        => 'array',
					'description' => 'List of cron events.',
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'hook'      => array( 'type' => 'string' ),
							'timestamp' => array( 'type' => 'integer' ),
							'schedule'  => array( 'type' => 'string' ),
							'args'      => array( 'type' => 'array' ),
						),
					),
				),
				'total'  => array(
					'type'        => 'integer',
					'description' => 'Total number of events found.',
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
	 * Execute the ability.
	 *
	 * @param array<string, mixed> $input Validated input data.
	 * @return array<string, mixed> List of cron events.
	 */
	public function doExecute( array $input ): array {
		$hook_filter = $input['hook'] ?? '';
		$cron_array  = _get_cron_array();

		if ( empty( $cron_array ) || ! is_array( $cron_array ) ) {
			return array(
				'events' => array(),
				'total'  => 0,
			);
		}

		$events = array();

		foreach ( $cron_array as $timestamp => $hooks ) {
			foreach ( $hooks as $hook => $events_data ) {
				// Apply hook filter if provided.
				if ( ! empty( $hook_filter ) && strpos( $hook, $hook_filter ) === false ) {
					continue;
				}

				foreach ( $events_data as $sig => $event_data ) {
					$events[] = array(
						'hook'      => $hook,
						'timestamp' => (int) $timestamp,
						'schedule'  => $event_data['schedule'] ?? '',
						'args'      => $event_data['args'] ?? array(),
					);
				}
			}
		}

		return array(
			'events' => $events,
			'total'  => count( $events ),
		);
	}
}
