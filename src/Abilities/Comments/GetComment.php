<?php

/**
 * GetComment ability - retrieves a single comment by ID.
 *
 * @package FAWpmcp\Abilities\Comments
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Comments;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\CommentNotFoundException;

/**
 * Ability to retrieve a single WordPress comment by ID.
 *
 * Returns complete comment data including:
 * - Comment ID, post ID
 * - Author name and email
 * - Content and date
 * - Status and link
 *
 * @package FAWpmcp\Abilities\Comments
 */
final class GetComment extends AbstractAbility {

	/**
	 * Returns the ability identifier.
	 *
	 * @return string Ability name.
	 */
	public function getName(): string {
		return 'fa-wpmcp/get-comment';
	}

	/**
	 * Returns the ability category.
	 *
	 * @return string Category name.
	 */
	public function getCategory(): string {
		return 'comments';
	}

	/**
	 * Returns the display label.
	 *
	 * @return string Ability label.
	 */
	public function getLabel(): string {
		return 'Get Comment';
	}

	/**
	 * Returns the ability description.
	 *
	 * @return string Description.
	 */
	public function getDescription(): string {
		return 'Retrieve a WordPress comment by ID. Returns: id, post_id, author, email, content, date, status, link.';
	}

	/**
	 * Returns the JSON Schema for input validation.
	 *
	 * @return array<string, mixed> JSON Schema array.
	 */
	public function getInputSchema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'comment_id' => array(
					'type'        => 'integer',
					'description' => 'The ID of the comment to retrieve.',
					'minimum'     => 1,
				),
			),
			'required'   => array( 'comment_id' ),
		);
	}

	/**
	 * Returns the JSON Schema for output.
	 *
	 * @return array<string, mixed> JSON Schema array.
	 */
	public function getOutputSchema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'comment' => array(
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
		);
	}

	/**
	 * Returns the WordPress capability required.
	 *
	 * @return string WordPress capability name.
	 */
	public function getRequiredCapability(): string {
		return 'read';
	}

	/**
	 * Executes the ability.
	 *
	 * @param array<string, mixed> $input Validated input data.
	 * @return array<string, mixed> Comment data.
	 * @throws CommentNotFoundException If comment not found.
	 */
	public function doExecute( array $input ): array {
		$comment_id = (int) $input['comment_id'];

		// Fetch comment from database.
		$comment = get_comment( $comment_id );

		if ( null === $comment ) {
			throw new CommentNotFoundException( 'Comment not found' );
		}

		return array(
			'comment' => $this->formatComment( $comment ),
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
