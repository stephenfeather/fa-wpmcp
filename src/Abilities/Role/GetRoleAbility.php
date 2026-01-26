<?php
/**
 * GetRoleAbility - retrieves details of a specific WordPress user role.
 *
 * @package FAWpmcp\Abilities\Role
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Role;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\RoleNotFoundException;

/**
 * Ability to get details of a specific WordPress user role.
 *
 * Returns role name, display name, capabilities, and capability count.
 *
 * @package FAWpmcp\Abilities\Role
 */
final class GetRoleAbility extends AbstractAbility {
	/**
	 * Get the unique ability name.
	 *
	 * @return string Ability name.
	 */
	public function getName(): string {
		return 'fa-wpmcp/get-role';
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
		return 'Get Role';
	}

	/**
	 * Get the ability description.
	 *
	 * @return string Description.
	 */
	public function getDescription(): string {
		return 'Get details of a specific WordPress user role including its capabilities.';
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
				'role' => array(
					'type'        => 'string',
					'description' => 'The role slug to retrieve.',
				),
			),
			'required'   => array( 'role' ),
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
				'name'              => array(
					'type'        => 'string',
					'description' => 'The role slug.',
				),
				'display_name'      => array(
					'type'        => 'string',
					'description' => 'The role display name.',
				),
				'capabilities'      => array(
					'type'        => 'object',
					'description' => 'Role capabilities as key-value pairs.',
				),
				'capabilities_count' => array(
					'type'        => 'integer',
					'description' => 'Number of capabilities the role has.',
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
	 * @return array<string, mixed> Role details.
	 * @throws RoleNotFoundException If role does not exist.
	 */
	public function doExecute( array $input ): array {
		$role_slug = (string) $input['role'];
		$role      = get_role( $role_slug );

		if ( null === $role ) {
			throw new RoleNotFoundException(
				sprintf( 'Role "%s" not found.', $role_slug )
			);
		}

		// Get display name from wp_roles.
		$wp_roles     = wp_roles();
		$role_names   = $wp_roles->get_names();
		$display_name = $role_names[ $role_slug ] ?? $role_slug;

		return array(
			'name'              => $role->name,
			'display_name'      => $display_name,
			'capabilities'      => $role->capabilities,
			'capabilities_count' => count( $role->capabilities ),
		);
	}
}
