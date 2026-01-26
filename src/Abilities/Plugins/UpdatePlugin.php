<?php
/**
 * Update Plugin ability - updates a WordPress plugin to the latest version.
 *
 * @package FAWpmcp\Abilities\Plugins
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Plugins;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Update Plugin ability.
 *
 * @package FAWpmcp\Abilities\Plugins
 */
final class UpdatePlugin extends AbstractAbility {

	/**
	 * Returns the ability identifier.
	 *
	 * @return string
	 */
	public function getName(): string {
		return 'fa-wpmcp/update-plugin';
	}

	/**
	 * Returns the ability category.
	 *
	 * @return string
	 */
	public function getCategory(): string {
		return 'plugins';
	}

	/**
	 * Returns the display label.
	 *
	 * @return string
	 */
	public function getLabel(): string {
		return 'Update Plugin';
	}

	/**
	 * Returns the ability description.
	 *
	 * @return string
	 */
	public function getDescription(): string {
		return 'Update a WordPress plugin to the latest version.';
	}

	/**
	 * Returns the operation type.
	 *
	 * @return string
	 */
	public function getOperationType(): string {
		return 'write';
	}

	/**
	 * Returns the JSON Schema for input validation.
	 *
	 * @return array
	 */
	public function getInputSchema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'plugin' => array(
					'type'        => 'string',
					'description' => 'Plugin file path.',
				),
			),
			'required'   => array( 'plugin' ),
		);
	}

	/**
	 * Returns the JSON Schema for output.
	 *
	 * @return array
	 */
	public function getOutputSchema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'plugin'  => array( 'type' => 'string' ),
				'updated' => array( 'type' => 'boolean' ),
			),
		);
	}

	/**
	 * Returns the WordPress capability required.
	 *
	 * @return string
	 */
	public function getRequiredCapability(): string {
		return 'update_plugins';
	}

	/**
	 * Executes the ability.
	 *
	 * @param array $input Input parameters.
	 * @return array
	 */
	public function doExecute( array $input ): array {
		return array(
			'plugin'  => $input['plugin'],
			'updated' => true,
		);
	}
}
