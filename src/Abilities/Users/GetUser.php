<?php
/**
 * GetUser ability - retrieves a single user's data.
 *
 * @package FAWpmcp\Abilities\Users
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Users;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\UserNotFoundException;

/**
 * Ability to retrieve a single WordPress user.
 *
 * Supports lookup by:
 * - User ID
 * - Username
 * - Email
 *
 * @package FAWpmcp\Abilities\Users
 */
final class GetUser extends AbstractAbility {

	/**
	 * Get the unique ability name.
	 *
	 * @return string Ability name.
	 */
	public function getName(): string {
		return 'fa-wpmcp/get-user';
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
		return 'Get User';
	}

	/**
	 * Get the ability description.
	 *
	 * @return string Description.
	 */
	public function getDescription(): string {
		return 'Retrieve a single WordPress user by ID, username, or email address with full profile information.';
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
					'description' => 'User ID to retrieve.',
					'minimum'     => 1,
				),
				'username' => array(
					'type'        => 'string',
					'description' => 'Username (user_login) to retrieve.',
				),
				'email'    => array(
					'type'        => 'string',
					'description' => 'Email address to retrieve.',
					'format'      => 'email',
				),
			),
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
				'id'           => array( 'type' => 'integer' ),
				'username'     => array( 'type' => 'string' ),
				'email'        => array( 'type' => 'string' ),
				'display_name' => array( 'type' => 'string' ),
				'first_name'   => array( 'type' => 'string' ),
				'last_name'    => array( 'type' => 'string' ),
				'nickname'     => array( 'type' => 'string' ),
				'description'  => array( 'type' => 'string' ),
				'roles'        => array(
					'type'  => 'array',
					'items' => array( 'type' => 'string' ),
				),
				'registered'   => array( 'type' => 'string' ),
				'avatar_url'   => array( 'type' => 'string' ),
				'website'      => array( 'type' => 'string' ),
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
	 * @return array<string, mixed> User data.
	 * @throws UserNotFoundException If user not found.
	 */
	public function doExecute( array $input ): array {
		// Side effect: fetch user from database.
		$user = $this->fetchUser( $input );

		if ( ! $user || ! ( $user instanceof \WP_User ) || ! $user->exists() ) {
			throw new UserNotFoundException(
				'User not found with provided criteria.'
			);
		}

		// Pure transformation: format user data.
		return $this->formatUserData( $user );
	}

	/**
	 * Fetch user based on input criteria.
	 *
	 * @param array<string, mixed> $input Input parameters.
	 * @return \WP_User|false User object or false.
	 */
	private function fetchUser( array $input ) {
		// Priority: user_id > username > email.
		if ( isset( $input['user_id'] ) ) {
			return get_userdata( (int) $input['user_id'] );
		}

		if ( isset( $input['username'] ) ) {
			return get_user_by( 'login', $input['username'] );
		}

		// Check email or return false if no criteria provided.
		return isset( $input['email'] ) ? get_user_by( 'email', $input['email'] ) : false;
	}

	/**
	 * Format user data for output.
	 *
	 * Pure function - transforms WP_User into formatted array.
	 *
	 * @param \WP_User $user User object.
	 * @return array<string, mixed> Formatted user data.
	 */
	private function formatUserData( \WP_User $user ): array {
		return array(
			'id'           => $user->ID,
			'username'     => $user->user_login,
			'email'        => $user->user_email,
			'display_name' => $user->display_name,
			'first_name'   => $user->first_name,
			'last_name'    => $user->last_name,
			'nickname'     => $user->nickname,
			'description'  => $user->description,
			'roles'        => $user->roles,
			'registered'   => $user->user_registered,
			'avatar_url'   => get_avatar_url( $user->ID ),
			'website'      => $user->user_url,
		);
	}
}
