<?php

/**
 * GetCronEventAbility - retrieves a specific cron event by hook name.
 *
 * @package FAWpmcp\Abilities\Cron
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Cron;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Ability to retrieve a specific WordPress cron event by hook name.
 *
 * Returns all scheduled instances of the specified hook with their details.
 *
 * @package FAWpmcp\Abilities\Cron
 */
final class GetCronEventAbility extends AbstractAbility {

	/**
	 * Get the unique ability name.
	 *
	 * @return string Ability name.
	 */
	public function getName(): string {
		return 'fa-wpmcp/get-cron-event';
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
		return 'Get Cron Event';
	}

	/**
	 * Get the ability description.
	 *
	 * @return string Description.
	 */
	public function getDescription(): string {
		return 'Retrieve a specific WordPress cron event by hook name. Returns all scheduled instances of the hook.';
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
					'description' => 'The hook name to retrieve.',
				),
			),
			'required'   => array( 'hook' ),
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
				'found'  => array(
					'type'        => 'boolean',
					'description' => 'Whether the hook was found.',
				),
				'events' => array(
					'type'        => 'array',
					'description' => 'List of scheduled events for this hook.',
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
	 * @return array<string, mixed> Cron event details.
	 */
	public function doExecute( array $input ): array {
		$hook       = (string) $input['hook'];
		$cron_array = _get_cron_array();

		if ( empty( $cron_array ) || ! is_array( $cron_array ) ) {
			return array(
				'found'  => false,
				'events' => array(),
			);
		}

		$events = array();

		foreach ( $cron_array as $timestamp => $hooks ) {
			if ( isset( $hooks[ $hook ] ) ) {
				foreach ( $hooks[ $hook ] as $sig => $event_data ) {
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
			'found'  => ! empty( $events ),
			'events' => $events,
		);
	}
}
