<?php
/**
 * GetTheme ability - gets details about a specific WordPress theme.
 *
 * @package FAWpmcp\Abilities\Themes
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Themes;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\ThemeNotFoundException;

/**
 * Ability to get details about a specific WordPress theme.
 *
 * @package FAWpmcp\Abilities\Themes
 */
final class GetTheme extends AbstractAbility {
	/**
	 * Returns the ability identifier.
	 *
	 * @return string
	 */
	public function getName(): string {
		return 'fa-wpmcp/get-theme';
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
		return 'Get Theme';
	}

	/**
	 * Returns the ability description.
	 *
	 * @return string
	 */
	public function getDescription(): string {
		return 'Get details about a specific WordPress theme.';
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
					'description' => 'Theme stylesheet name (e.g., twentytwentyfour).',
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
				'stylesheet' => array( 'type' => 'string' ),
				'name'       => array( 'type' => 'string' ),
				'version'    => array( 'type' => 'string' ),
				'active'     => array( 'type' => 'boolean' ),
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
	 * Executes the ability.
	 *
	 * @param array $input Input parameters.
	 * @return array
	 * @throws ThemeNotFoundException If the specified theme is not found.
	 */
	public function doExecute( array $input ): array {
		$stylesheet = $input['stylesheet'];
		$theme      = wp_get_theme( $stylesheet );

		if ( ! $theme->exists() ) {
			throw new ThemeNotFoundException( "Theme '{$stylesheet}' not found." );
		}

		$active_theme = get_option( 'stylesheet' );

		return array(
			'stylesheet' => $theme->get_stylesheet(),
			'name'       => $theme->get( 'Name' ),
			'version'    => $theme->get( 'Version' ),
			'active'     => $theme->get_stylesheet() === $active_theme,
		);
	}
}
