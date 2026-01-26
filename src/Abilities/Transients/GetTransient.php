<?php
/**
 * GetTransient ability - retrieves a single transient value by key.
 *
 * @package FAWpmcp\Abilities\Transients
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Transients;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Ability to retrieve a single WordPress transient value by key.
 *
 * Returns the transient value if it exists, or indicates non-existence.
 * Supports both standard transients and network (site) transients for multisite.
 *
 * @package FAWpmcp\Abilities\Transients
 */
final class GetTransient extends AbstractAbility {

	/**
	 * Get the unique ability name.
	 *
	 * @return string Ability name.
	 */
	public function getName(): string {
		return 'fa-wpmcp/get-transient';
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
		return 'Get Transient';
	}

	/**
	 * Get the ability description.
	 *
	 * @return string Description.
	 */
	public function getDescription(): string {
		return 'Retrieve a single WordPress transient value by key. Supports both standard and network transients.';
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
				'key'     => array(
					'type'        => 'string',
					'description' => 'The transient key name.',
				),
				'network' => array(
					'type'        => 'boolean',
					'description' => 'Whether to retrieve a network (site) transient for multisite. Default: false.',
					'default'     => false,
				),
			),
			'required'   => array( 'key' ),
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
				'value'  => array(
					'description' => 'The transient value, or null if not found.',
				),
				'exists' => array(
					'type'        => 'boolean',
					'description' => 'Whether the transient exists.',
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
	 * @return array<string, mixed> Transient data.
	 */
	public function doExecute( array $input ): array {
		$key     = (string) $input['key'];
		$network = ! empty( $input['network'] );

		// Get the transient value.
		if ( $network ) {
			$value = get_site_transient( $key );
		} else {
			$value = get_transient( $key );
		}

		// Check if transient exists (false means not found or expired).
		$exists = false !== $value;

		return array(
			'value'  => $exists ? $value : null,
			'exists' => $exists,
		);
	}
}
