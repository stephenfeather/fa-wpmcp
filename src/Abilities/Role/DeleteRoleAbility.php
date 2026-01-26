<?php
/**
 * DeleteRoleAbility - deletes a WordPress user role.
 *
 * @package FAWpmcp\Abilities\Role
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Role;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\RoleNotFoundException;
use FAWpmcp\Exceptions\RoleDeletionException;

/**
 * Ability to delete a WordPress user role.
 *
 * Cannot delete default WordPress roles (administrator, editor, author, contributor, subscriber).
 *
 * @package FAWpmcp\Abilities\Role
 */
final class DeleteRoleAbility extends AbstractAbility {
	/**
	 * Default WordPress roles that cannot be deleted.
	 *
	 * @var array<string>
	 */
	private const DEFAULT_ROLES = array(
		'administrator',
		'editor',
		'author',
		'contributor',
		'subscriber',
	);

	/**
	 * Get the unique ability name.
	 *
	 * @return string Ability name.
	 */
	public function getName(): string {
		return 'fa-wpmcp/delete-role';
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
		return 'Delete Role';
	}

	/**
	 * Get the ability description.
	 *
	 * @return string Description.
	 */
	public function getDescription(): string {
		return 'Delete a WordPress user role. Cannot delete default WordPress roles.';
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
					'description' => 'The role slug to delete.',
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
				'deleted' => array(
					'type'        => 'boolean',
					'description' => 'Whether the role was deleted.',
				),
				'role'    => array(
					'type'        => 'string',
					'description' => 'The deleted role slug.',
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
		return 'delete_users';
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
	 * @throws RoleNotFoundException If role does not exist.
	 * @throws RoleDeletionException If trying to delete a default role.
	 */
	public function doExecute( array $input ): array {
		$role_slug = (string) $input['role'];

		// Check if role exists.
		$role = get_role( $role_slug );
		if ( null === $role ) {
			throw new RoleNotFoundException(
				sprintf( 'Role "%s" not found.', $role_slug )
			);
		}

		// Prevent deletion of default roles.
		if ( in_array( $role_slug, self::DEFAULT_ROLES, true ) ) {
			throw new RoleDeletionException(
				sprintf( 'Cannot delete default WordPress role "%s".', $role_slug )
			);
		}

		// Delete the role.
		$wp_roles = wp_roles();
		$wp_roles->remove_role( $role_slug );

		return array(
			'deleted' => true,
			'role'    => $role_slug,
		);
	}
}
