<?php
/**
 * ListThemes ability - lists installed WordPress themes.
 *
 * @package FAWpmcp\Abilities\Themes
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Themes;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Ability to list installed WordPress themes.
 *
 * @package FAWpmcp\Abilities\Themes
 */
final class ListThemes extends AbstractAbility {
	/**
	 * Returns the ability identifier.
	 *
	 * @return string
	 */
	public function getName(): string {
		return 'fa-wpmcp/list-themes';
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
		return 'List Themes';
	}

	/**
	 * Returns the ability description.
	 *
	 * @return string
	 */
	public function getDescription(): string {
		return 'List installed WordPress themes with their activation status and metadata.';
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
				'status' => array(
					'type'        => 'string',
					'description' => 'Filter by status (active, inactive, all).',
					'enum'        => array( 'active', 'inactive', 'all' ),
				),
			),
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
				'themes' => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'stylesheet' => array( 'type' => 'string' ),
							'name'       => array( 'type' => 'string' ),
							'version'    => array( 'type' => 'string' ),
							'active'     => array( 'type' => 'boolean' ),
						),
					),
				),
				'total'  => array( 'type' => 'integer' ),
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
	 */
	public function doExecute( array $input ): array {
		$all_themes   = wp_get_themes();
		$status       = $input['status'] ?? 'all';
		$active_theme = get_option( 'stylesheet' );

		$themes = array();
		foreach ( $all_themes as $theme ) {
			$is_active = $theme->get_stylesheet() === $active_theme;

			if ( 'active' === $status && ! $is_active ) {
				continue;
			}
			if ( 'inactive' === $status && $is_active ) {
				continue;
			}

			$themes[] = array(
				'stylesheet' => $theme->get_stylesheet(),
				'name'       => $theme->get( 'Name' ),
				'version'    => $theme->get( 'Version' ),
				'active'     => $is_active,
			);
		}

		return array(
			'themes' => $themes,
			'total'  => count( $themes ),
		);
	}
}
