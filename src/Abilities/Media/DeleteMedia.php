<?php

/**
 * DeleteMedia ability - deletes or trashes a WordPress media attachment.
 *
 * @package FAWpmcp\Abilities\Media
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Media;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\MediaDeletionException;
use FAWpmcp\Exceptions\MediaNotFoundException;

/**
 * Ability to delete a WordPress media attachment.
 *
 * By default, media items are moved to trash (recoverable).
 * Use force=true to permanently delete.
 *
 * @package FAWpmcp\Abilities\Media
 */
final class DeleteMedia extends AbstractAbility {

	/**
	 * Get the unique ability name.
	 *
	 * @return string Ability name.
	 */
	public function getName(): string {
		return 'fa-wpmcp/delete-media';
	}

	/**
	 * Get the ability category.
	 *
	 * @return string Category name.
	 */
	public function getCategory(): string {
		return 'media';
	}

	/**
	 * Get the human-readable label.
	 *
	 * @return string Ability label.
	 */
	public function getLabel(): string {
		return 'Delete Media';
	}

	/**
	 * Get the ability description.
	 *
	 * @return string Description.
	 */
	public function getDescription(): string {
		return 'Delete a WordPress media attachment. By default, moves to trash (recoverable). Set force=true to permanently delete.';
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
				'media_id' => array(
					'type'        => 'integer',
					'description' => 'The ID of the media attachment to delete.',
					'minimum'     => 1,
				),
				'force'    => array(
					'type'        => 'boolean',
					'description' => 'If true, permanently delete instead of trashing. Default: false.',
					'default'     => false,
				),
			),
			'required'   => array( 'media_id' ),
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
				'media_id' => array(
					'type'        => 'integer',
					'description' => 'The ID of the deleted media attachment.',
				),
				'action'   => array(
					'type'        => 'string',
					'description' => 'The action performed: "trashed" or "deleted".',
					'enum'        => array( 'trashed', 'deleted' ),
				),
				'success'  => array(
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
		return 'delete_posts';
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
	 * @throws MediaNotFoundException If media does not exist or is not an attachment.
	 * @throws MediaDeletionException If deletion fails.
	 */
	public function doExecute( array $input ): array {
		$media_id = (int) $input['media_id'];
		$force    = (bool) ( $input['force'] ?? false );

		// Verify media exists and is an attachment.
		$post = get_post( $media_id );
		if ( null === $post ) {
			throw new MediaNotFoundException( "Media {$media_id} not found." );
		}

		if ( 'attachment' !== $post->post_type ) {
			throw new MediaNotFoundException( "Post {$media_id} is not a media attachment." );
		}

		// wp_delete_attachment handles both trash and permanent delete.
		// When $force is false, it moves to trash; when true, it permanently deletes.
		$result = wp_delete_attachment( $media_id, $force );
		$action = $force ? 'deleted' : 'trashed';

		if ( false === $result || null === $result ) {
			throw new MediaDeletionException( "Failed to {$action} media {$media_id}." );
		}

		return array(
			'media_id' => $media_id,
			'action'   => $action,
			'success'  => true,
		);
	}
}
