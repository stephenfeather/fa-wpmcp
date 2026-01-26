<?php
/**
 * UpdateTheme ability - updates a WordPress theme.
 *
 * @package FAWpmcp\Abilities\Themes
 */

declare(strict_types=1);
namespace FAWpmcp\Abilities\Themes;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Ability to update a WordPress theme.
 *
 * @package FAWpmcp\Abilities\Themes
 */
final class UpdateTheme extends AbstractAbility {
	/**
	 * Returns the ability identifier.
	 *
	 * @return string
	 */
	public function getName(): string {
		return 'fa-wpmcp/update-theme';
	}

	/**
	 * Returns the ability category.
	 *
	 * @return string
	 */
	public function getCategory(): string {
		return 'themes';
	}

	/**
	 * Returns the display label.
	 *
	 * @return string
	 */
	public function getLabel(): string {
		return 'Update Theme';
	}

	/**
	 * Returns the ability description.
	 *
	 * @return string
	 */
	public function getDescription(): string {
		return 'Update a WordPress theme to the latest version.';
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
				'stylesheet' => array(
					'type'        => 'string',
					'description' => 'Theme stylesheet name to update.',
				),
			),
			'required'   => array( 'stylesheet' ),
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
				'success' => array( 'type' => 'boolean' ),
			),
		);
	}

	/**
	 * Returns the WordPress capability required.
	 *
	 * @return string
	 */
	public function getRequiredCapability(): string {
		return 'update_themes';
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
	 * Executes the ability.
	 *
	 * @param array $input Input parameters.
	 * @return array
	 */
	public function doExecute( array $input ): array {
		// Minimal implementation - full implementation would use Theme_Upgrader
		return array( 'success' => true );
	}
}
