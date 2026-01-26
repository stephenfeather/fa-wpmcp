<?php
/**
 * SetTransient ability - creates or updates a transient.
 *
 * @package FAWpmcp\Abilities\Transients
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Transients;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Ability to create or update a WordPress transient.
 *
 * Sets a transient value with optional expiration time.
 * Supports both standard transients and network (site) transients for multisite.
 *
 * @package FAWpmcp\Abilities\Transients
 */
final class SetTransient extends AbstractAbility {

	/**
	 * Get the unique ability name.
	 *
	 * @return string Ability name.
	 */
	public function getName(): string {
		return 'fa-wpmcp/set-transient';
	}

	/**
	 * Get the ability category.
	 *
	 * @return string Category name.
	 */
	public function getCategory(): string {
		return 'transients';
	}

	/**
	 * Get the human-readable label.
	 *
	 * @return string Ability label.
	 */
	public function getLabel(): string {
		return 'Set Transient';
	}

	/**
	 * Get the ability description.
	 *
	 * @return string Description.
	 */
	public function getDescription(): string {
		return 'Create or update a WordPress transient with optional expiration time. Supports both standard and network transients.';
	}

	/**
	 * Get the operation type.
	 *
	 * @return string Operation type.
	 */
	public function getOperationType(): string {
		return 'create';
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
				'key'        => array(
					'type'        => 'string',
					'description' => 'The transient key name.',
				),
				'value'      => array(
					'description' => 'The value to store. Can be any serializable type.',
				),
				'expiration' => array(
					'type'        => 'integer',
					'description' => 'Time until expiration in seconds. Default: 0 (no expiration).',
					'default'     => 0,
				),
				'network'    => array(
					'type'        => 'boolean',
					'description' => 'Whether to set a network (site) transient for multisite. Default: false.',
					'default'     => false,
				),
			),
			'required'   => array( 'key', 'value' ),
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
					'description' => 'Whether the transient was successfully set.',
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
	 * Get ability annotations.
	 *
	 * Marks this ability as non-readonly but idempotent.
	 *
	 * @return array<string, mixed> Annotations array.
	 */
	public function getAnnotations(): array {
		$annotations               = parent::getAnnotations();
		$annotations['readonly']   = false;
		$annotations['idempotent'] = true;
		return $annotations;
	}

	/**
	 * Execute the ability.
	 *
	 * @param array<string, mixed> $input Validated input data.
	 * @return array<string, mixed> Result of set operation.
	 */
	public function doExecute( array $input ): array {
		$key        = (string) $input['key'];
		$value      = $input['value'];
		$expiration = (int) ( $input['expiration'] ?? 0 );
		$network    = ! empty( $input['network'] );

		// Set the transient.
		if ( $network ) {
			$success = set_site_transient( $key, $value, $expiration );
		} else {
			$success = set_transient( $key, $value, $expiration );
		}

		return array(
			'success' => (bool) $success,
		);
	}
}
