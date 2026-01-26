<?php
/**
 * ListComments ability - retrieves a paginated list of comments.
 *
 * @package FAWpmcp\Abilities\Comments
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Comments;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Ability to retrieve a paginated list of WordPress comments.
 *
 * Supports filtering by:
 * - Post ID
 * - Comment status (approved, hold, spam, trash)
 * - Pagination (page and per_page)
 *
 * @package FAWpmcp\Abilities\Comments
 */
final class ListComments extends AbstractAbility {

	/**
	 * Get the unique ability name.
	 *
	 * @return string Ability name.
	 */
	public function getName(): string {
		return 'fa-wpmcp/list-comments';
	}

	/**
	 * Get the ability category.
	 *
	 * @return string Category name.
	 */
	public function getCategory(): string {
		return 'comments';
	}

	/**
	 * Get the human-readable label.
	 *
	 * @return string Ability label.
	 */
	public function getLabel(): string {
		return 'List Comments';
	}

	/**
	 * Get the ability description.
	 *
	 * @return string Description.
	 */
	public function getDescription(): string {
		return 'List WordPress comments with pagination. Defaults: page=1, per_page=10 (max 100), status=approve. Filters: post_id, status (approve, hold, spam, trash, all). Returns: comments (id, post_id, author, email, content, date, status, link), total, page, per_page.';
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
					'description' => 'Page number for pagination (1-based).',
					'default'     => 1,
					'minimum'     => 1,
				),
				'per_page' => array(
					'type'        => 'integer',
					'description' => 'Number of comments per page.',
					'default'     => 10,
					'minimum'     => 1,
					'maximum'     => 100,
				),
				'post_id'  => array(
					'type'        => 'integer',
					'description' => 'Optional post ID to filter comments for a specific post.',
					'minimum'     => 1,
				),
				'status'   => array(
					'type'        => 'string',
					'description' => 'Comment status (approve, hold, spam, trash).',
					'enum'        => array( 'approve', 'hold', 'spam', 'trash', 'all' ),
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
				'comments' => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'id'      => array( 'type' => 'integer' ),
							'post_id' => array( 'type' => 'integer' ),
							'author'  => array( 'type' => 'string' ),
							'email'   => array( 'type' => 'string' ),
							'content' => array( 'type' => 'string' ),
							'date'    => array( 'type' => 'string' ),
							'status'  => array( 'type' => 'string' ),
							'link'    => array( 'type' => 'string' ),
						),
					),
				),
				'total'    => array(
					'type'        => 'integer',
					'description' => 'Total number of comments matching the query.',
				),
				'page'     => array(
					'type'        => 'integer',
					'description' => 'Current page number.',
				),
				'per_page' => array(
					'type'        => 'integer',
					'description' => 'Number of comments per page.',
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
		return 'read';
	}

	/**
	 * Execute the ability.
	 *
	 * @param array<string, mixed> $input Validated input data.
	 * @return array<string, mixed> Comment list data.
	 */
	public function doExecute( array $input ): array {
		$page     = $input['page'] ?? 1;
		$per_page = $input['per_page'] ?? 10;
		$post_id  = $input['post_id'] ?? null;
		$status   = $input['status'] ?? 'approve';

		// Build query args.
		$args = array(
			'number' => $per_page,
			'offset' => ( $page - 1 ) * $per_page,
			'status' => $status,
		);

		if ( null !== $post_id ) {
			$args['post_id'] = $post_id;
		}

		// Fetch comments.
		$comments = get_comments( $args );

		// Get total count.
		$count_args = $post_id ? $post_id : 0;
		$counts     = wp_count_comments( $count_args );

		// Map status to count property.
		$total = match ( $status ) {
			'approve' => (int) $counts->approved,
			'hold'    => (int) $counts->moderated,
			'spam'    => (int) $counts->spam,
			'trash'   => (int) $counts->trash,
			'all'     => (int) $counts->total_comments,
			default   => (int) $counts->approved,
		};

		return array(
			'comments' => array_map( array( $this, 'formatComment' ), $comments ),
			'total'    => $total,
			'page'     => $page,
			'per_page' => $per_page,
		);
	}

	/**
	 * Format a WP_Comment object into output array.
	 *
	 * @param \WP_Comment|object $comment The comment object.
	 * @return array<string, mixed> Formatted comment data.
	 */
	private function formatComment( object $comment ): array {
		return array(
			'id'      => (int) $comment->comment_ID,
			'post_id' => (int) $comment->comment_post_ID,
			'author'  => $comment->comment_author,
			'email'   => $comment->comment_author_email,
			'content' => $comment->comment_content,
			'date'    => $comment->comment_date,
			'status'  => $comment->comment_approved,
			'link'    => get_comment_link( $comment ),
		);
	}
}
