<?php
/**
 * GetOption ability - retrieves a WordPress option.
 *
 * @package FAWpmcp\Abilities\Settings
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Settings;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Settings\OptionAccessPolicy;
use FAWpmcp\Exceptions\OptionException;

/**
 * Ability to retrieve a WordPress option.
 *
 * Returns option value with metadata:
 * - Option name
 * - Current value
 * - Whether the option exists
 *
 * @package FAWpmcp\Abilities\Settings
 */
final class GetOption extends AbstractAbility {
	/**
	 * Returns the ability identifier.
	 *
	 * @return string Ability name.
	 */
	public function getName(): string {
		return 'fa-wpmcp/get-option';
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
		return 'Get Option';
	}

	/**
	 * Returns the ability description.
	 *
	 * @return string Description.
	 */
	public function getDescription(): string {
		return 'Retrieve a WordPress option by name with optional default value.';
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
					'description' => 'The name of the option to retrieve.',
				),
				'default'     => array(
					'description' => 'Default value if option does not exist.',
				),
			),
			'required'   => array( 'option_name' ),
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
				'value'       => array(
					'description' => 'The option value or default.',
				),
				'exists'      => array(
					'type'        => 'boolean',
					'description' => 'Whether the option exists in the database.',
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
	 * @return array<string, mixed> Option data.
	 * @throws OptionException If option name is invalid or protected.
	 */
	public function doExecute( array $input ): array {
		$option_name = sanitize_key( $input['option_name'] );
		$default     = $input['default'] ?? false;

		// Validate option name length (MySQL utf8mb4 index limit).
		if ( strlen( $option_name ) > 191 ) {
			throw new OptionException( 'Option name exceeds maximum length of 191 characters.' );
		}

		OptionAccessPolicy::assertAllowed( $option_name );

		// Use a unique sentinel to detect if option exists.
		$sentinel = new \stdClass();
		$raw      = get_option( $option_name, $sentinel );
		$exists   = ( $sentinel !== $raw );

		$value = $exists ? $raw : $default;

		return array(
			'option_name' => $option_name,
			'value'       => $value,
			'exists'      => $exists,
		);
	}
}
