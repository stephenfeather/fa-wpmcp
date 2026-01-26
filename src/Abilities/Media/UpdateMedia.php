<?php

/**
 * UpdateMedia ability - updates media metadata.
 *
 * @package FAWpmcp\Abilities\Media
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Media;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\PostNotFoundException;
use FAWpmcp\Exceptions\PostTypeMismatchException;
use FAWpmcp\Exceptions\PostUpdateException;

/**
 * Ability to update WordPress media item metadata.
 *
 * Features:
 * - Partial updates (only provided fields are updated)
 * - Update title, caption, description, alt text
 * - Input sanitization
 *
 * @package FAWpmcp\Abilities\Media
 */
final class UpdateMedia extends AbstractAbility {

	/**
	 * Returns the ability identifier.
	 *
	 * @return string Ability name.
	 */
	public function getName(): string {
		return 'fa-wpmcp/update-media';
	}

	/**
	 * Returns the ability category.
	 *
	 * @return string Category name.
	 */
	public function getCategory(): string {
		return 'media';
	}

	/**
	 * Returns the display label.
	 *
	 * @return string Ability label.
	 */
	public function getLabel(): string {
		return 'Update Media';
	}

	/**
	 * Returns the ability description.
	 *
	 * @return string Description.
	 */
	public function getDescription(): string {
		return 'Update WordPress media library item metadata including title, caption, description, and alt text. Only provided fields will be updated.';
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
				'media_id'    => array(
					'type'        => 'integer',
					'description' => 'The ID of the media item to update.',
					'minimum'     => 1,
				),
				'title'       => array(
					'type'        => 'string',
					'description' => 'The new media title.',
				),
				'caption'     => array(
					'type'        => 'string',
					'description' => 'The new media caption.',
				),
				'description' => array(
					'type'        => 'string',
					'description' => 'The new media description.',
				),
				'alt_text'    => array(
					'type'        => 'string',
					'description' => 'The new alt text for the media (images only).',
				),
			),
			'required'   => array( 'media_id' ),
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
				'media_id' => array(
					'type'        => 'integer',
					'description' => 'The ID of the updated media item.',
				),
				'url'      => array(
					'type'        => 'string',
					'description' => 'The media URL.',
				),
				'updated'  => array(
					'type'        => 'boolean',
					'description' => 'Whether the update was successful.',
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
		return 'upload_files';
	}

	/**
	 * Returns the operation type.
	 *
	 * @return string 'write' for update operations.
	 */
	public function getOperationType(): string {
		return 'write';
	}

	/**
	 * Executes the ability.
	 *
	 * @param array<string, mixed> $input Validated input data.
	 * @return array<string, mixed> Updated media data.
	 * @throws RuntimeException If media not found or update fails.
	 */
	public function doExecute( array $input ): array {
		$media_id = (int) $input['media_id'];

		// Side effect: verify media exists.
		$post = get_post( $media_id );

		if ( null === $post ) {
			throw new PostNotFoundException( 'Media item not found' );
		}

		// Validate that it's an attachment.
		if ( 'attachment' !== $post->post_type ) {
			throw new PostTypeMismatchException( 'Post is not a media attachment' );
		}

		// Pure transformation: build update data with sanitization.
		$update_data = $this->buildUpdateData( $input );

		// Side effect: update post in database if there's post data to update.
		if ( count( $update_data ) > 1 ) { // More than just the ID.
			$result = wp_update_post( $update_data, true );

			// Error handling.
			if ( is_wp_error( $result ) ) {
				throw new PostUpdateException(
                    // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Internal exception message.
					'Failed to update media: ' . $result->get_error_message()
				);
			}
		}

		// Side effect: update alt text if provided.
		if ( isset( $input['alt_text'] ) ) {
			update_post_meta( $media_id, '_wp_attachment_image_alt', sanitize_text_field( $input['alt_text'] ) );
		}

		// Pure transformation: format response.
		return $this->formatResponse( $media_id );
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
		$update_data = array(
			'ID' => (int) $input['media_id'],
		);

		// Add title if provided.
		if ( isset( $input['title'] ) ) {
			$update_data['post_title'] = sanitize_text_field( $input['title'] );
		}

		// Add caption if provided.
		if ( isset( $input['caption'] ) ) {
			$update_data['post_excerpt'] = sanitize_textarea_field( $input['caption'] );
		}

		// Add description if provided.
		if ( isset( $input['description'] ) ) {
			$update_data['post_content'] = sanitize_textarea_field( $input['description'] );
		}

		return $update_data;
	}

	/**
	 * Format the response after media update.
	 *
	 * @param int $media_id Updated media ID.
	 * @return array<string, mixed> Response data.
	 */
	private function formatResponse( int $media_id ): array {
		return array(
			'media_id' => $media_id,
			'url'      => wp_get_attachment_url( $media_id ),
			'updated'  => true,
		);
	}
}
