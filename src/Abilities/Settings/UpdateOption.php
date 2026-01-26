<?php

/**
 * UpdateOption ability - updates or creates a WordPress option.
 *
 * @package FAWpmcp\Abilities\Settings
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Settings;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Settings\OptionAccessPolicy;
use FAWpmcp\Exceptions\OptionException;

/**
 * Ability to update or create a WordPress option.
 *
 * Updates existing option or creates new one if it doesn't exist.
 * Supports setting autoload behavior.
 *
 * @package FAWpmcp\Abilities\Settings
 */
final class UpdateOption extends AbstractAbility {

	/**
	 * Returns the ability identifier.
	 *
	 * @return string Ability name.
	 */
	public function getName(): string {
		return 'fa-wpmcp/update-option';
	}

	/**
	 * Returns the ability category.
	 *
	 * @return string Category name.
	 */
	public function getCategory(): string {
		return 'settings';
	}

	/**
	 * Returns the display label.
	 *
	 * @return string Ability label.
	 */
	public function getLabel(): string {
		return 'Update Option';
	}

	/**
	 * Returns the ability description.
	 *
	 * @return string Description.
	 */
	public function getDescription(): string {
		return 'Update or create a WordPress option with optional autoload setting.';
	}

	/**
	 * Returns the operation type.
	 *
	 * @return string Operation type.
	 */
	public function getOperationType(): string {
		return 'write';
	}

	/**
	 * Returns the JSON Schema for input validation.
	 *
	 * @return array<string, mixed> JSON Schema array.
	 */
	public function getInputSchema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'option_name' => array(
					'type'        => 'string',
					'description' => 'The name of the option to update.',
				),
				'value'       => array(
					'description' => 'The value to set for the option.',
				),
				'autoload'    => array(
					'type'        => 'string',
					'description' => 'Whether to autoload the option (yes or no).',
					'enum'        => array( 'yes', 'no' ),
				),
			),
			'required'   => array( 'option_name', 'value' ),
		);
	}

	/**
	 * Returns the JSON Schema for output.
	 *
	 * @return array<string, mixed> JSON Schema array.
	 */
	public function getOutputSchema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'option_name' => array(
					'type'        => 'string',
					'description' => 'The name of the option.',
				),
				'updated'     => array(
					'type'        => 'boolean',
					'description' => 'Whether the option was successfully updated.',
				),
			),
		);
	}

	/**
	 * Returns the WordPress capability required.
	 *
	 * @return string WordPress capability name.
	 */
	public function getRequiredCapability(): string {
		return 'manage_options';
	}

	/**
	 * Executes the ability.
	 *
	 * @param array<string, mixed> $input Validated input data.
	 * @return array<string, mixed> Update result.
	 * @throws OptionException If option name is invalid or protected.
	 */
	public function doExecute( array $input ): array {
		$option_name = sanitize_key( $input['option_name'] );
		$value       = $input['value'];
		$autoload    = $input['autoload'] ?? null;

		// Validate option name length (MySQL utf8mb4 index limit).
		if ( strlen( $option_name ) > 191 ) {
			throw new OptionException( 'Option name exceeds maximum length of 191 characters.' );
		}

		OptionAccessPolicy::assertAllowed( $option_name );

		$updated = update_option( $option_name, $value, $autoload );

		return array(
			'option_name' => $option_name,
			'updated'     => $updated,
		);
	}
}
