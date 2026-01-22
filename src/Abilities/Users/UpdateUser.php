<?php
/**
 * UpdateUser ability - updates an existing WordPress user.
 *
 * @package FAWpmcp\Abilities\Users
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Users;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\UserNotFoundException;
use FAWpmcp\Exceptions\UserUpdateException;

/**
 * Ability to update an existing WordPress user.
 *
 * Supports updating:
 * - Email, password
 * - Display name, first name, last name
 * - Role
 * - Website, description
 *
 * @package FAWpmcp\Abilities\Users
 */
final class UpdateUser extends AbstractAbility {
	/**
	 * Valid user roles.
	 *
	 * @var array<string>
	 */
	private const VALID_ROLES = array(
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
		return 'fa-wpmcp/update-user';
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
		return 'Update User';
	}

	/**
	 * Get the ability description.
	 *
	 * @return string Description.
	 */
	public function getDescription(): string {
		return 'Update an existing WordPress user profile including email, password, role, and profile information.';
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
				'user_id'      => array(
					'type'        => 'integer',
					'description' => 'User ID to update.',
					'minimum'     => 1,
				),
				'email'        => array(
					'type'        => 'string',
					'description' => 'New email address.',
					'format'      => 'email',
				),
				'password'     => array(
					'type'        => 'string',
					'description' => 'New password.',
				),
				'role'         => array(
					'type'        => 'string',
					'description' => 'New user role.',
					'enum'        => self::VALID_ROLES,
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
					'description' => 'Display name.',
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
			'required'   => array( 'user_id' ),
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
				'user_id'        => array(
					'type'        => 'integer',
					'description' => 'The ID of the updated user.',
				),
				'updated_fields' => array(
					'type'        => 'array',
					'description' => 'List of fields that were updated.',
					'items'       => array( 'type' => 'string' ),
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
		return 'edit_users';
	}

	/**
	 * Get the operation type.
	 *
	 * @return string 'write' for update operations.
	 */
	public function getOperationType(): string {
		return 'write';
	}

	/**
	 * Execute the ability.
	 *
	 * @param array<string, mixed> $input Validated input data.
	 * @return array<string, mixed> Update result.
	 * @throws UserNotFoundException If user not found.
	 * @throws UserUpdateException If update fails.
	 */
	public function doExecute( array $input ): array {
		$user_id = (int) $input['user_id'];

		// Verify user exists.
		$user = get_userdata( $user_id );
		if ( ! $user || ! $user->exists() ) {
			throw new UserNotFoundException(
				"User with ID {$user_id} not found."
			);
		}

		// Pure transformation: build update data with sanitization.
		$update_data = $this->buildUpdateData( $input );

		if ( empty( $update_data ) ) {
			// Nothing to update.
			return array(
				'user_id'        => $user_id,
				'updated_fields' => array(),
			);
		}

		// Add user ID to update data.
		$update_data['ID'] = $user_id;

		// Side effect: update user in database.
		$result = wp_update_user( $update_data );

		// Error handling.
		if ( is_wp_error( $result ) ) {
			throw new UserUpdateException(
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Internal exception message.
				'Failed to update user: ' . $result->get_error_message()
			);
		}

		// Pure transformation: format response.
		return $this->formatResponse( $user_id, $update_data );
	}

	/**
	 * Build update data array with sanitization.
	 *
	 * Pure function - sanitizes and transforms input into update data.
	 * Only includes fields that are provided in input.
	 *
	 * @param array<string, mixed> $input Input parameters.
	 * @return array<string, mixed> Sanitized update data.
	 */
	private function buildUpdateData( array $input ): array {
		$update_data = array();

		// Update email if provided.
		if ( isset( $input['email'] ) ) {
			$update_data['user_email'] = sanitize_email( $input['email'] );
		}

		// Update password if provided.
		if ( isset( $input['password'] ) && '' !== $input['password'] ) {
			$update_data['user_pass'] = $input['password'];
		}

		// Update role if provided.
		if ( isset( $input['role'] ) ) {
			$update_data['role'] = $this->validateRole( $input['role'] );
		}

		// Update display name if provided.
		if ( isset( $input['display_name'] ) ) {
			$update_data['display_name'] = sanitize_text_field( $input['display_name'] );
		}

		// Update first name if provided.
		if ( isset( $input['first_name'] ) ) {
			$update_data['first_name'] = sanitize_text_field( $input['first_name'] );
		}

		// Update last name if provided.
		if ( isset( $input['last_name'] ) ) {
			$update_data['last_name'] = sanitize_text_field( $input['last_name'] );
		}

		// Update website if provided.
		if ( isset( $input['website'] ) ) {
			$update_data['user_url'] = esc_url_raw( $input['website'] );
		}

		// Update description if provided.
		if ( isset( $input['description'] ) ) {
			$update_data['description'] = sanitize_textarea_field( $input['description'] );
		}

		return $update_data;
	}

	/**
	 * Validate user role.
	 *
	 * Pure function - returns valid role or original if invalid.
	 *
	 * @param string $role Input role.
	 * @return string Valid role.
	 */
	private function validateRole( string $role ): string {
		if ( in_array( $role, self::VALID_ROLES, true ) ) {
			return $role;
		}
		return $role; // Allow custom roles.
	}

	/**
	 * Format the response after user update.
	 *
	 * @param int                  $user_id     Updated user ID.
	 * @param array<string, mixed> $update_data Data that was updated.
	 * @return array<string, mixed> Response data.
	 */
	private function formatResponse( int $user_id, array $update_data ): array {
		// Extract field names (remove 'ID' from list).
		$updated_fields = array_keys( $update_data );
		$updated_fields = array_filter(
			$updated_fields,
			function ( $key ) {
				return 'ID' !== $key;
			}
		);

		return array(
			'user_id'        => $user_id,
			'updated_fields' => array_values( $updated_fields ),
		);
	}
}
