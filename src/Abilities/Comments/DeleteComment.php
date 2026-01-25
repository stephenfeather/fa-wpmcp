<?php
/**
 * DeleteComment ability - deletes or trashes a WordPress comment.
 *
 * @package FAWpmcp\Abilities\Comments
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Comments;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\CommentDeletionException;
use FAWpmcp\Exceptions\CommentNotFoundException;

/**
 * Ability to delete a WordPress comment.
 *
 * By default, comments are moved to trash (recoverable).
 * Use force=true to permanently delete.
 *
 * @package FAWpmcp\Abilities\Comments
 */
final class DeleteComment extends AbstractAbility {
	/**
	 * Get the unique ability name.
	 *
	 * @return string Ability name.
	 */
	public function getName(): string {
		return 'fa-wpmcp/delete-comment';
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
		return 'Delete Comment';
	}

	/**
	 * Get the ability description.
	 *
	 * @return string Description.
	 */
	public function getDescription(): string {
		return 'Delete a WordPress comment. By default, moves to trash (recoverable). Set force=true to permanently delete.';
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
				'comment_id' => array(
					'type'        => 'integer',
					'description' => 'The ID of the comment to delete.',
					'minimum'     => 1,
				),
				'force'      => array(
					'type'        => 'boolean',
					'description' => 'If true, permanently delete instead of trashing. Default: false.',
					'default'     => false,
				),
			),
			'required'   => array( 'comment_id' ),
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
				'comment_id' => array(
					'type'        => 'integer',
					'description' => 'The ID of the deleted comment.',
				),
				'action'     => array(
					'type'        => 'string',
					'description' => 'The action performed: "trashed" or "deleted".',
					'enum'        => array( 'trashed', 'deleted' ),
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
		return 'moderate_comments';
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
	 * @throws CommentNotFoundException If comment does not exist.
	 * @throws CommentDeletionException If deletion fails.
	 */
	public function doExecute( array $input ): array {
		$comment_id = (int) $input['comment_id'];
		$force      = (bool) ( $input['force'] ?? false );

		// Verify comment exists.
		$comment = get_comment( $comment_id );
		if ( ! $comment ) {
			throw new CommentNotFoundException( "Comment {$comment_id} not found." );
		}

		if ( $force ) {
			// Permanent deletion.
			$result = wp_delete_comment( $comment_id, true );
			$action = 'deleted';
		} else {
			// Move to trash (recoverable).
			$result = wp_trash_comment( $comment_id );
			$action = 'trashed';
		}

		if ( false === $result ) {
			throw new CommentDeletionException( "Failed to {$action} comment {$comment_id}." );
		}

		return array(
			'comment_id' => $comment_id,
			'action'     => $action,
			'success'    => true,
		);
	}
}
