<?php
/**
 * UpdatePost ability - updates an existing WordPress post.
 *
 * @package FAWpmcp\Abilities\Posts
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Posts;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\PostNotFoundException;
use FAWpmcp\Exceptions\PostTypeMismatchException;
use FAWpmcp\Exceptions\PostUpdateException;

/**
 * Ability to update an existing WordPress post.
 *
 * Features:
 * - Partial updates (only provided fields are updated)
 * - Input sanitization (title, content, excerpt)
 * - Category and tag updates
 * - Status changes
 *
 * @package FAWpmcp\Abilities\Posts
 */
final class UpdatePost extends AbstractAbility {
    /**
     * Valid post statuses.
     *
     * @var array<string>
     */
    private const VALID_STATUSES = array(
        'publish',
        'draft',
        'pending',
        'private',
        'future',
        'trash',
    );

    /**
     * Get the unique ability name.
     *
     * @return string Ability name.
     */
    public function getName(): string {
        return 'fa-wpmcp/update-post';
    }

    /**
     * Get the ability category.
     *
     * @return string Category name.
     */
    public function getCategory(): string {
        return 'posts-pages';
    }

    /**
     * Get the human-readable label.
     *
     * @return string Ability label.
     */
    public function getLabel(): string {
        return 'Update Post';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string {
        return 'Update an existing WordPress post, page, or custom post type. Supports partial updates: only provided fields are updated. Fields: title, content, excerpt, status, categories, tags. WARNING: Setting status to "trash" will move the post to trash.';
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
                'post_id'    => array(
                    'type'        => 'integer',
                    'description' => 'The ID of the post to update.',
                    'minimum'     => 1,
                ),
                'title'      => array(
                    'type'        => 'string',
                    'description' => 'The new post title.',
                ),
                'content'    => array(
                    'type'        => 'string',
                    'description' => 'The new post content (HTML allowed).',
                ),
                'excerpt'    => array(
                    'type'        => 'string',
                    'description' => 'The new post excerpt.',
                ),
                'status'     => array(
                    'type'        => 'string',
                    'description' => 'The new post status.',
                    'enum'        => self::VALID_STATUSES,
                ),
                'categories' => array(
                    'type'        => 'array',
                    'description' => 'Array of category IDs to assign.',
                    'items'       => array( 'type' => 'integer' ),
                ),
                'tags'       => array(
                    'type'        => 'array',
                    'description' => 'Array of tag names or IDs to assign.',
                    'items'       => array( 'type' => 'string' ),
                ),
                'post_type'  => array(
                    'type'        => 'string',
                    'description' => 'Optional post type to validate. If provided, will throw error if post type does not match.',
                ),
            ),
            'required'   => array( 'post_id' ),
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
                'post_id'   => array(
                    'type'        => 'integer',
                    'description' => 'The ID of the updated post.',
                ),
                'permalink' => array(
                    'type'        => 'string',
                    'description' => 'The permalink URL of the post.',
                ),
                'status'    => array(
                    'type'        => 'string',
                    'description' => 'The current status of the post.',
                ),
                'edit_url'  => array(
                    'type'        => 'string',
                    'description' => 'The URL to edit the post in WordPress admin.',
                ),
                'updated'   => array(
                    'type'        => 'boolean',
                    'description' => 'Whether the update was successful.',
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
        return 'edit_posts';
    }

    /**
     * Get the operation type.
     *
     * @return string 'write' for update operations.
     */
    public function getOperationType(): string {
        return 'write';
    }

    /**
     * Get ability annotations.
     *
     * Update operations that can trash content are marked as destructive.
     *
     * @return array<string, mixed> Annotations array.
     */
    public function getAnnotations(): array {
        $annotations                 = parent::getAnnotations();
        $annotations['destructive']  = true;
        return $annotations;
    }

    /**
     * Execute the ability.
     *
     * @param array<string, mixed> $input Validated input data.
     * @return array<string, mixed> Updated post data.
     * @throws RuntimeException If post not found or update fails.
     */
    public function doExecute( array $input ): array {
        $post_id = (int) $input['post_id'];

        // Side effect: verify post exists.
        $post = get_post( $post_id );

        if ( null === $post ) {
            throw new PostNotFoundException( 'Post not found' );
        }

        // Validate post_type if provided.
        if ( isset( $input['post_type'] ) && $input['post_type'] !== $post->post_type ) {
            throw new PostTypeMismatchException( 'Post type mismatch' );
        }

        // Pure transformation: build update data with sanitization.
        $update_data = $this->buildUpdateData( $input );

        // Side effect: update post in database.
        $result = wp_update_post( $update_data, true );

        // Error handling.
        if ( is_wp_error( $result ) ) {
            throw new PostUpdateException(
                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Internal exception message.
                'Failed to update post: ' . $result->get_error_message()
            );
        }

        // Pure transformation: format response.
        return $this->formatResponse( $post_id );
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
            'ID' => (int) $input['post_id'],
        );

        // Add title if provided.
        if ( isset( $input['title'] ) ) {
            $update_data['post_title'] = sanitize_text_field( $input['title'] );
        }

        // Add content if provided.
        if ( isset( $input['content'] ) ) {
            $update_data['post_content'] = wp_kses_post( $input['content'] );
        }

        // Add excerpt if provided.
        if ( isset( $input['excerpt'] ) ) {
            $update_data['post_excerpt'] = sanitize_textarea_field( $input['excerpt'] );
        }

        // Add status if provided and valid.
        if ( isset( $input['status'] ) ) {
            $validated_status = $this->validateStatus( $input['status'] );
            if ( null !== $validated_status ) {
                $update_data['post_status'] = $validated_status;
            }
        }

        // Add categories if provided.
        if ( isset( $input['categories'] ) && is_array( $input['categories'] ) ) {
            $update_data['post_category'] = array_map( 'intval', $input['categories'] );
        }

        // Add tags if provided.
        if ( isset( $input['tags'] ) && is_array( $input['tags'] ) ) {
            $update_data['tags_input'] = $input['tags'];
        }

        return $update_data;
    }

    /**
     * Validate post status.
     *
     * Pure function - returns valid status or null if invalid.
     *
     * @param string $status Input status.
     * @return string|null Valid status or null.
     */
    private function validateStatus( string $status ): ?string {
        if ( in_array( $status, self::VALID_STATUSES, true ) ) {
            return $status;
        }
        return null;
    }

    /**
     * Format the response after post update.
     *
     * @param int $post_id Updated post ID.
     * @return array<string, mixed> Response data.
     */
    private function formatResponse( int $post_id ): array {
        return array(
            'post_id'   => $post_id,
            'permalink' => get_permalink( $post_id ),
            'status'    => get_post_status( $post_id ),
            'edit_url'  => get_edit_post_link( $post_id, 'raw' ),
            'updated'   => true,
        );
    }
}
