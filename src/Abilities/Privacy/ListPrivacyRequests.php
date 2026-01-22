<?php
/**
 * ListPrivacyRequests ability - retrieves privacy requests.
 *
 * @package FAWpmcp\Abilities\Privacy
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Privacy;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Ability to list privacy requests (export and erasure) with pagination.
 *
 * Supports filtering by:
 * - Request type (export_personal_data, remove_personal_data)
 * - Request status (request-pending, request-confirmed, request-failed, request-completed)
 * - Pagination
 *
 * @package FAWpmcp\Abilities\Privacy
 */
final class ListPrivacyRequests extends AbstractAbility {
	/**
	 * Maximum requests per page limit.
	 *
	 * @var int
	 */
	private const MAX_PER_PAGE = 100;

	/**
	 * Default requests per page.
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
		return 'fa-wpmcp/list-privacy-requests';
	}

	/**
	 * Get the ability category.
	 *
	 * @return string Category name.
	 */
	public function getCategory(): string {
		return 'privacy';
	}

	/**
	 * Get the human-readable label.
	 *
	 * @return string Ability label.
	 */
	public function getLabel(): string {
		return 'List Privacy Requests';
	}

	/**
	 * Get the ability description.
	 *
	 * @return string Description.
	 */
	public function getDescription(): string {
		return 'Retrieve a paginated list of privacy requests (export and erasure) with optional filtering.';
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
					'description' => 'Number of requests per page (max 100).',
					'minimum'     => 1,
					'maximum'     => self::MAX_PER_PAGE,
					'default'     => self::DEFAULT_PER_PAGE,
				),
				'type'     => array(
					'type'        => 'string',
					'description' => 'Filter by request type.',
					'enum'        => array( 'export_personal_data', 'remove_personal_data' ),
				),
				'status'   => array(
					'type'        => 'string',
					'description' => 'Filter by request status.',
					'enum'        => array( 'request-pending', 'request-confirmed', 'request-failed', 'request-completed' ),
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
				'requests'     => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'id'           => array( 'type' => 'integer' ),
							'email'        => array( 'type' => 'string' ),
							'type'         => array( 'type' => 'string' ),
							'status'       => array( 'type' => 'string' ),
							'created_at'   => array( 'type' => 'string' ),
							'confirmed_at' => array( 'type' => 'string' ),
						),
					),
				),
				'total'        => array(
					'type'        => 'integer',
					'description' => 'Total number of requests matching query.',
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
					'description' => 'Number of requests per page.',
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
		return 'manage_options';
	}

	/**
	 * Execute the ability.
	 *
	 * @param array<string, mixed> $input Validated input data.
	 * @return array<string, mixed> Requests list with pagination.
	 */
	public function doExecute( array $input ): array {
		$query_args = $this->buildQueryArgs( $input );

		// Get privacy requests.
		$posts = get_posts( $query_args );

		// Get total count.
		$count_result = wp_count_posts( 'user_request' );
		$total        = $this->getTotalRequests( $count_result, $input );

		// Format results.
		return $this->formatResults( $posts, $total, $input );
	}

	/**
	 * Build get_posts() arguments from input.
	 *
	 * @param array<string, mixed> $input Input parameters.
	 * @return array<string, mixed> get_posts() arguments.
	 */
	private function buildQueryArgs( array $input ): array {
		$per_page = isset( $input['per_page'] )
			? min( (int) $input['per_page'], self::MAX_PER_PAGE )
			: self::DEFAULT_PER_PAGE;

		$page = $input['page'] ?? 1;

		$args = array(
			'post_type'      => 'user_request',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		// Add status filter.
		if ( isset( $input['status'] ) && '' !== $input['status'] ) {
			$args['post_status'] = $input['status'];
		} else {
			$args['post_status'] = array( 'request-pending', 'request-confirmed', 'request-failed', 'request-completed' );
		}

		// Add type filter via meta_query.
		if ( isset( $input['type'] ) && '' !== $input['type'] ) {
			$args['meta_query'] = array(
				array(
					'key'   => 'action_name',
					'value' => $input['type'],
				),
			);
		}

		return $args;
	}

	/**
	 * Get total requests matching filters.
	 *
	 * @param object               $count_result Result from wp_count_posts().
	 * @param array<string, mixed> $input        Input parameters.
	 * @return int Total requests.
	 */
	private function getTotalRequests( object $count_result, array $input ): int {
		// If filtering by status, use status-specific count.
		if ( isset( $input['status'] ) && '' !== $input['status'] ) {
			$status = $input['status'];
			return isset( $count_result->$status ) ? (int) $count_result->$status : 0;
		}

		// Sum all statuses.
		$total = 0;
		foreach ( array( 'request-pending', 'request-confirmed', 'request-failed', 'request-completed' ) as $status ) {
			if ( isset( $count_result->$status ) ) {
				$total += (int) $count_result->$status;
			}
		}

		return $total;
	}

	/**
	 * Format get_posts() results into output array.
	 *
	 * @param array<\WP_Post>      $posts Post objects.
	 * @param int                  $total Total requests matching query.
	 * @param array<string, mixed> $input Original input parameters.
	 * @return array<string, mixed> Formatted results.
	 */
	private function formatResults( array $posts, int $total, array $input ): array {
		$formatted_requests = array();

		foreach ( $posts as $post ) {
			$formatted_requests[] = $this->formatRequestItem( $post );
		}

		$per_page = isset( $input['per_page'] )
			? min( (int) $input['per_page'], self::MAX_PER_PAGE )
			: self::DEFAULT_PER_PAGE;

		$pages = $per_page > 0 ? (int) ceil( $total / $per_page ) : 0;

		return array(
			'requests'     => $formatted_requests,
			'total'        => $total,
			'pages'        => $pages,
			'current_page' => $input['page'] ?? 1,
			'per_page'     => $per_page,
		);
	}

	/**
	 * Format a single request for list output.
	 *
	 * @param \WP_Post $post Request post object.
	 * @return array<string, mixed> Formatted request data.
	 */
	private function formatRequestItem( \WP_Post $post ): array {
		$email               = get_post_meta( $post->ID, '_wp_user_request_user_email', true );
		$action_name         = get_post_meta( $post->ID, 'action_name', true );
		$confirmed_timestamp = get_post_meta( $post->ID, '_wp_user_request_confirmed_timestamp', true );
		$confirmed_at        = $confirmed_timestamp ? gmdate( 'Y-m-d H:i:s', (int) $confirmed_timestamp ) : null;

		return array(
			'id'           => $post->ID,
			'email'        => $email,
			'type'         => $action_name,
			'status'       => $post->post_status,
			'created_at'   => $post->post_date,
			'confirmed_at' => $confirmed_at,
		);
	}
}
