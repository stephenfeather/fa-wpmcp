<?php

/**
 * ListRolesAbility - lists all WordPress user roles.
 *
 * @package FAWpmcp\Abilities\Role
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Role;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Ability to list all WordPress user roles.
 *
 * Returns an array of roles with names, display names, and capabilities.
 *
 * @package FAWpmcp\Abilities\Role
 */
final class ListRolesAbility extends AbstractAbility {

	/**
	 * Get the unique ability name.
	 *
	 * @return string Ability name.
	 */
	public function getName(): string {
		return 'fa-wpmcp/list-roles';
	}

	/**
	 * Get the ability category.
	 *
	 * @return string Category name.
	 */
	public function getCategory(): string {
		return 'role';
	}

	/**
	 * Get the human-readable label.
	 *
	 * @return string Ability label.
	 */
	public function getLabel(): string {
		return 'List Roles';
	}

	/**
	 * Get the ability description.
	 *
	 * @return string Description.
	 */
	public function getDescription(): string {
		return 'List all WordPress user roles with their capabilities.';
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
				'roles' => array(
					'type'        => 'array',
					'description' => 'List of user roles.',
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'name'         => array( 'type' => 'string' ),
							'display_name' => array( 'type' => 'string' ),
							'capabilities' => array( 'type' => 'object' ),
						),
					),
				),
				'total' => array(
					'type'        => 'integer',
					'description' => 'Total number of roles.',
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
		return 'list_users';
	}

	/**
	 * Execute the ability.
	 *
	 * @param array<string, mixed> $input Validated input data.
	 * @return array<string, mixed> List of roles.
	 */
	public function doExecute( array $input ): array {
		$wp_roles    = wp_roles();
		$role_names  = $wp_roles->get_names();
		$role_objects = $wp_roles->role_objects;

		$roles = array();

		foreach ( $role_names as $role_slug => $display_name ) {
			$role_object = $role_objects[ $role_slug ] ?? null;

			$roles[] = array(
				'name'         => $role_slug,
				'display_name' => $display_name,
				'capabilities' => $role_object ? $role_object->capabilities : array(),
			);
		}

		return array(
			'roles' => $roles,
			'total' => count( $roles ),
		);
	}
}
