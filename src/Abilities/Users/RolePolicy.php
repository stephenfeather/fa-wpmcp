<?php
/**
 * RolePolicy - enforces maximum role restrictions for API user operations.
 *
 * @package FAWpmcp\Abilities\Users
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Users;

use FAWpmcp\Exceptions\RoleNotAllowedException;

/**
 * Enforces role restrictions for user creation and updates via API.
 *
 * Provides configurable maximum role assignment to prevent
 * high-privilege roles (e.g., administrator) from being
 * assigned via API unless explicitly allowed.
 *
 * @package FAWpmcp\Abilities\Users
 */
final class RolePolicy {

	/**
	 * WordPress option name for max API role configuration.
	 *
	 * @var string
	 */
	public const OPTION_NAME = 'fa_wpmcp_max_api_role';

	/**
	 * Default maximum role (editor) - prevents admin creation by default.
	 *
	 * @var string
	 */
	public const DEFAULT_MAX_ROLE = 'editor';

	/**
	 * Role hierarchy from highest to lowest privilege.
	 *
	 * @var array<string, int>
	 */
	private const ROLE_HIERARCHY = array(
		'administrator' => 5,
		'editor'        => 4,
		'author'        => 3,
		'contributor'   => 2,
		'subscriber'    => 1,
	);

	/**
	 * All standard WordPress roles.
	 *
	 * @var array<string>
	 */
	public const ALL_ROLES = array(
		'administrator',
		'editor',
		'author',
		'contributor',
		'subscriber',
	);

	/**
	 * Get the configured maximum role level.
	 *
	 * @return string The maximum role that can be assigned via API.
	 */
	public function getMaxRole(): string {
		$max_role = \get_option( self::OPTION_NAME, self::DEFAULT_MAX_ROLE );

		// Validate the stored option is a valid role.
		if ( ! isset( self::ROLE_HIERARCHY[ $max_role ] ) ) {
			return self::DEFAULT_MAX_ROLE;
		}

		return $max_role;
	}

	/**
	 * Get the list of roles allowed for API assignment.
	 *
	 * @return array<string> Roles that can be assigned via API.
	 */
	public function getAllowedRoles(): array {
		$max_role  = $this->getMaxRole();
		$max_level = self::ROLE_HIERARCHY[ $max_role ];

		return array_filter(
			self::ALL_ROLES,
			function ( string $role ) use ( $max_level ): bool {
				return self::ROLE_HIERARCHY[ $role ] <= $max_level;
			}
		);
	}

	/**
	 * Check if a role is allowed for API assignment.
	 *
	 * @param string $role The role to check.
	 * @return bool True if allowed, false otherwise.
	 */
	public function isRoleAllowed( string $role ): bool {
		// Unknown roles are allowed (custom roles).
		if ( ! isset( self::ROLE_HIERARCHY[ $role ] ) ) {
			return true;
		}

		$max_role  = $this->getMaxRole();
		$max_level = self::ROLE_HIERARCHY[ $max_role ];

		return self::ROLE_HIERARCHY[ $role ] <= $max_level;
	}

	/**
	 * Validate a role and throw if not allowed.
	 *
	 * @param string $role The role to validate.
	 * @return string The validated role.
	 * @throws RoleNotAllowedException If role exceeds max allowed.
	 */
	public function validateRole( string $role ): string {
		if ( ! $this->isRoleAllowed( $role ) ) {
			$max_role = $this->getMaxRole();
			throw new RoleNotAllowedException(
				sprintf(
					'Role "%s" cannot be assigned via API. Maximum allowed role is "%s". ' .
					'To allow higher roles, update the %s option in WordPress settings.',
					$role,
					$max_role,
					self::OPTION_NAME
				)
			);
		}

		return $role;
	}

	/**
	 * Get role level for comparison.
	 *
	 * @param string $role The role name.
	 * @return int The role level (higher = more privileged).
	 */
	public function getRoleLevel( string $role ): int {
		return self::ROLE_HIERARCHY[ $role ] ?? 0;
	}

	/**
	 * Check if a role is a standard WordPress role.
	 *
	 * @param string $role The role to check.
	 * @return bool True if standard role, false if custom.
	 */
	public function isStandardRole( string $role ): bool {
		return isset( self::ROLE_HIERARCHY[ $role ] );
	}
}
