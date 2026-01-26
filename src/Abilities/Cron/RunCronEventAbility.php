<?php
/**
 * RunCronEventAbility - manually triggers a WP-Cron event.
 *
 * @package FAWpmcp\Abilities\Cron
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Cron;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Ability to manually run a WordPress cron event.
 *
 * Triggers the hook action with its registered arguments.
 *
 * @package FAWpmcp\Abilities\Cron
 */
final class RunCronEventAbility extends AbstractAbility {
	/**
	 * Get the unique ability name.
	 *
	 * @return string Ability name.
	 */
	public function getName(): string {
		return 'fa-wpmcp/run-cron-event';
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
		return 'Run Cron Event';
	}

	/**
	 * Get the ability description.
	 *
	 * @return string Description.
	 */
	public function getDescription(): string {
		return 'Manually trigger a WordPress cron event hook. Runs the first scheduled instance of the hook immediately.';
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
					'description' => 'The hook name to run.',
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
				'success'  => array(
					'type'        => 'boolean',
					'description' => 'Whether the hook was executed.',
				),
				'executed' => array(
					'type'        => 'boolean',
					'description' => 'Whether the do_action was called.',
				),
				'error'    => array(
					'type'        => 'string',
					'description' => 'Error message if execution failed.',
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
	 * Get ability annotations.
	 *
	 * @return array<string, mixed> Annotations array.
	 */
	public function getAnnotations(): array {
		return array(
			'readonly'     => false,
			'destructive'  => false,
			'idempotent'   => false,
			'instructions' => $this->getDescription(),
		);
	}

	/**
	 * Execute the ability.
	 *
	 * @param array<string, mixed> $input Validated input data.
	 * @return array<string, mixed> Run result.
	 */
	public function doExecute( array $input ): array {
		$hook       = (string) $input['hook'];
		$cron_array = _get_cron_array();

		// Find the first scheduled instance of this hook.
		$event_args = null;

		if ( ! empty( $cron_array ) && is_array( $cron_array ) ) {
			// Sort by timestamp to get the earliest event first.
			ksort( $cron_array );

			foreach ( $cron_array as $timestamp => $hooks ) {
				if ( isset( $hooks[ $hook ] ) ) {
					// Get the first event's args.
					$events     = $hooks[ $hook ];
					$event_data = reset( $events );
					$event_args = $event_data['args'] ?? array();
					break;
				}
			}
		}

		// If hook not found in scheduled events, return error.
		if ( null === $event_args ) {
			return array(
				'success'  => false,
				'executed' => false,
				'error'    => 'Hook not found in scheduled cron events.',
			);
		}

		// Execute the hook with its arguments.
		do_action( $hook, ...$event_args );

		return array(
			'success'  => true,
			'executed' => true,
		);
	}
}
