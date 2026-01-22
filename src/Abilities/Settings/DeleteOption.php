<?php
/**
 * DeleteOption ability - deletes a WordPress option.
 *
 * @package FAWpmcp\Abilities\Settings
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Settings;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Settings\OptionAccessPolicy;

/**
 * Ability to delete a WordPress option.
 *
 * Removes the option from the database.
 *
 * @package FAWpmcp\Abilities\Settings
 */
final class DeleteOption extends AbstractAbility {
	/**
	 * Get the unique ability name.
	 *
	 * @return string Ability name.
	 */
	public function getName(): string {
		return 'fa-wpmcp/delete-option';
	}

	/**
	 * Get the ability category.
	 *
	 * @return string Category name.
	 */
	public function getCategory(): string {
		return 'settings';
	}

	/**
	 * Get the human-readable label.
	 *
	 * @return string Ability label.
	 */
	public function getLabel(): string {
		return 'Delete Option';
	}

	/**
	 * Get the ability description.
	 *
	 * @return string Description.
	 */
	public function getDescription(): string {
		return 'Delete a WordPress option from the database.';
	}

	/**
	 * Get the operation type.
	 *
	 * @return string Operation type.
	 */
	public function getOperationType(): string {
		return 'write';
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
				'option_name' => array(
					'type'        => 'string',
					'description' => 'The name of the option to delete.',
				),
			),
			'required'   => array( 'option_name' ),
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
				'option_name' => array(
					'type'        => 'string',
					'description' => 'The name of the option.',
				),
				'deleted'     => array(
					'type'        => 'boolean',
					'description' => 'Whether the option was successfully deleted.',
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
	 * @return array<string, mixed> Deletion result.
	 */
	public function doExecute( array $input ): array {
		$option_name = $input['option_name'];

		OptionAccessPolicy::assertAllowed( $option_name );
		$deleted     = delete_option( $option_name );

		return array(
			'option_name' => $option_name,
			'deleted'     => $deleted,
		);
	}
}
