<?php

/**
 * Deactivate Plugin ability - deactivates a WordPress plugin.
 *
 * @package FAWpmcp\Abilities\Plugins
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Plugins;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Deactivate Plugin ability.
 *
 * @package FAWpmcp\Abilities\Plugins
 */
final class DeactivatePlugin extends AbstractAbility {

	/**
	 * Returns the ability identifier.
	 *
	 * @return string
	 */
	public function getName(): string {
		return 'fa-wpmcp/deactivate-plugin';
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
		return 'Deactivate Plugin';
	}

	/**
	 * Returns the ability description.
	 *
	 * @return string
	 */
	public function getDescription(): string {
		return 'Deactivate a WordPress plugin.';
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
				'plugin'      => array( 'type' => 'string' ),
				'deactivated' => array( 'type' => 'boolean' ),
			),
		);
	}

	/**
	 * Returns the WordPress capability required.
	 *
	 * @return string
	 */
	public function getRequiredCapability(): string {
		return 'activate_plugins';
	}

	/**
	 * Executes the ability.
	 *
	 * @param array $input Input parameters.
	 * @return array
	 */
	public function doExecute( array $input ): array {
		$plugin = $input['plugin'];
		deactivate_plugins( $plugin );
		return array(
			'plugin'      => $plugin,
			'deactivated' => true,
		);
	}
}
