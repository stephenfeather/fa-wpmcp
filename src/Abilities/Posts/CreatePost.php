<?php
/**
 * CreatePost ability - creates a new WordPress post.
 *
 * @package FAWpmcp\Abilities\Posts
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Posts;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\PostCreationException;

/**
 * Ability to create a new WordPress post.
 *
 * Features:
 * - Input sanitization (title, content, excerpt)
 * - Default status is draft for safety
 * - Category and tag assignment
 * - Author assignment
 *
 * @package FAWpmcp\Abilities\Posts
 */
final class CreatePost extends AbstractAbility {
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
    );

    /**
     * Get the unique ability name.
     *
     * @return string Ability name.
     */
    public function get_name(): string {
        return 'fa-wpmcp/create-post';
    }

    /**
     * Get the ability category.
     *
     * @return string Category name.
     */
    public function get_category(): string {
        return 'posts-pages';
    }

    /**
     * Get the human-readable label.
     *
     * @return string Ability label.
     */
    public function get_label(): string {
        return 'Create Post';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function get_description(): string {
        return 'Create a new WordPress post, page, or custom post type with title, content, and optional settings like status, categories, and tags. Defaults to draft status for safety.';
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
                'title'      => array(
                    'type'        => 'string',
                    'description' => 'The post title.',
                ),
                'content'    => array(
                    'type'        => 'string',
                    'description' => 'The post content (HTML allowed).',
                ),
                'excerpt'    => array(
                    'type'        => 'string',
                    'description' => 'The post excerpt.',
                ),
                'status'     => array(
                    'type'        => 'string',
                    'description' => 'Post status (defaults to draft).',
                    'enum'        => self::VALID_STATUSES,
                    'default'     => 'draft',
                ),
                'author'     => array(
                    'type'        => 'integer',
                    'description' => 'Author user ID.',
                    'minimum'     => 1,
                ),
                'categories' => array(
                    'type'        => 'array',
                    'description' => 'Array of category IDs.',
                    'items'       => array( 'type' => 'integer' ),
                ),
                'tags'       => array(
                    'type'        => 'array',
                    'description' => 'Array of tag names or IDs.',
                    'items'       => array( 'type' => 'string' ),
                ),
                'post_type'  => array(
                    'type'        => 'string',
                    'description' => 'Post type to create (post, page, or custom post type).',
                    'default'     => 'post',
                ),
            ),
            'required'   => array( 'title' ),
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
                'post_id'   => array(
                    'type'        => 'integer',
                    'description' => 'The ID of the created post.',
                ),
                'permalink' => array(
                    'type'        => 'string',
                    'description' => 'The permalink URL of the post.',
                ),
                'status'    => array(
                    'type'        => 'string',
                    'description' => 'The status of the created post.',
                ),
                'edit_url'  => array(
                    'type'        => 'string',
                    'description' => 'The URL to edit the post in WordPress admin.',
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
        return 'publish_posts';
    }

    /**
     * Get the operation type.
     *
     * @return string 'write' for create operations.
     */
    public function get_operation_type(): string {
        return 'write';
    }

    /**
     * Execute the ability.
     *
     * @param array<string, mixed> $input Validated input data.
     * @return array<string, mixed> Created post data.
     * @throws RuntimeException If post creation fails.
     */
    public function do_execute( array $input ): array {
        // Pure transformation: build post data with sanitization.
        $post_data = $this->build_post_data( $input );

        // Side effect: insert post into database.
        $post_id = wp_insert_post( $post_data, true );

        // Error handling.
        if ( is_wp_error( $post_id ) ) {
            throw new PostCreationException(
                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Internal exception message.
                'Failed to create post: ' . $post_id->get_error_message()
            );
        }

        // Pure transformation: format response.
        return $this->format_response( $post_id );
    }

    /**
     * Build post data array with sanitization.
     *
     * Pure function - sanitizes and transforms input into post data.
     *
     * @param array<string, mixed> $input Input parameters.
     * @return array<string, mixed> Sanitized post data.
     */
    private function build_post_data( array $input ): array {
        $post_data = array(
            'post_title'  => sanitize_text_field( $input['title'] ),
            'post_status' => $this->validate_status( $input['status'] ?? 'draft' ),
            'post_type'   => $input['post_type'] ?? 'post',
        );

        // Add content if provided.
        if ( isset( $input['content'] ) ) {
            $post_data['post_content'] = wp_kses_post( $input['content'] );
        }

        // Add excerpt if provided.
        if ( isset( $input['excerpt'] ) ) {
            $post_data['post_excerpt'] = sanitize_textarea_field( $input['excerpt'] );
        }

        // Add author if provided.
        if ( isset( $input['author'] ) ) {
            $post_data['post_author'] = (int) $input['author'];
        }

        // Add categories if provided.
        if ( isset( $input['categories'] ) && is_array( $input['categories'] ) ) {
            $post_data['post_category'] = array_map( 'intval', $input['categories'] );
        }

        // Add tags if provided.
        if ( isset( $input['tags'] ) && is_array( $input['tags'] ) ) {
            $post_data['tags_input'] = $input['tags'];
        }

        return $post_data;
    }

    /**
     * Validate post status.
     *
     * Pure function - returns valid status or default.
     *
     * @param string $status Input status.
     * @return string Valid status.
     */
    private function validate_status( string $status ): string {
        if ( in_array( $status, self::VALID_STATUSES, true ) ) {
            return $status;
        }
        return 'draft';
    }

    /**
     * Format the response after post creation.
     *
     * @param int $post_id Created post ID.
     * @return array<string, mixed> Response data.
     */
    private function format_response( int $post_id ): array {
        return array(
            'post_id'   => $post_id,
            'permalink' => get_permalink( $post_id ),
            'status'    => get_post_status( $post_id ),
            'edit_url'  => get_edit_post_link( $post_id, 'raw' ),
        );
    }
}
