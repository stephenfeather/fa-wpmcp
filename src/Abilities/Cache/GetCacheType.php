<?php
/**
 * GetCacheType ability - identifies the WordPress object cache implementation.
 *
 * @package FAWpmcp\Abilities\Cache
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Cache;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Ability to identify the WordPress object cache implementation type.
 *
 * Detects whether an external object cache drop-in is being used and
 * attempts to identify the specific implementation (Redis, Memcached, etc.).
 *
 * @package FAWpmcp\Abilities\Cache
 */
final class GetCacheType extends AbstractAbility {
	/**
	 * Get the unique ability name.
	 *
	 * @return string Ability name.
	 */
	public function getName(): string {
		return 'fa-wpmcp/get-cache-type';
	}

	/**
	 * Get the ability category.
	 *
	 * @return string Category name.
	 */
	public function getCategory(): string {
		return 'cache';
	}

	/**
	 * Get the human-readable label.
	 *
	 * @return string Ability label.
	 */
	public function getLabel(): string {
		return 'Get Cache Type';
	}

	/**
	 * Get the ability description.
	 *
	 * @return string Description.
	 */
	public function getDescription(): string {
		return 'Identify the WordPress object cache implementation type (e.g., Redis, Memcached, APCu, or default).';
	}

	/**
	 * Get the input schema.
	 *
	 * @return array<string, mixed> JSON Schema array.
	 */
	public function getInputSchema(): array {
		return array(
			'type'       => 'object',
			'properties' => new \stdClass(),
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
				'type'         => array(
					'type'        => 'string',
					'description' => 'The cache implementation type.',
					'enum'        => array( 'default', 'redis', 'memcached', 'memcache', 'apcu', 'xcache', 'wincache', 'unknown' ),
				),
				'persistent'   => array(
					'type'        => 'boolean',
					'description' => 'Whether the cache is persistent.',
				),
				'drop_in'      => array(
					'type'        => 'boolean',
					'description' => 'Whether an object-cache.php drop-in is active.',
				),
				'drop_in_path' => array(
					'type'        => 'string',
					'description' => 'Path to the object-cache.php drop-in if present.',
				),
			),
		);
	}

	/**
	 * Get ability annotations.
	 *
	 * @return array<string, mixed> Annotations array.
	 */
	public function getAnnotations(): array {
		$annotations               = parent::getAnnotations();
		$annotations['mcp.public'] = true;
		return $annotations;
	}

	/**
	 * Get the required WordPress capability.
	 *
	 * @return string WordPress capability name.
	 */
	public function getRequiredCapability(): string {
		return 'manage_options';
	}

	/**
	 * Get the operation type.
	 *
	 * @return string Operation type ('read' or 'write').
	 */
	public function getOperationType(): string {
		return 'read';
	}

	/**
	 * Execute the ability.
	 *
	 * @param array<string, mixed> $input Validated input data.
	 * @return array<string, mixed> Cache type information.
	 */
	public function doExecute( array $input ): array {
		global $wp_object_cache;

		$persistent   = wp_using_ext_object_cache();
		$drop_in_path = WP_CONTENT_DIR . '/object-cache.php';
		$drop_in      = file_exists( $drop_in_path );

		// Detect cache type.
		$type = $this->detectCacheType( $wp_object_cache, $drop_in_path );

		return array(
			'type'         => $type,
			'persistent'   => $persistent,
			'drop_in'      => $drop_in,
			'drop_in_path' => $drop_in ? $drop_in_path : '',
		);
	}

	/**
	 * Detect the cache implementation type.
	 *
	 * @param mixed  $cache         The global cache object.
	 * @param string $drop_in_path  Path to the drop-in file.
	 * @return string The detected cache type.
	 */
	private function detectCacheType( $cache, string $drop_in_path ): string {
		// If no external cache, it's the default.
		if ( ! wp_using_ext_object_cache() ) {
			return 'default';
		}

		// Check by class name.
		if ( is_object( $cache ) ) {
			$class_name = strtolower( get_class( $cache ) );

			if ( false !== strpos( $class_name, 'redis' ) ) {
				return 'redis';
			}
			if ( false !== strpos( $class_name, 'memcached' ) ) {
				return 'memcached';
			}
			if ( false !== strpos( $class_name, 'memcache' ) ) {
				return 'memcache';
			}
			if ( false !== strpos( $class_name, 'apcu' ) ) {
				return 'apcu';
			}
			if ( false !== strpos( $class_name, 'xcache' ) ) {
				return 'xcache';
			}
			if ( false !== strpos( $class_name, 'wincache' ) ) {
				return 'wincache';
			}
		}

		// Check drop-in file content for hints.
		if ( file_exists( $drop_in_path ) && is_readable( $drop_in_path ) ) {
			// Read first 2KB to find identifiers.
			$content = file_get_contents( $drop_in_path, false, null, 0, 2048 );
			if ( false !== $content ) {
				$content_lower = strtolower( $content );

				if ( false !== strpos( $content_lower, 'redis' ) ) {
					return 'redis';
				}
				if ( false !== strpos( $content_lower, 'memcached' ) ) {
					return 'memcached';
				}
				if ( false !== strpos( $content_lower, 'memcache' ) ) {
					return 'memcache';
				}
				if ( false !== strpos( $content_lower, 'apcu' ) ) {
					return 'apcu';
				}
			}
		}

		return 'unknown';
	}
}
