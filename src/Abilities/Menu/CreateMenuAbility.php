<?php
/**
 * CreateMenuAbility - creates a new WordPress navigation menu.
 *
 * @package FAWpmcp\Abilities\Menu
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Menu;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\MenuCreationException;

/**
 * Ability to create a new WordPress navigation menu.
 *
 * Creates a menu with the specified name and optionally assigns it to a theme location.
 *
 * @package FAWpmcp\Abilities\Menu
 */
final class CreateMenuAbility extends AbstractAbility {

	/**
	 * Get the unique ability name.
	 *
	 * @return string Ability name.
	 */
	public function getName(): string {
		return 'fa-wpmcp/create-menu';
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
		return 'Create Menu';
	}

	/**
	 * Get the ability description.
	 *
	 * @return string Description.
	 */
	public function getDescription(): string {
		return 'Create a new WordPress navigation menu and optionally assign it to a theme location.';
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
				'name'     => array(
					'type'        => 'string',
					'description' => 'The menu name.',
				),
				'location' => array(
					'type'        => 'string',
					'description' => 'Optional theme location to assign the menu to.',
				),
			),
			'required'   => array( 'name' ),
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
				'term_id'  => array(
					'type'        => 'integer',
					'description' => 'The created menu term ID.',
				),
				'name'     => array(
					'type'        => 'string',
					'description' => 'The menu name.',
				),
				'slug'     => array(
					'type'        => 'string',
					'description' => 'The menu slug.',
				),
				'location' => array(
					'type'        => 'string',
					'description' => 'The theme location the menu was assigned to (if any).',
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
	 * Get the operation type.
	 *
	 * @return string 'write' for this ability.
	 */
	public function getOperationType(): string {
		return 'write';
	}

	/**
	 * Get ability annotations.
	 *
	 * @return array<string, mixed> Annotations array.
	 */
	public function getAnnotations(): array {
		return array(
			'readonly'     => false,
			'destructive'  => false,
			'idempotent'   => false,
			'instructions' => $this->getDescription(),
		);
	}

	/**
	 * Execute the ability.
	 *
	 * @param array<string, mixed> $input Validated input data.
	 * @return array<string, mixed> Created menu details.
	 * @throws MenuCreationException If menu creation fails.
	 */
	public function doExecute( array $input ): array {
		$menu_name = (string) $input['name'];
		$location  = $input['location'] ?? null;

		// Create the menu.
		$menu_id = wp_create_nav_menu( $menu_name );

		if ( is_wp_error( $menu_id ) ) {
			throw new MenuCreationException(
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Internal exception message.
				sprintf( 'Failed to create menu: %s', $menu_id->get_error_message() )
			);
		}

		// Get the created menu object.
		$menu = wp_get_nav_menu_object( $menu_id );

		$result = array(
			'term_id' => $menu->term_id,
			'name'    => $menu->name,
			'slug'    => $menu->slug,
		);

		// Assign to location if specified.
		if ( null !== $location && '' !== $location ) {
			$locations             = get_nav_menu_locations();
			$locations[ $location ] = $menu_id;
			set_theme_mod( 'nav_menu_locations', $locations );
			$result['location'] = $location;
		}

		return $result;
	}
}
