<?php
/**
 * ListMenusAbility - lists all WordPress navigation menus.
 *
 * @package FAWpmcp\Abilities\Menu
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Menu;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Ability to list all WordPress navigation menus.
 *
 * Returns an array of menus with term_id, name, slug, locations, and item count.
 *
 * @package FAWpmcp\Abilities\Menu
 */
final class ListMenusAbility extends AbstractAbility {

	/**
	 * Get the unique ability name.
	 *
	 * @return string Ability name.
	 */
	public function getName(): string {
		return 'fa-wpmcp/list-menus';
	}

	/**
	 * Get the ability category.
	 *
	 * @return string Category name.
	 */
	public function getCategory(): string {
		return 'menu';
	}

	/**
	 * Get the human-readable label.
	 *
	 * @return string Ability label.
	 */
	public function getLabel(): string {
		return 'List Menus';
	}

	/**
	 * Get the ability description.
	 *
	 * @return string Description.
	 */
	public function getDescription(): string {
		return 'List all WordPress navigation menus with their locations and item counts.';
	}

	/**
	 * Get the input schema.
	 *
	 * @return array<string, mixed> JSON Schema array.
	 */
	public function getInputSchema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(),
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
				'menus' => array(
					'type'        => 'array',
					'description' => 'List of navigation menus.',
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'term_id'    => array( 'type' => 'integer' ),
							'name'       => array( 'type' => 'string' ),
							'slug'       => array( 'type' => 'string' ),
							'locations'  => array(
								'type'  => 'array',
								'items' => array( 'type' => 'string' ),
							),
							'item_count' => array( 'type' => 'integer' ),
						),
					),
				),
				'total' => array(
					'type'        => 'integer',
					'description' => 'Total number of menus.',
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
		return 'edit_theme_options';
	}

	/**
	 * Execute the ability.
	 *
	 * @param array<string, mixed> $input Validated input data.
	 * @return array<string, mixed> List of menus.
	 */
	public function doExecute( array $input ): array {
		$nav_menus = wp_get_nav_menus();
		$locations = get_nav_menu_locations();

		$menus = array();

		foreach ( $nav_menus as $menu ) {
			// Find locations assigned to this menu.
			$menu_locations = array();
			foreach ( $locations as $location => $menu_id ) {
				if ( $menu_id === $menu->term_id ) {
					$menu_locations[] = $location;
				}
			}

			$menus[] = array(
				'term_id'    => $menu->term_id,
				'name'       => $menu->name,
				'slug'       => $menu->slug,
				'locations'  => $menu_locations,
				'item_count' => $menu->count,
			);
		}

		return array(
			'menus' => $menus,
			'total' => count( $menus ),
		);
	}
}
