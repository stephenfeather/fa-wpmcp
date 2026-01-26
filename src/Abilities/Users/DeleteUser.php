<?php
/**
 * DeleteUser ability - deletes a WordPress user.
 *
 * @package FAWpmcp\Abilities\Users
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Users;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\UserDeletionException;
use FAWpmcp\Exceptions\UserNotFoundException;

/**
 * Ability to delete a WordPress user.
 *
 * User deletion is always permanent (no trash).
 * Posts can optionally be reassigned to another user.
 *
 * @package FAWpmcp\Abilities\Users
 */
final class DeleteUser extends AbstractAbility {

	/**
	 * Get the unique ability name.
	 *
	 * @return string Ability name.
	 */
	public function getName(): string {
		return 'fa-wpmcp/delete-user';
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
		return 'Delete User';
	}

	/**
	 * Get the ability description.
	 *
	 * @return string Description.
	 */
	public function getDescription(): string {
		return 'Delete a WordPress user. User deletion is always permanent. Use reassign parameter to transfer posts to another user, otherwise posts will be deleted.';
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
				'user_id'  => array(
					'type'        => 'integer',
					'description' => 'The ID of the user to delete.',
					'minimum'     => 1,
				),
				'reassign' => array(
					'type'        => 'integer',
					'description' => 'User ID to reassign posts to. If not provided, posts will be deleted.',
					'minimum'     => 1,
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
				'user_id'    => array(
					'type'        => 'integer',
					'description' => 'The ID of the deleted user.',
				),
				'reassigned' => array(
					'type'        => array( 'integer', 'null' ),
					'description' => 'User ID that posts were reassigned to, or null if posts were deleted.',
				),
				'action'     => array(
					'type'        => 'string',
					'description' => 'The action performed (always "deleted" for users).',
					'enum'        => array( 'deleted' ),
				),
				'success'    => array(
					'type'        => 'boolean',
					'description' => 'Whether the operation succeeded.',
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
	 * @return string Operation type ('read' or 'write').
	 */
	public function getOperationType(): string {
		return 'write';
	}

	/**
	 * Get ability annotations.
	 *
	 * Marks this ability as destructive and non-idempotent.
	 *
	 * @return array<string, mixed> Annotations array.
	 */
	public function getAnnotations(): array {
		$annotations                = parent::getAnnotations();
		$annotations['destructive'] = true;
		$annotations['idempotent']  = false;
		return $annotations;
	}

	/**
	 * Execute the ability.
	 *
	 * @param array<string, mixed> $input Validated input data.
	 * @return array<string, mixed> Deletion result.
	 * @throws UserNotFoundException If user does not exist.
	 * @throws UserDeletionException If deletion fails or is not allowed.
	 */
	public function doExecute( array $input ): array {
		$user_id  = (int) $input['user_id'];
		$reassign = isset( $input['reassign'] ) ? (int) $input['reassign'] : null;

		// Verify user exists.
		$user = get_userdata( $user_id );
		if ( false === $user ) {
			throw new UserNotFoundException( "User {$user_id} not found." );
		}

		// Prevent self-deletion.
		$current_user_id = get_current_user_id();
		if ( $user_id === $current_user_id ) {
			throw new UserDeletionException( 'Cannot delete the currently logged-in user.' );
		}

		// If reassign is specified, verify the target user exists.
		if ( null !== $reassign ) {
			$reassign_user = get_userdata( $reassign );
			if ( false === $reassign_user ) {
				throw new UserNotFoundException( "Reassign target user {$reassign} not found." );
			}
		}

		// Require the user functions file for wp_delete_user if not already loaded.
		if ( ! function_exists( 'wp_delete_user' ) ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
		}

		// Delete the user.
		$result = wp_delete_user( $user_id, $reassign );

		if ( false === $result || true !== $result ) {
			throw new UserDeletionException( "Failed to delete user {$user_id}." );
		}

		return array(
			'user_id'    => $user_id,
			'reassigned' => $reassign,
			'action'     => 'deleted',
			'success'    => true,
		);
	}
}
