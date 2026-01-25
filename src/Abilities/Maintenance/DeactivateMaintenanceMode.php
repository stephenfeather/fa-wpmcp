<?php
/**
 * DeactivateMaintenanceMode ability - disables WordPress maintenance mode.
 *
 * @package FAWpmcp\Abilities\Maintenance
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Maintenance;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\MaintenanceModeException;

/**
 * Ability to deactivate WordPress maintenance mode.
 *
 * Removes the .maintenance file from ABSPATH to disable maintenance mode.
 *
 * @package FAWpmcp\Abilities\Maintenance
 */
final class DeactivateMaintenanceMode extends AbstractAbility {
	/**
	 * Get the unique ability name.
	 *
	 * @return string Ability name.
	 */
	public function getName(): string {
		return 'fa-wpmcp/deactivate-maintenance-mode';
	}

	/**
	 * Get the ability category.
	 *
	 * @return string Category name.
	 */
	public function getCategory(): string {
		return 'maintenance';
	}

	/**
	 * Get the human-readable label.
	 *
	 * @return string Ability label.
	 */
	public function getLabel(): string {
		return 'Deactivate Maintenance Mode';
	}

	/**
	 * Get the ability description.
	 *
	 * @return string Description.
	 */
	public function getDescription(): string {
		return 'Deactivate WordPress maintenance mode. Site will be accessible to visitors again.';
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
				'deactivated' => array(
					'type'        => 'boolean',
					'description' => 'Whether maintenance mode was successfully deactivated.',
				),
				'was_active'  => array(
					'type'        => 'boolean',
					'description' => 'Whether maintenance mode was active before this call.',
				),
				'message'     => array(
					'type'        => 'string',
					'description' => 'Status message.',
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
	 * @return string Operation type ('read' or 'write').
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
		$annotations               = parent::getAnnotations();
		$annotations['idempotent'] = true;
		return $annotations;
	}

	/**
	 * Execute the ability.
	 *
	 * @param array<string, mixed> $input Validated input data.
	 * @return array<string, mixed> Deactivation result.
	 * @throws MaintenanceModeException If deactivation fails.
	 */
	public function doExecute( array $input ): array {
		$maintenance_file = ABSPATH . '.maintenance';

		// Check if maintenance mode was active.
		$was_active = file_exists( $maintenance_file );

		if ( ! $was_active ) {
			return array(
				'deactivated' => true,
				'was_active'  => false,
				'message'     => 'Maintenance mode was not active.',
			);
		}

		// Remove the maintenance file.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
		$result = unlink( $maintenance_file );

		if ( false === $result ) {
			throw new MaintenanceModeException(
				'Failed to remove maintenance file. Check file system permissions.'
			);
		}

		return array(
			'deactivated' => true,
			'was_active'  => true,
			'message'     => 'Maintenance mode deactivated. Site is now accessible.',
		);
	}
}
