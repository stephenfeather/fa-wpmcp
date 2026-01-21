<?php
/**
 * GetMedia ability - retrieves a single media item by ID.
 *
 * @package FAWpmcp\Abilities\Media
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Media;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\PostNotFoundException;
use FAWpmcp\Exceptions\PostTypeMismatchException;

/**
 * Ability to retrieve a single WordPress media item by ID.
 *
 * Returns complete media data including:
 * - Basic fields (title, caption, description)
 * - File information (URL, filename, MIME type, size)
 * - Image metadata (dimensions, sizes)
 * - Alt text and author information
 *
 * @package FAWpmcp\Abilities\Media
 */
final class GetMedia extends AbstractAbility {
    /**
     * Get the unique ability name.
     *
     * @return string Ability name.
     */
    public function getName(): string {
        return 'fa-wpmcp/get-media';
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
        return 'Get Media';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string {
        return 'Retrieve a single WordPress media library item by ID with full details including file information, metadata, dimensions, available sizes, and author information.';
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
                    'description' => 'The ID of the media item to retrieve.',
                    'minimum'     => 1,
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
                'media' => array(
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
                        'sizes'       => array(
                            'type'                 => 'object',
                            'description'          => 'Available image sizes with URLs and dimensions.',
                            'additionalProperties' => array(
                                'type'       => 'object',
                                'properties' => array(
                                    'url'    => array( 'type' => 'string' ),
                                    'width'  => array( 'type' => 'integer' ),
                                    'height' => array( 'type' => 'integer' ),
                                ),
                            ),
                        ),
                        'meta'        => array( 'type' => 'object' ),
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
        return 'upload_files';
    }

    /**
     * Execute the ability.
     *
     * @param array<string, mixed> $input Validated input data.
     * @return array<string, mixed> Media data.
     * @throws RuntimeException If media not found.
     */
    public function doExecute( array $input ): array {
        $media_id = (int) $input['media_id'];

        // Side effect: fetch attachment from database.
        $post = get_post( $media_id );

        if ( null === $post ) {
            throw new PostNotFoundException( 'Media item not found' );
        }

        // Validate that it's an attachment.
        if ( 'attachment' !== $post->post_type ) {
            throw new PostTypeMismatchException( 'Post is not a media attachment' );
        }

        // Pure transformation: format media data.
        return array(
            'media' => $this->formatMedia( $post ),
        );
    }

    /**
     * Format a WP_Post attachment object into output array.
     *
     * Pure function - transforms attachment data without side effects (except for WP API calls).
     *
     * @param \WP_Post $post The attachment post object.
     * @return array<string, mixed> Formatted media data.
     */
    private function formatMedia( \WP_Post $post ): array {
        $attachment_id = $post->ID;
        $metadata      = wp_get_attachment_metadata( $attachment_id );

        // Get file size.
        $file     = get_attached_file( $attachment_id );
        $filesize = $file && file_exists( $file ) ? filesize( $file ) : 0;

        // Get dimensions for images.
        $width  = isset( $metadata['width'] ) ? (int) $metadata['width'] : 0;
        $height = isset( $metadata['height'] ) ? (int) $metadata['height'] : 0;

        return array(
            'id'          => $attachment_id,
            'title'       => $post->post_title,
            'filename'    => basename( get_attached_file( $attachment_id ) ),
            'url'         => wp_get_attachment_url( $attachment_id ),
            'mime_type'   => $post->post_mime_type,
            'type'        => $this->getMediaType( $post->post_mime_type ),
            'date'        => $post->post_date,
            'modified'    => $post->post_modified,
            'filesize'    => $filesize,
            'width'       => $width,
            'height'      => $height,
            'alt_text'    => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
            'caption'     => $post->post_excerpt,
            'description' => $post->post_content,
            'author'      => $this->formatAuthor( (int) $post->post_author ),
            'sizes'       => $this->getImageSizes( $attachment_id, $metadata ),
            'meta'        => $this->getAttachmentMeta( $attachment_id ),
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
    private function getMediaType( string $mime_type ): string {
        if ( str_starts_with( $mime_type, 'image/' ) ) {
            return 'image';
        }
        if ( str_starts_with( $mime_type, 'video/' ) ) {
            return 'video';
        }
        if ( str_starts_with( $mime_type, 'audio/' ) ) {
            return 'audio';
        }
        if ( str_starts_with( $mime_type, 'application/pdf' ) || str_starts_with( $mime_type, 'application/msword' ) ) {
            return 'document';
        }
        return 'other';
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
     * Get available image sizes with URLs and dimensions.
     *
     * Pure function - extracts size information from metadata.
     *
     * @param int                  $attachment_id Attachment ID.
     * @param array<string, mixed> $metadata      Attachment metadata.
     * @return array<string, array<string, mixed>> Image sizes array.
     */
    private function getImageSizes( int $attachment_id, array $metadata ): array {
        $sizes = array();

        // Add full size.
        $full_url = wp_get_attachment_url( $attachment_id );
        if ( $full_url ) {
            $sizes['full'] = array(
                'url'    => $full_url,
                'width'  => isset( $metadata['width'] ) ? (int) $metadata['width'] : 0,
                'height' => isset( $metadata['height'] ) ? (int) $metadata['height'] : 0,
            );
        }

        // Add intermediate sizes.
        if ( isset( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
            foreach ( $metadata['sizes'] as $size_name => $size_data ) {
                $size_url = wp_get_attachment_image_src( $attachment_id, $size_name );
                if ( $size_url && is_array( $size_url ) ) {
                    $sizes[ $size_name ] = array(
                        'url'    => $size_url[0],
                        'width'  => isset( $size_data['width'] ) ? (int) $size_data['width'] : 0,
                        'height' => isset( $size_data['height'] ) ? (int) $size_data['height'] : 0,
                    );
                }
            }
        }

        return $sizes;
    }

    /**
     * Get attachment meta, filtering out internal keys.
     *
     * Pure function - filters and formats meta data.
     *
     * @param int $attachment_id Attachment ID.
     * @return array<string, mixed> Filtered meta data.
     */
    private function getAttachmentMeta( int $attachment_id ): array {
        $meta = get_post_meta( $attachment_id, '', true );

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
