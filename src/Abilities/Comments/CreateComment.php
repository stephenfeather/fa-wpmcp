<?php
/**
 * CreateComment ability - creates a new comment.
 *
 * @package FAWpmcp\Abilities\Comments
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Comments;

use FAWpmcp\Abilities\AbstractAbility;
use RuntimeException;

/**
 * Ability to create a new WordPress comment.
 *
 * Creates a comment with:
 * - Post ID (required)
 * - Author name (required)
 * - Author email (required)
 * - Comment content (required)
 *
 * Returns the created comment ID and link.
 *
 * @package FAWpmcp\Abilities\Comments
 */
final class CreateComment extends AbstractAbility {
	/**
	 * Get the unique ability name.
	 *
	 * @return string Ability name.
	 */
	public function get_name(): string {
		return 'fa-wpmcp/create-comment';
	}

	/**
	 * Get the ability category.
	 *
	 * @return string Category name.
	 */
	public function get_category(): string {
		return 'comments';
	}

	/**
	 * Get the human-readable label.
	 *
	 * @return string Ability label.
	 */
	public function get_label(): string {
		return 'Create Comment';
	}

	/**
	 * Get the ability description.
	 *
	 * @return string Description.
	 */
	public function get_description(): string {
		return 'Create a new WordPress comment on a post with author details and content.';
	}

	/**
	 * Get the input schema.
	 *
	 * @return array<string, mixed> JSON Schema array.
	 */
	public function get_input_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'post_id' => array(
					'type'        => 'integer',
					'description' => 'The ID of the post to comment on.',
					'minimum'     => 1,
				),
				'author'  => array(
					'type'        => 'string',
					'description' => 'Comment author name.',
					'minLength'   => 1,
				),
				'email'   => array(
					'type'        => 'string',
					'description' => 'Comment author email address.',
					'format'      => 'email',
				),
				'content' => array(
					'type'        => 'string',
					'description' => 'Comment content.',
					'minLength'   => 1,
				),
				'url'     => array(
					'type'        => 'string',
					'description' => 'Optional comment author URL.',
					'format'      => 'uri',
				),
			),
			'required'   => array( 'post_id', 'author', 'email', 'content' ),
		);
	}

	/**
	 * Get the output schema.
	 *
	 * @return array<string, mixed> JSON Schema array.
	 */
	public function get_output_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'comment_id' => array(
					'type'        => 'integer',
					'description' => 'The ID of the created comment.',
				),
				'link'       => array(
					'type'        => 'string',
					'description' => 'Permalink to the created comment.',
				),
			),
		);
	}

	/**
	 * Get the required WordPress capability.
	 *
	 * @return string WordPress capability name.
	 */
	public function get_required_capability(): string {
		return 'edit_posts';
	}

	/**
	 * Execute the ability.
	 *
	 * @param array<string, mixed> $input Validated input data.
	 * @return array<string, mixed> Created comment data.
	 * @throws RuntimeException If comment creation fails.
	 */
	public function do_execute( array $input ): array {
		$comment_data = array(
			'comment_post_ID'      => (int) $input['post_id'],
			'comment_author'       => (string) $input['author'],
			'comment_author_email' => (string) $input['email'],
			'comment_content'      => (string) $input['content'],
		);

		// Add optional URL if provided.
		if ( isset( $input['url'] ) ) {
			$comment_data['comment_author_url'] = (string) $input['url'];
		}

		// Insert comment.
		$comment_id = wp_insert_comment( $comment_data );

		if ( false === $comment_id || 0 === $comment_id ) {
			throw new RuntimeException( 'Failed to create comment' );
		}

		return array(
			'comment_id' => $comment_id,
			'link'       => get_comment_link( $comment_id ),
		);
	}
}
