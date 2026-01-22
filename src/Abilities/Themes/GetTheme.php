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
	public function getName(): string {
		return 'fa-wpmcp/get-theme';
	}

	public function getCategory(): string {
		return 'themes';
	}

	public function getLabel(): string {
		return 'Get Theme';
	}

	public function getDescription(): string {
		return 'Get details about a specific WordPress theme.';
	}

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

	public function getRequiredCapability(): string {
		return 'switch_themes';
	}

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
