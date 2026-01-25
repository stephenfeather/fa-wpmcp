<?php
/**
 * CreateUser ability - creates a new WordPress user.
 *
 * @package FAWpmcp\Abilities\Users
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Users;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\RoleNotAllowedException;
use FAWpmcp\Exceptions\UserCreationException;

/**
 * Ability to create a new WordPress user.
 *
 * Features:
 * - Required: username, email
 * - Optional: password (auto-generated if not provided), role, first_name, last_name, display_name
 * - Sends new user notification email
 *
 * @package FAWpmcp\Abilities\Users
 */
final class CreateUser extends AbstractAbility {
	/**
	 * Role policy for validating role assignments.
	 *
	 * @var RolePolicy
	 */
	private RolePolicy $role_policy;

	/**
	 * Constructor.
	 *
	 * @param RolePolicy|null $role_policy Optional role policy instance.
	 */
	public function __construct( ?RolePolicy $role_policy = null ) {
		$this->role_policy = $role_policy ?? new RolePolicy();
	}

	/**
	 * Get the unique ability name.
	 *
	 * @return string Ability name.
	 */
	public function getName(): string {
		return 'fa-wpmcp/create-user';
	}

	/**
	 * Get the ability category.
	 *
	 * @return string Category name.
	 */
	public function getCategory(): string {
		return 'users';
	}

	/**
	 * Get the human-readable label.
	 *
	 * @return string Ability label.
	 */
	public function getLabel(): string {
		return 'Create User';
	}

	/**
	 * Get the ability description.
	 *
	 * @return string Description.
	 */
	public function getDescription(): string {
		return 'Create a new WordPress user with username, email, and optional settings like password, role, and profile information.';
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
				'username'     => array(
					'type'        => 'string',
					'description' => 'The username (user_login).',
				),
				'email'        => array(
					'type'        => 'string',
					'description' => 'The user email address.',
					'format'      => 'email',
				),
				'password'     => array(
					'type'        => 'string',
					'description' => 'The user password (auto-generated if not provided).',
				),
				'role'         => array(
					'type'        => 'string',
					'description' => sprintf(
						'User role (defaults to subscriber). Maximum assignable role: %s.',
						$this->role_policy->getMaxRole()
					),
					'enum'        => array_values( $this->role_policy->getAllowedRoles() ),
					'default'     => 'subscriber',
				),
				'first_name'   => array(
					'type'        => 'string',
					'description' => 'User first name.',
				),
				'last_name'    => array(
					'type'        => 'string',
					'description' => 'User last name.',
				),
				'display_name' => array(
					'type'        => 'string',
					'description' => 'Display name (defaults to username).',
				),
				'website'      => array(
					'type'        => 'string',
					'description' => 'User website URL.',
					'format'      => 'uri',
				),
				'description'  => array(
					'type'        => 'string',
					'description' => 'User biographical info.',
				),
			),
			'required'   => array( 'username', 'email' ),
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
				'user_id'   => array(
					'type'        => 'integer',
					'description' => 'The ID of the created user.',
				),
				'username'  => array(
					'type'        => 'string',
					'description' => 'The username of the created user.',
				),
				'email'     => array(
					'type'        => 'string',
					'description' => 'The email of the created user.',
				),
				'role'      => array(
					'type'        => 'string',
					'description' => 'The role of the created user.',
				),
				'edit_url'  => array(
					'type'        => 'string',
					'description' => 'The URL to edit the user in WordPress admin.',
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
		return 'create_users';
	}

	/**
	 * Get the operation type.
	 *
	 * @return string 'write' for create operations.
	 */
	public function getOperationType(): string {
		return 'write';
	}

	/**
	 * Get ability annotations.
	 *
	 * Create operations are non-idempotent - repeated calls create new resources.
	 *
	 * @return array<string, mixed> Annotations array.
	 */
	public function getAnnotations(): array {
		$annotations                = parent::getAnnotations();
		$annotations['idempotent']  = false;
		return $annotations;
	}

	/**
	 * Execute the ability.
	 *
	 * @param array<string, mixed> $input Validated input data.
	 * @return array<string, mixed> Created user data.
	 * @throws RoleNotAllowedException If role exceeds max allowed.
	 * @throws UserCreationException If user creation fails.
	 */
	public function doExecute( array $input ): array {
		// Pure transformation: build user data with sanitization.
		$user_data = $this->buildUserData( $input );

		// Side effect: insert user into database.
		$user_id = wp_insert_user( $user_data );

		// Error handling.
		if ( is_wp_error( $user_id ) ) {
			throw new UserCreationException(
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Internal exception message.
				'Failed to create user: ' . $user_id->get_error_message()
			);
		}

		// Pure transformation: format response.
		return $this->formatResponse( $user_id, $input );
	}

	/**
	 * Build user data array with sanitization.
	 *
	 * Pure function - sanitizes and transforms input into user data.
	 *
	 * @param array<string, mixed> $input Input parameters.
	 * @return array<string, mixed> Sanitized user data.
	 */
	private function buildUserData( array $input ): array {
		$user_data = array(
			'user_login' => sanitize_user( $input['username'] ),
			'user_email' => sanitize_email( $input['email'] ),
			'role'       => $this->validateRole( $input['role'] ?? 'subscriber' ),
		);

		// Add password if provided, otherwise WordPress will auto-generate.
		if ( isset( $input['password'] ) && '' !== $input['password'] ) {
			$user_data['user_pass'] = $input['password'];
		}

		// Add display name if provided, otherwise defaults to username.
		if ( isset( $input['display_name'] ) ) {
			$user_data['display_name'] = sanitize_text_field( $input['display_name'] );
		} else {
			$user_data['display_name'] = $user_data['user_login'];
		}

		// Add first name if provided.
		if ( isset( $input['first_name'] ) ) {
			$user_data['first_name'] = sanitize_text_field( $input['first_name'] );
		}

		// Add last name if provided.
		if ( isset( $input['last_name'] ) ) {
			$user_data['last_name'] = sanitize_text_field( $input['last_name'] );
		}

		// Add website if provided.
		if ( isset( $input['website'] ) ) {
			$user_data['user_url'] = esc_url_raw( $input['website'] );
		}

		// Add description if provided.
		if ( isset( $input['description'] ) ) {
			$user_data['description'] = sanitize_textarea_field( $input['description'] );
		}

		return $user_data;
	}

	/**
	 * Validate user role against policy.
	 *
	 * Validates that the role is allowed per the max API role configuration.
	 * Falls back to subscriber for unknown roles.
	 *
	 * @param string $role Input role.
	 * @return string Valid role.
	 * @throws RoleNotAllowedException If role exceeds max allowed.
	 */
	private function validateRole( string $role ): string {
		// First check if it's a standard role that exceeds max allowed.
		$this->role_policy->validateRole( $role );

		// For standard roles, return as-is. For unknown roles, default to subscriber.
		if ( $this->role_policy->isStandardRole( $role ) ) {
			return $role;
		}

		return 'subscriber';
	}

	/**
	 * Format the response after user creation.
	 *
	 * @param int                  $user_id Created user ID.
	 * @param array<string, mixed> $input   Original input.
	 * @return array<string, mixed> Response data.
	 */
	private function formatResponse( int $user_id, array $input ): array {
		$user = get_userdata( $user_id );

		return array(
			'user_id'  => $user_id,
			'username' => $user ? $user->user_login : $input['username'],
			'email'    => $user ? $user->user_email : $input['email'],
			'role'     => $input['role'] ?? 'subscriber',
			'edit_url' => get_edit_user_link( $user_id ),
		);
	}
}
