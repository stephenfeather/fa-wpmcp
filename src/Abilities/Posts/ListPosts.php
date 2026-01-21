<?php
/**
 * ListPosts ability - retrieves a paginated list of posts.
 *
 * @package FAWpmcp\Abilities\Posts
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Posts;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Ability to list WordPress posts with pagination and filtering.
 *
 * Supports:
 * - Pagination (page, per_page with max 100)
 * - Status filtering (publish, draft, etc.)
 * - Author filtering
 * - Category filtering
 * - Search query
 * - Ordering
 *
 * @package FAWpmcp\Abilities\Posts
 */
final class ListPosts extends AbstractAbility {
    /**
     * Maximum posts per page limit.
     *
     * @var int
     */
    private const MAX_PER_PAGE = 100;

    /**
     * Default posts per page.
     *
     * @var int
     */
    private const DEFAULT_PER_PAGE = 10;

    /**
     * Get the unique ability name.
     *
     * @return string Ability name.
     */
    public function get_name(): string {
        return 'fa-wpmcp/list-posts';
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
        return 'List Posts';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function get_description(): string {
        return 'Retrieve a paginated list of WordPress posts, pages, or custom post types with optional filtering by post type, status, author, category, and search term.';
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
                'page'     => array(
                    'type'        => 'integer',
                    'description' => 'Page number for pagination.',
                    'minimum'     => 1,
                    'default'     => 1,
                ),
                'per_page' => array(
                    'type'        => 'integer',
                    'description' => 'Number of posts per page (max 100).',
                    'minimum'     => 1,
                    'maximum'     => self::MAX_PER_PAGE,
                    'default'     => self::DEFAULT_PER_PAGE,
                ),
                'status'   => array(
                    'type'        => 'string',
                    'description' => 'Post status filter (publish, draft, pending, private, future, trash, any).',
                    'enum'        => array( 'publish', 'draft', 'pending', 'private', 'future', 'trash', 'any' ),
                    'default'     => 'publish',
                ),
                'author'   => array(
                    'type'        => 'integer',
                    'description' => 'Filter by author user ID.',
                    'minimum'     => 1,
                ),
                'category' => array(
                    'type'        => 'integer',
                    'description' => 'Filter by category ID.',
                    'minimum'     => 1,
                ),
                'search'   => array(
                    'type'        => 'string',
                    'description' => 'Search term to filter posts.',
                ),
                'orderby'  => array(
                    'type'        => 'string',
                    'description' => 'Field to order by.',
                    'enum'        => array( 'date', 'title', 'modified', 'ID', 'author', 'name' ),
                    'default'     => 'date',
                ),
                'order'     => array(
                    'type'        => 'string',
                    'description' => 'Sort order.',
                    'enum'        => array( 'ASC', 'DESC' ),
                    'default'     => 'DESC',
                ),
                'post_type' => array(
                    'type'        => 'string',
                    'description' => 'Post type to query (post, page, or custom post type).',
                    'default'     => 'post',
                ),
            ),
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
                'posts'        => array(
                    'type'  => 'array',
                    'items' => array(
                        'type'       => 'object',
                        'properties' => array(
                            'id'        => array( 'type' => 'integer' ),
                            'title'     => array( 'type' => 'string' ),
                            'excerpt'   => array( 'type' => 'string' ),
                            'status'    => array( 'type' => 'string' ),
                            'type'      => array( 'type' => 'string' ),
                            'slug'      => array( 'type' => 'string' ),
                            'permalink' => array( 'type' => 'string' ),
                            'date'      => array( 'type' => 'string' ),
                            'modified'  => array( 'type' => 'string' ),
                            'author'    => array(
                                'type'       => 'object',
                                'properties' => array(
                                    'id'   => array( 'type' => 'integer' ),
                                    'name' => array( 'type' => 'string' ),
                                ),
                            ),
                        ),
                    ),
                ),
                'total'        => array(
                    'type'        => 'integer',
                    'description' => 'Total number of posts matching query.',
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
                    'description' => 'Number of posts per page.',
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
        return 'read';
    }

    /**
     * Execute the ability.
     *
     * @param array<string, mixed> $input Validated input data.
     * @return array<string, mixed> Posts list with pagination.
     */
    public function do_execute( array $input ): array {
        // Pure transformation: build query args.
        $query_args = $this->build_query_args( $input );

        // Side effect: execute WP_Query.
        $query = new \WP_Query( $query_args );

        // Pure transformation: format results.
        $result = $this->format_results( $query, $input );

        // Side effect: reset post data.
        wp_reset_postdata();

        return $result;
    }

    /**
     * Build WP_Query arguments from input.
     *
     * Pure function - transforms input into query args without side effects.
     *
     * @param array<string, mixed> $input Input parameters.
     * @return array<string, mixed> WP_Query arguments.
     */
    private function build_query_args( array $input ): array {
        $per_page = isset( $input['per_page'] )
            ? min( (int) $input['per_page'], self::MAX_PER_PAGE )
            : self::DEFAULT_PER_PAGE;

        $args = array(
            'post_type'      => $input['post_type'] ?? 'post',
            'post_status'    => $input['status'] ?? 'publish',
            'paged'          => $input['page'] ?? 1,
            'posts_per_page' => $per_page,
            'orderby'        => $input['orderby'] ?? 'date',
            'order'          => $input['order'] ?? 'DESC',
        );

        // Add optional filters.
        if ( isset( $input['author'] ) ) {
            $args['author'] = (int) $input['author'];
        }

        if ( isset( $input['category'] ) ) {
            $args['cat'] = (int) $input['category'];
        }

        if ( isset( $input['search'] ) && '' !== $input['search'] ) {
            $args['s'] = $input['search'];
        }

        return $args;
    }

    /**
     * Format WP_Query results into output array.
     *
     * Pure function - transforms query results without side effects.
     *
     * @param \WP_Query            $query WP_Query instance.
     * @param array<string, mixed> $input Original input parameters.
     * @return array<string, mixed> Formatted results.
     */
    private function format_results( \WP_Query $query, array $input ): array {
        $posts = array();

        foreach ( $query->posts as $post ) {
            $posts[] = $this->format_post_item( $post );
        }

        $per_page = isset( $input['per_page'] )
            ? min( (int) $input['per_page'], self::MAX_PER_PAGE )
            : self::DEFAULT_PER_PAGE;

        return array(
            'posts'        => $posts,
            'total'        => (int) $query->found_posts,
            'pages'        => (int) $query->max_num_pages,
            'current_page' => $input['page'] ?? 1,
            'per_page'     => $per_page,
        );
    }

    /**
     * Format a single post for list output.
     *
     * Pure function - transforms post object into formatted array.
     *
     * @param \WP_Post $post Post object.
     * @return array<string, mixed> Formatted post data.
     */
    private function format_post_item( \WP_Post $post ): array {
        return array(
            'id'        => $post->ID,
            'title'     => $post->post_title,
            'excerpt'   => $post->post_excerpt,
            'status'    => $post->post_status,
            'type'      => $post->post_type,
            'slug'      => $post->post_name,
            'permalink' => get_permalink( $post->ID ),
            'date'      => $post->post_date,
            'modified'  => $post->post_modified,
            'author'    => array(
                'id'   => (int) $post->post_author,
                'name' => get_the_author_meta( 'display_name', (int) $post->post_author ),
            ),
        );
    }
}
