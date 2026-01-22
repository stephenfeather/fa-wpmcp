<?php
/**
 * ListUsers ability - retrieves a paginated list of users.
 *
 * @package FAWpmcp\Abilities\Users
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Users;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Ability to list WordPress users with pagination and filtering.
 *
 * Supports:
 * - Pagination (page, per_page with max 100)
 * - Role filtering
 * - Search by name/email/username
 * - Ordering
 *
 * @package FAWpmcp\Abilities\Users
 */
final class ListUsers extends AbstractAbility {
	/**
	 * Maximum users per page limit.
	 *
	 * @var int
	 */
	private const MAX_PER_PAGE = 100;

	/**
	 * Default users per page.
	 *
	 * @var int
	 */
	private const DEFAULT_PER_PAGE = 10;

	/**
	 * Get the unique ability name.
	 *
	 * @return string Ability name.
	 */
	public function getName(): string {
		return 'fa-wpmcp/list-users';
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
		return 'List Users';
	}

	/**
	 * Get the ability description.
	 *
	 * @return string Description.
	 */
	public function getDescription(): string {
		return 'Retrieve a paginated list of WordPress users with optional filtering by role and search term.';
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
				'page'     => array(
					'type'        => 'integer',
					'description' => 'Page number for pagination.',
					'minimum'     => 1,
					'default'     => 1,
				),
				'per_page' => array(
					'type'        => 'integer',
					'description' => 'Number of users per page (max 100).',
					'minimum'     => 1,
					'maximum'     => self::MAX_PER_PAGE,
					'default'     => self::DEFAULT_PER_PAGE,
				),
				'role'     => array(
					'type'        => 'string',
					'description' => 'Filter by user role (administrator, editor, author, contributor, subscriber, or custom role).',
				),
				'search'   => array(
					'type'        => 'string',
					'description' => 'Search term to filter users by username, email, or display name.',
				),
				'orderby'  => array(
					'type'        => 'string',
					'description' => 'Field to order by.',
					'enum'        => array( 'ID', 'display_name', 'user_login', 'user_email', 'user_registered' ),
					'default'     => 'user_registered',
				),
				'order'    => array(
					'type'        => 'string',
					'description' => 'Sort order.',
					'enum'        => array( 'ASC', 'DESC' ),
					'default'     => 'ASC',
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
				'users'        => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'id'            => array( 'type' => 'integer' ),
							'username'      => array( 'type' => 'string' ),
							'email'         => array( 'type' => 'string' ),
							'display_name'  => array( 'type' => 'string' ),
							'first_name'    => array( 'type' => 'string' ),
							'last_name'     => array( 'type' => 'string' ),
							'roles'         => array(
								'type'  => 'array',
								'items' => array( 'type' => 'string' ),
							),
							'registered'    => array( 'type' => 'string' ),
							'avatar_url'    => array( 'type' => 'string' ),
						),
					),
				),
				'total'        => array(
					'type'        => 'integer',
					'description' => 'Total number of users matching query.',
				),
				'pages'        => array(
					'type'        => 'integer',
					'description' => 'Total number of pages.',
				),
				'current_page' => array(
					'type'        => 'integer',
					'description' => 'Current page number.',
				),
				'per_page'     => array(
					'type'        => 'integer',
					'description' => 'Number of users per page.',
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
	 * @return array<string, mixed> Users list with pagination.
	 */
	public function doExecute( array $input ): array {
		// Pure transformation: build query args.
		$query_args = $this->buildQueryArgs( $input );

		// Side effect: execute get_users().
		$users = get_users( $query_args );

		// Get total count for pagination (separate query without pagination).
		$count_args         = $this->buildCountArgs( $input );
		$total_users_result = count_users();
		$total_users        = $this->getTotalUsers( $count_args, $total_users_result );

		// Pure transformation: format results.
		return $this->formatResults( $users, $total_users, $input );
	}

	/**
	 * Build get_users() arguments from input.
	 *
	 * Pure function - transforms input into query args without side effects.
	 *
	 * @param array<string, mixed> $input Input parameters.
	 * @return array<string, mixed> get_users() arguments.
	 */
	private function buildQueryArgs( array $input ): array {
		$per_page = isset( $input['per_page'] )
			? min( (int) $input['per_page'], self::MAX_PER_PAGE )
			: self::DEFAULT_PER_PAGE;

		$page = $input['page'] ?? 1;

		$args = array(
			'number'  => $per_page,
			'offset'  => ( $page - 1 ) * $per_page,
			'orderby' => $input['orderby'] ?? 'user_registered',
			'order'   => $input['order'] ?? 'ASC',
		);

		// Add optional filters.
		if ( isset( $input['role'] ) && '' !== $input['role'] ) {
			$args['role'] = $input['role'];
		}

		if ( isset( $input['search'] ) && '' !== $input['search'] ) {
			$args['search']         = '*' . $input['search'] . '*';
			$args['search_columns'] = array( 'user_login', 'user_email', 'display_name' );
		}

		return $args;
	}

	/**
	 * Build count query arguments from input.
	 *
	 * Pure function - extracts role filter for count query.
	 *
	 * @param array<string, mixed> $input Input parameters.
	 * @return array<string, mixed> Count query arguments.
	 */
	private function buildCountArgs( array $input ): array {
		$args = array();

		if ( isset( $input['role'] ) && '' !== $input['role'] ) {
			$args['role'] = $input['role'];
		}

		if ( isset( $input['search'] ) && '' !== $input['search'] ) {
			$args['search'] = $input['search'];
		}

		return $args;
	}

	/**
	 * Get total users matching filters.
	 *
	 * Pure function - calculates total from count_users result.
	 *
	 * @param array<string, mixed> $count_args  Count arguments.
	 * @param object               $count_result Result from count_users().
	 * @return int Total users.
	 */
	private function getTotalUsers( array $count_args, object $count_result ): int {
		// If filtering by role, use role-specific count.
		if ( isset( $count_args['role'] ) ) {
			$role = $count_args['role'];
			return isset( $count_result->avail_roles[ $role ] )
				? (int) $count_result->avail_roles[ $role ]
				: 0;
		}

		// If searching, need to run separate query.
		if ( isset( $count_args['search'] ) ) {
			$search_args = array(
				'search'         => '*' . $count_args['search'] . '*',
				'search_columns' => array( 'user_login', 'user_email', 'display_name' ),
				'fields'         => 'ID',
			);
			$search_users = get_users( $search_args );
			return count( $search_users );
		}

		// No filters - return total users.
		return (int) $count_result->total_users;
	}

	/**
	 * Format get_users() results into output array.
	 *
	 * Pure function - transforms user results without side effects.
	 *
	 * @param array<\WP_User>      $users User objects.
	 * @param int                  $total Total users matching query.
	 * @param array<string, mixed> $input Original input parameters.
	 * @return array<string, mixed> Formatted results.
	 */
	private function formatResults( array $users, int $total, array $input ): array {
		$formatted_users = array();

		foreach ( $users as $user ) {
			$formatted_users[] = $this->formatUserItem( $user );
		}

		$per_page = isset( $input['per_page'] )
			? min( (int) $input['per_page'], self::MAX_PER_PAGE )
			: self::DEFAULT_PER_PAGE;

		$pages = $per_page > 0 ? (int) ceil( $total / $per_page ) : 0;

		return array(
			'users'        => $formatted_users,
			'total'        => $total,
			'pages'        => $pages,
			'current_page' => $input['page'] ?? 1,
			'per_page'     => $per_page,
		);
	}

	/**
	 * Format a single user for list output.
	 *
	 * Pure function - transforms user object into formatted array.
	 *
	 * @param \WP_User $user User object.
	 * @return array<string, mixed> Formatted user data.
	 */
	private function formatUserItem( \WP_User $user ): array {
		return array(
			'id'           => $user->ID,
			'username'     => $user->user_login,
			'email'        => $user->user_email,
			'display_name' => $user->display_name,
			'first_name'   => $user->first_name,
			'last_name'    => $user->last_name,
			'roles'        => $user->roles,
			'registered'   => $user->user_registered,
			'avatar_url'   => get_avatar_url( $user->ID ),
		);
	}
}
