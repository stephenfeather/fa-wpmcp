<?php

/**
 * ActivateTheme ability - activates a WordPress theme.
 *
 * @package FAWpmcp\Abilities\Themes
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Themes;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Ability to activate a WordPress theme.
 *
 * @package FAWpmcp\Abilities\Themes
 */
final class ActivateTheme extends AbstractAbility {

	/**
	 * Returns the ability identifier.
	 *
	 * @return string
	 */
	public function getName(): string {
		return 'fa-wpmcp/activate-theme';
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
		return 'Activate Theme';
	}

	/**
	 * Returns the ability description.
	 *
	 * @return string
	 */
	public function getDescription(): string {
		return 'Activate a WordPress theme.';
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
					'description' => 'Theme stylesheet name to activate.',
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
		return 'switch_themes';
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
		switch_theme( $input['stylesheet'] );
		return array( 'success' => true );
	}
}
