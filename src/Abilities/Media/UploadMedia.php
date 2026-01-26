<?php
/**
 * UploadMedia ability - uploads a new media file.
 *
 * @package FAWpmcp\Abilities\Media
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Media;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\MediaUploadException;

/**
 * Ability to upload a new media file to WordPress.
 *
 * Features:
 * - Base64-encoded file upload
 * - URL-based file import
 * - Automatic MIME type detection
 * - Optional metadata (title, caption, alt text)
 * - Generates image sizes automatically
 *
 * @package FAWpmcp\Abilities\Media
 */
final class UploadMedia extends AbstractAbility {

	/**
	 * Maximum file size in bytes (10MB).
	 *
	 * @var int
	 */
	private const MAX_FILE_SIZE = 10485760;

	/**
	 * Get the unique ability name.
	 *
	 * @return string Ability name.
	 */
	public function getName(): string {
		return 'fa-wpmcp/upload-media';
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
		return 'Upload Media';
	}

	/**
	 * Get the ability description.
	 *
	 * @return string Description.
	 */
	public function getDescription(): string {
		return 'Upload a new file to the WordPress media library. Accepts base64-encoded file data or a URL to import from. Automatically generates image sizes and metadata. Supports images, videos, audio, and documents up to 10MB.';
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
				'filename'    => array(
					'type'        => 'string',
					'description' => 'The filename for the uploaded file (e.g., image.jpg).',
				),
				'file_data'   => array(
					'type'        => 'string',
					'description' => 'Base64-encoded file data. Required if url is not provided.',
				),
				'url'         => array(
					'type'        => 'string',
					'description' => 'URL to import the file from. Required if file_data is not provided.',
					'format'      => 'uri',
				),
				'title'       => array(
					'type'        => 'string',
					'description' => 'Optional title for the media item.',
				),
				'caption'     => array(
					'type'        => 'string',
					'description' => 'Optional caption for the media item.',
				),
				'description' => array(
					'type'        => 'string',
					'description' => 'Optional description for the media item.',
				),
				'alt_text'    => array(
					'type'        => 'string',
					'description' => 'Optional alt text for images.',
				),
			),
			'required'   => array( 'filename' ),
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
				'media_id'  => array(
					'type'        => 'integer',
					'description' => 'The ID of the uploaded media item.',
				),
				'url'       => array(
					'type'        => 'string',
					'description' => 'The URL of the uploaded media.',
				),
				'mime_type' => array(
					'type'        => 'string',
					'description' => 'The MIME type of the uploaded file.',
				),
				'type'      => array(
					'type'        => 'string',
					'description' => 'The media type (image, video, audio, document, other).',
				),
				'filesize'  => array(
					'type'        => 'integer',
					'description' => 'The file size in bytes.',
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
	 * Get the operation type.
	 *
	 * @return string 'write' for upload operations.
	 */
	public function getOperationType(): string {
		return 'write';
	}

	/**
	 * Execute the ability.
	 *
	 * @param array<string, mixed> $input Validated input data.
	 * @return array<string, mixed> Uploaded media data.
	 * @throws MediaUploadException If upload fails.
	 * @throws \Exception If an unexpected error occurs.
	 */
	public function doExecute( array $input ): array {
		// Validate input.
		if ( empty( $input['file_data'] ) && empty( $input['url'] ) ) {
			throw new MediaUploadException( 'Either file_data or url must be provided' );
		}

		// Get file data.
		if ( ! empty( $input['file_data'] ) ) {
			$file_data = $this->decodeBase64( $input['file_data'] );
		} else {
			$file_data = $this->fetchFromUrl( $input['url'] );
		}

		// Check file size.
		if ( strlen( $file_data ) > self::MAX_FILE_SIZE ) {
			throw new MediaUploadException( 'File size exceeds 10MB limit' );
		}

		// Create temporary file.
		$temp_file = $this->createTempFile( $file_data, $input['filename'] );

		try {
			// Upload to media library.
			$attachment_id = $this->uploadToMediaLibrary( $temp_file, $input['filename'], $input );

			// Clean up temp file.
			wp_delete_file( $temp_file );

			// Format response.
			return $this->formatResponse( $attachment_id );
		} catch ( \Exception $e ) {
			// Clean up temp file on error.
			wp_delete_file( $temp_file );
			throw $e;
		}
	}

	/**
	 * Decode base64-encoded file data.
	 *
	 * Pure function - decodes base64 string.
	 *
	 * @param string $base64_data Base64-encoded data.
	 * @return string Decoded binary data.
	 * @throws MediaUploadException If decoding fails.
	 */
	private function decodeBase64( string $base64_data ): string {
		// Remove data URI scheme if present.
		if ( preg_match( '/^data:([^;]+);base64,(.+)$/', $base64_data, $matches ) ) {
			$base64_data = $matches[2];
		}

		$decoded = base64_decode( $base64_data, true );

		if ( false === $decoded ) {
			throw new MediaUploadException( 'Invalid base64 data' );
		}

		return $decoded;
	}

	/**
	 * Fetch file data from URL.
	 *
	 * Side effect function - makes HTTP request.
	 * Includes SSRF protection to prevent requests to internal networks.
	 *
	 * @param string $url URL to fetch from.
	 * @return string File data.
	 * @throws MediaUploadException If fetch fails or URL is invalid.
	 */
	private function fetchFromUrl( string $url ): string {
		// Validate URL for SSRF protection.
		$this->validateUrlForSsrf( $url );

		$response = wp_remote_get( $url, array( 'timeout' => 30 ) );

		if ( is_wp_error( $response ) ) {
			throw new MediaUploadException(
                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Internal exception message.
				'Failed to fetch URL: ' . $response->get_error_message()
			);
		}

		$body = wp_remote_retrieve_body( $response );

		if ( empty( $body ) ) {
			throw new MediaUploadException( 'Empty response from URL' );
		}

		return $body;
	}

	/**
	 * Validate URL to prevent SSRF attacks.
	 *
	 * Checks that the URL uses a safe scheme and does not point to
	 * internal/private network addresses.
	 *
	 * @param string $url URL to validate.
	 * @return void
	 * @throws MediaUploadException If URL fails validation.
	 */
	private function validateUrlForSsrf( string $url ): void {
		// Parse the URL using native PHP function.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Using native for testability; wp_parse_url is just a wrapper.
		$parsed = parse_url( $url );

		if ( false === $parsed || ! isset( $parsed['scheme'], $parsed['host'] ) ) {
			throw new MediaUploadException( 'Invalid URL format' );
		}

		// Only allow http and https schemes.
		$scheme = strtolower( $parsed['scheme'] );
		if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
			throw new MediaUploadException( 'URL scheme must be http or https' );
		}

		$host = strtolower( $parsed['host'] );

		// Block localhost variations.
		$blocked_hosts = array(
			'localhost',
			'127.0.0.1',
			'::1',
			'0.0.0.0',
			'[::1]',
		);

		if ( in_array( $host, $blocked_hosts, true ) ) {
			throw new MediaUploadException( 'URLs pointing to localhost are not allowed' );
		}

		// Resolve hostname to IP address for further validation.
		$ip = gethostbyname( $host );

		// If gethostbyname returns the hostname, it couldn't resolve.
		if ( $ip === $host && ! filter_var( $host, FILTER_VALIDATE_IP ) ) {
			throw new MediaUploadException( 'Could not resolve hostname' );
		}

		// Validate the resolved IP is not in a private/reserved range.
		if ( ! $this->isPublicIp( $ip ) ) {
			throw new MediaUploadException( 'URLs pointing to private or reserved IP ranges are not allowed' );
		}
	}

	/**
	 * Check if an IP address is public (not private or reserved).
	 *
	 * Pure function - validates IP against private/reserved ranges.
	 *
	 * @param string $ip IP address to check.
	 * @return bool True if IP is public, false if private/reserved.
	 */
	private function isPublicIp( string $ip ): bool {
		// Use PHP's filter_var with appropriate flags.
		// This checks against RFC 1918 private ranges, loopback, link-local, etc.
		$flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;

		return false !== filter_var( $ip, FILTER_VALIDATE_IP, $flags );
	}

	/**
	 * Create a temporary file from binary data.
	 *
	 * Side effect function - writes to filesystem.
	 *
	 * @param string $file_data Binary file data.
	 * @param string $filename  Original filename.
	 * @return string Path to temporary file.
	 * @throws MediaUploadException If file creation fails.
	 */
	private function createTempFile( string $file_data, string $filename ): string {
		$upload_dir = wp_upload_dir();
		$temp_file  = $upload_dir['path'] . '/' . wp_unique_filename( $upload_dir['path'], $filename );

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Direct filesystem access is appropriate here.
		$result = file_put_contents( $temp_file, $file_data );

		if ( false === $result ) {
			throw new MediaUploadException( 'Failed to create temporary file' );
		}

		return $temp_file;
	}

	/**
	 * Upload file to WordPress media library.
	 *
	 * Side effect function - creates attachment post and generates metadata.
	 *
	 * @param string               $file_path File path.
	 * @param string               $filename  Original filename.
	 * @param array<string, mixed> $input     Input parameters.
	 * @return int Attachment ID.
	 * @throws MediaUploadException If upload fails.
	 */
	private function uploadToMediaLibrary( string $file_path, string $filename, array $input ): int {
		// Get file type.
		$filetype = wp_check_filetype( $filename );

		if ( empty( $filetype['type'] ) ) {
			throw new MediaUploadException( 'Invalid or unsupported file type' );
		}

		// Prepare attachment data.
		$attachment = array(
			'post_mime_type' => $filetype['type'],
			'post_title'     => isset( $input['title'] ) ? sanitize_text_field( $input['title'] ) : sanitize_file_name( pathinfo( $filename, PATHINFO_FILENAME ) ),
			'post_content'   => isset( $input['description'] ) ? sanitize_textarea_field( $input['description'] ) : '',
			'post_excerpt'   => isset( $input['caption'] ) ? sanitize_textarea_field( $input['caption'] ) : '',
			'post_status'    => 'inherit',
		);

		// Insert attachment.
		$attachment_id = wp_insert_attachment( $attachment, $file_path );

		if ( is_wp_error( $attachment_id ) ) {
			throw new MediaUploadException(
                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Internal exception message.
				'Failed to create attachment: ' . $attachment_id->get_error_message()
			);
		}

		if ( 0 === $attachment_id ) {
			throw new MediaUploadException( 'Failed to create attachment' );
		}

		// Generate metadata.
		$this->loadImageFunctions();
		$metadata = wp_generate_attachment_metadata( $attachment_id, $file_path );
		wp_update_attachment_metadata( $attachment_id, $metadata );

		// Set alt text if provided.
		if ( isset( $input['alt_text'] ) ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $input['alt_text'] ) );
		}

		return $attachment_id;
	}

	/**
	 * Format the response after upload.
	 *
	 * @param int $attachment_id Uploaded attachment ID.
	 * @return array<string, mixed> Response data.
	 */
	private function formatResponse( int $attachment_id ): array {
		$file      = get_attached_file( $attachment_id );
		$filesize  = $file && file_exists( $file ) ? filesize( $file ) : 0;
		$post      = get_post( $attachment_id );
		$mime_type = $post ? $post->post_mime_type : '';

		return array(
			'media_id'  => $attachment_id,
			'url'       => wp_get_attachment_url( $attachment_id ),
			'mime_type' => $mime_type,
			'type'      => $this->getMediaType( $mime_type ),
			'filesize'  => $filesize,
		);
	}

	/**
	 * Load WordPress image functions.
	 *
	 * Side effect function - loads WordPress admin functions.
	 *
	 * @return void
	 */
	private function loadImageFunctions(): void {
		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_require_once -- WordPress image functions are loaded conditionally.
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}
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
		$type_map = array(
			'image/' => 'image',
			'video/' => 'video',
			'audio/' => 'audio',
		);

		foreach ( $type_map as $prefix => $type ) {
			if ( str_starts_with( $mime_type, $prefix ) ) {
				return $type;
			}
		}

		if ( $this->isDocumentType( $mime_type ) ) {
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
	private function isDocumentType( string $mime_type ): bool {
		return str_starts_with( $mime_type, 'application/pdf' )
			|| str_starts_with( $mime_type, 'application/msword' );
	}
}
