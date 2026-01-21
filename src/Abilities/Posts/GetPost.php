<?php
/**
 * GetPost ability - retrieves a single post by ID.
 *
 * @package FAWpmcp\Abilities\Posts
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Posts;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\PostNotFoundException;
use FAWpmcp\Exceptions\PostTypeMismatchException;

/**
 * Ability to retrieve a single WordPress post by ID.
 *
 * Returns complete post data including:
 * - Basic post fields (title, content, status, etc.)
 * - Featured image URL
 * - Categories and tags
 * - Author information
 * - Permalinks and edit URLs
 *
 * @package FAWpmcp\Abilities\Posts
 */
final class GetPost extends AbstractAbility {
    /**
     * Get the unique ability name.
     *
     * @return string Ability name.
     */
    public function getName(): string {
        return 'fa-wpmcp/get-post';
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
        return 'Get Post';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string {
        return 'Retrieve a single WordPress post, page, or custom post type by ID with full details including categories, tags, featured image, and author information. Optionally validate post type.';
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
                'post_id'   => array(
                    'type'        => 'integer',
                    'description' => 'The ID of the post to retrieve.',
                    'minimum'     => 1,
                ),
                'post_type' => array(
                    'type'        => 'string',
                    'description' => 'Optional post type to validate (post, page, or custom post type). If provided, will throw error if post type does not match.',
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
                'post' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'id'             => array( 'type' => 'integer' ),
                        'title'          => array( 'type' => 'string' ),
                        'content'        => array( 'type' => 'string' ),
                        'excerpt'        => array( 'type' => 'string' ),
                        'status'         => array( 'type' => 'string' ),
                        'type'           => array( 'type' => 'string' ),
                        'slug'           => array( 'type' => 'string' ),
                        'permalink'      => array( 'type' => 'string' ),
                        'edit_url'       => array( 'type' => 'string' ),
                        'date'           => array( 'type' => 'string' ),
                        'modified'       => array( 'type' => 'string' ),
                        'featured_image' => array( 'type' => 'string' ),
                        'author'         => array(
                            'type'       => 'object',
                            'properties' => array(
                                'id'   => array( 'type' => 'integer' ),
                                'name' => array( 'type' => 'string' ),
                            ),
                        ),
                        'categories'     => array(
                            'type'  => 'array',
                            'items' => array(
                                'type'       => 'object',
                                'properties' => array(
                                    'id'   => array( 'type' => 'integer' ),
                                    'name' => array( 'type' => 'string' ),
                                    'slug' => array( 'type' => 'string' ),
                                ),
                            ),
                        ),
                        'tags'           => array(
                            'type'  => 'array',
                            'items' => array(
                                'type'       => 'object',
                                'properties' => array(
                                    'id'   => array( 'type' => 'integer' ),
                                    'name' => array( 'type' => 'string' ),
                                    'slug' => array( 'type' => 'string' ),
                                ),
                            ),
                        ),
                        'meta'           => array( 'type' => 'object' ),
                    ),
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
     * @return array<string, mixed> Post data.
     * @throws RuntimeException If post not found.
     */
    public function doExecute( array $input ): array {
        $post_id = (int) $input['post_id'];

        // Side effect: fetch post from database.
        $post = get_post( $post_id );

        if ( null === $post ) {
            throw new PostNotFoundException( 'Post not found' );
        }

        // Validate post_type if provided.
        if ( isset( $input['post_type'] ) && $input['post_type'] !== $post->post_type ) {
            throw new PostTypeMismatchException( 'Post type mismatch' );
        }

        // Pure transformation: format post data.
        return array(
            'post' => $this->formatPost( $post ),
        );
    }

    /**
     * Format a WP_Post object into output array.
     *
     * Pure function - transforms post data without side effects (except for WP API calls).
     *
     * @param \WP_Post $post The post object.
     * @return array<string, mixed> Formatted post data.
     */
    private function formatPost( \WP_Post $post ): array {
        $post_id = $post->ID;

        return array(
            'id'             => $post_id,
            'title'          => $post->post_title,
            'content'        => $post->post_content,
            'excerpt'        => $post->post_excerpt,
            'status'         => $post->post_status,
            'type'           => $post->post_type,
            'slug'           => $post->post_name,
            'permalink'      => get_permalink( $post_id ),
            'edit_url'       => get_edit_post_link( $post_id, 'raw' ),
            'date'           => $post->post_date,
            'modified'       => $post->post_modified,
            'featured_image' => $this->getFeaturedImageUrl( $post_id ),
            'author'         => $this->formatAuthor( (int) $post->post_author ),
            'categories'     => $this->formatCategories( $post_id ),
            'tags'           => $this->formatTags( $post_id ),
            'meta'           => $this->getPostMeta( $post_id ),
        );
    }

    /**
     * Get the featured image URL for a post.
     *
     * @param int $post_id Post ID.
     * @return string Featured image URL or empty string.
     */
    private function getFeaturedImageUrl( int $post_id ): string {
        $url = get_the_post_thumbnail_url( $post_id, 'full' );
        return is_string( $url ) ? $url : '';
    }

    /**
     * Format author data.
     *
     * Pure function - transforms author ID into formatted array.
     *
     * @param int $author_id Author user ID.
     * @return array<string, mixed> Author data.
     */
    private function formatAuthor( int $author_id ): array {
        return array(
            'id'   => $author_id,
            'name' => get_the_author_meta( 'display_name', $author_id ),
        );
    }

    /**
     * Format categories for a post.
     *
     * Pure function - transforms term objects into formatted arrays.
     *
     * @param int $post_id Post ID.
     * @return array<int, array<string, mixed>> Categories array.
     */
    private function formatCategories( int $post_id ): array {
        $categories = wp_get_post_categories( $post_id, array( 'fields' => 'all' ) );

        if ( ! is_array( $categories ) ) {
            return array();
        }

        return array_map(
            fn( $term ) => array(
                'id'   => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
            ),
            $categories
        );
    }

    /**
     * Format tags for a post.
     *
     * Pure function - transforms term objects into formatted arrays.
     *
     * @param int $post_id Post ID.
     * @return array<int, array<string, mixed>> Tags array.
     */
    private function formatTags( int $post_id ): array {
        $tags = wp_get_post_tags( $post_id, array( 'fields' => 'all' ) );

        if ( ! is_array( $tags ) ) {
            return array();
        }

        return array_map(
            fn( $term ) => array(
                'id'   => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
            ),
            $tags
        );
    }

    /**
     * Get post meta, filtering out internal keys.
     *
     * Pure function - filters and formats meta data.
     *
     * @param int $post_id Post ID.
     * @return array<string, mixed> Filtered meta data.
     */
    private function getPostMeta( int $post_id ): array {
        $meta = get_post_meta( $post_id, '', true );

        if ( ! is_array( $meta ) ) {
            return array();
        }

        // Filter out internal WordPress meta keys.
        $filtered = array();
        foreach ( $meta as $key => $value ) {
            // Skip internal keys that start with underscore.
            if ( strpos( $key, '_' ) === 0 ) {
                continue;
            }
            $filtered[ $key ] = is_array( $value ) && 1 === count( $value ) ? $value[0] : $value;
        }

        return $filtered;
    }
}
