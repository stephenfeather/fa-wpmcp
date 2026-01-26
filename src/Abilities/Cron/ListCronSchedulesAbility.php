<?php

/**
 * ListCronSchedulesAbility - lists available cron recurrence schedules.
 *
 * @package FAWpmcp\Abilities\Cron
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Cron;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Ability to list available WordPress cron recurrence schedules.
 *
 * Returns the registered cron schedules (hourly, daily, etc.) that can be used when scheduling events.
 *
 * @package FAWpmcp\Abilities\Cron
 */
final class ListCronSchedulesAbility extends AbstractAbility {

	/**
	 * Get the unique ability name.
	 *
	 * @return string Ability name.
	 */
	public function getName(): string {
		return 'fa-wpmcp/list-cron-schedules';
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
		return 'List Cron Schedules';
	}

	/**
	 * Get the ability description.
	 *
	 * @return string Description.
	 */
	public function getDescription(): string {
		return 'List available WordPress cron recurrence schedules (hourly, daily, etc.) for scheduling events.';
	}

	/**
	 * Get the input schema.
	 *
	 * @return array<string, mixed> JSON Schema array.
	 */
	public function getInputSchema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(),
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
				'schedules' => array(
					'type'        => 'array',
					'description' => 'List of available cron schedules.',
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'name'     => array( 'type' => 'string' ),
							'interval' => array( 'type' => 'integer' ),
							'display'  => array( 'type' => 'string' ),
						),
					),
				),
				'total'     => array(
					'type'        => 'integer',
					'description' => 'Total number of schedules available.',
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
	 * @return array<string, mixed> List of cron schedules.
	 */
	public function doExecute( array $input ): array {
		$wp_schedules = wp_get_schedules();

		if ( empty( $wp_schedules ) ) {
			return array(
				'schedules' => array(),
				'total'     => 0,
			);
		}

		$schedules = array();

		foreach ( $wp_schedules as $name => $data ) {
			$schedules[] = array(
				'name'     => $name,
				'interval' => (int) ( $data['interval'] ?? 0 ),
				'display'  => $data['display'] ?? $name,
			);
		}

		// Sort by interval ascending.
		usort(
			$schedules,
			function ( $a, $b ) {
				return $a['interval'] <=> $b['interval'];
			}
		);

		return array(
			'schedules' => $schedules,
			'total'     => count( $schedules ),
		);
	}
}
