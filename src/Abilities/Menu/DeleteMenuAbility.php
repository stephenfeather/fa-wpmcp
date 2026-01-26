<?php
/**
 * DeleteMenuAbility - deletes a WordPress navigation menu.
 *
 * @package FAWpmcp\Abilities\Menu
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Menu;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\MenuNotFoundException;

/**
 * Ability to delete a WordPress navigation menu.
 *
 * Deletes the specified menu by ID or slug.
 *
 * @package FAWpmcp\Abilities\Menu
 */
final class DeleteMenuAbility extends AbstractAbility {

	/**
	 * Get the unique ability name.
	 *
	 * @return string Ability name.
	 */
	public function getName(): string {
		return 'fa-wpmcp/delete-menu';
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
		return 'Delete Menu';
	}

	/**
	 * Get the ability description.
	 *
	 * @return string Description.
	 */
	public function getDescription(): string {
		return 'Delete a WordPress navigation menu by ID or slug.';
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
				'menu' => array(
					'type'        => array( 'string', 'integer' ),
					'description' => 'Menu ID or slug to delete.',
				),
			),
			'required'   => array( 'menu' ),
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
				'deleted' => array(
					'type'        => 'boolean',
					'description' => 'Whether the menu was deleted.',
				),
				'menu'    => array(
					'type'        => 'integer',
					'description' => 'The deleted menu ID.',
				),
				'name'    => array(
					'type'        => 'string',
					'description' => 'The deleted menu name.',
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
			'destructive'  => true,
			'idempotent'   => false,
			'instructions' => $this->getDescription(),
		);
	}

	/**
	 * Execute the ability.
	 *
	 * @param array<string, mixed> $input Validated input data.
	 * @return array<string, mixed> Deletion result.
	 * @throws MenuNotFoundException If menu does not exist or deletion fails.
	 */
	public function doExecute( array $input ): array {
		$menu_identifier = $input['menu'];
		$menu            = wp_get_nav_menu_object( $menu_identifier );

		if ( false === $menu ) {
			throw new MenuNotFoundException(
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Internal exception message.
				sprintf( 'Menu "%s" not found.', $menu_identifier )
			);
		}

		$menu_id   = $menu->term_id;
		$menu_name = $menu->name;

		// Delete the menu.
		$result = wp_delete_nav_menu( $menu_id );

		if ( is_wp_error( $result ) ) {
			throw new MenuNotFoundException(
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Internal exception message.
				sprintf( 'Failed to delete menu: %s', $result->get_error_message() )
			);
		}

		return array(
			'deleted' => true,
			'menu'    => $menu_id,
			'name'    => $menu_name,
		);
	}
}
