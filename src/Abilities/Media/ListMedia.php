<?php

/**
 * ListMedia ability - retrieves a paginated list of media items.
 *
 * @package FAWpmcp\Abilities\Media
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Media;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Ability to list WordPress media library items with pagination and filtering.
 *
 * Supports:
 * - Pagination (page, per_page with max 100)
 * - MIME type filtering (image/*, video/*, audio/*, application/*)
 * - Search query
 * - Ordering by date, title, modified
 *
 * @package FAWpmcp\Abilities\Media
 */
final class ListMedia extends AbstractAbility
{
    /**
     * Maximum media items per page limit.
     *
     * @var int
     */
    private const MAX_PER_PAGE = 100;

    /**
     * Default media items per page.
     *
     * @var int
     */
    private const DEFAULT_PER_PAGE = 10;

    /**
     * Get the unique ability name.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/list-media';
    }

    /**
     * Get the ability category.
     *
     * @return string Category name.
     */
    public function getCategory(): string
    {
        return 'media';
    }

    /**
     * Get the human-readable label.
     *
     * @return string Ability label.
     */
    public function getLabel(): string
    {
        return 'List Media';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Retrieve a paginated list of WordPress media library items with optional filtering by MIME type and search term. Returns attachment metadata including URLs, dimensions, and file information.';
    }

    /**
     * Get the input schema.
     *
     * @return array<string, mixed> JSON Schema array.
     */
    public function getInputSchema(): array
    {
        return array(
            'type'       => 'object',
            'properties' => array(
                'page'      => array(
                    'type'        => 'integer',
                    'description' => 'Page number for pagination.',
                    'minimum'     => 1,
                    'default'     => 1,
                ),
                'per_page'  => array(
                    'type'        => 'integer',
                    'description' => 'Number of media items per page (max 100).',
                    'minimum'     => 1,
                    'maximum'     => self::MAX_PER_PAGE,
                    'default'     => self::DEFAULT_PER_PAGE,
                ),
                'mime_type' => array(
                    'type'        => 'string',
                    'description' => 'Filter by MIME type (e.g., image/jpeg, image/*, video/mp4).',
                ),
                'search'    => array(
                    'type'        => 'string',
                    'description' => 'Search term to filter media items by filename or title.',
                ),
                'orderby'   => array(
                    'type'        => 'string',
                    'description' => 'Field to order by.',
                    'enum'        => array( 'date', 'title', 'modified', 'ID' ),
                    'default'     => 'date',
                ),
                'order'     => array(
                    'type'        => 'string',
                    'description' => 'Sort order.',
                    'enum'        => array( 'ASC', 'DESC' ),
                    'default'     => 'DESC',
                ),
            ),
        );
    }

    /**
     * Get the output schema.
     *
     * @return array<string, mixed> JSON Schema array.
     */
    public function getOutputSchema(): array
    {
        return array(
            'type'       => 'object',
            'properties' => array(
                'media'        => array(
                    'type'  => 'array',
                    'items' => array(
                        'type'       => 'object',
                        'properties' => array(
                            'id'          => array( 'type' => 'integer' ),
                            'title'       => array( 'type' => 'string' ),
                            'filename'    => array( 'type' => 'string' ),
                            'url'         => array( 'type' => 'string' ),
                            'mime_type'   => array( 'type' => 'string' ),
                            'type'        => array( 'type' => 'string' ),
                            'date'        => array( 'type' => 'string' ),
                            'modified'    => array( 'type' => 'string' ),
                            'filesize'    => array( 'type' => 'integer' ),
                            'width'       => array( 'type' => 'integer' ),
                            'height'      => array( 'type' => 'integer' ),
                            'alt_text'    => array( 'type' => 'string' ),
                            'caption'     => array( 'type' => 'string' ),
                            'description' => array( 'type' => 'string' ),
                            'author'      => array(
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
                    'description' => 'Total number of media items matching query.',
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
                    'description' => 'Number of media items per page.',
                ),
            ),
        );
    }

    /**
     * Get the required WordPress capability.
     *
     * @return string WordPress capability name.
     */
    public function getRequiredCapability(): string
    {
        return 'upload_files';
    }

    /**
     * Execute the ability.
     *
     * @param array<string, mixed> $input Validated input data.
     * @return array<string, mixed> Media list with pagination.
     */
    public function doExecute(array $input): array
    {
        // Pure transformation: build query args.
        $query_args = $this->buildQueryArgs($input);

        // Side effect: execute WP_Query.
        $query = new \WP_Query($query_args);

        // Pure transformation: format results.
        $result = $this->formatResults($query, $input);

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
    private function buildQueryArgs(array $input): array
    {
        $per_page = isset($input['per_page'])
            ? min((int) $input['per_page'], self::MAX_PER_PAGE)
            : self::DEFAULT_PER_PAGE;

        $args = array(
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'paged'          => $input['page'] ?? 1,
            'posts_per_page' => $per_page,
            'orderby'        => $input['orderby'] ?? 'date',
            'order'          => $input['order'] ?? 'DESC',
        );

        // Add MIME type filter.
        if (isset($input['mime_type']) && '' !== $input['mime_type']) {
            $args['post_mime_type'] = $input['mime_type'];
        }

        // Add search filter.
        if (isset($input['search']) && '' !== $input['search']) {
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
    private function formatResults(\WP_Query $query, array $input): array
    {
        $media = array();

        foreach ($query->posts as $post) {
            $media[] = $this->formatMediaItem($post);
        }

        $per_page = isset($input['per_page'])
            ? min((int) $input['per_page'], self::MAX_PER_PAGE)
            : self::DEFAULT_PER_PAGE;

        return array(
            'media'        => $media,
            'total'        => (int) $query->found_posts,
            'pages'        => (int) $query->max_num_pages,
            'current_page' => $input['page'] ?? 1,
            'per_page'     => $per_page,
        );
    }

    /**
     * Format a single media item for list output.
     *
     * Pure function - transforms attachment post object into formatted array.
     *
     * @param \WP_Post $post Attachment post object.
     * @return array<string, mixed> Formatted media data.
     */
    private function formatMediaItem(\WP_Post $post): array
    {
        $attachment_id = $post->ID;
        $metadata      = wp_get_attachment_metadata($attachment_id);

        // Get file size.
        $file     = get_attached_file($attachment_id);
        $filesize = $file && file_exists($file) ? filesize($file) : 0;

        // Get dimensions for images.
        $width  = isset($metadata['width']) ? (int) $metadata['width'] : 0;
        $height = isset($metadata['height']) ? (int) $metadata['height'] : 0;

        return array(
            'id'          => $attachment_id,
            'title'       => $post->post_title,
            'filename'    => basename(get_attached_file($attachment_id)),
            'url'         => wp_get_attachment_url($attachment_id),
            'mime_type'   => $post->post_mime_type,
            'type'        => $this->getMediaType($post->post_mime_type),
            'date'        => $post->post_date,
            'modified'    => $post->post_modified,
            'filesize'    => $filesize,
            'width'       => $width,
            'height'      => $height,
            'alt_text'    => get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
            'caption'     => $post->post_excerpt,
            'description' => $post->post_content,
            'author'      => array(
                'id'   => (int) $post->post_author,
                'name' => get_the_author_meta('display_name', (int) $post->post_author),
            ),
        );
    }

    /**
     * Get the general media type from MIME type.
     *
     * Pure function - extracts media type category from MIME type.
     *
     * @param string $mime_type MIME type string.
     * @return string Media type (image, video, audio, document, other).
     */
    private function getMediaType(string $mime_type): string
    {
        $type_map = array(
            'image/' => 'image',
            'video/' => 'video',
            'audio/' => 'audio',
        );

        foreach ($type_map as $prefix => $type) {
            if (str_starts_with($mime_type, $prefix)) {
                return $type;
            }
        }

        if ($this->isDocumentType($mime_type)) {
            return 'document';
        }

        return 'other';
    }

    /**
     * Check if MIME type is a document.
     *
     * Pure function - determines if MIME type represents a document.
     *
     * @param string $mime_type MIME type string.
     * @return bool True if document type.
     */
    private function isDocumentType(string $mime_type): bool
    {
        return str_starts_with($mime_type, 'application/pdf')
            || str_starts_with($mime_type, 'application/msword');
    }
}
