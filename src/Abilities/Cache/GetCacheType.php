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
final class GetCacheType extends AbstractAbility
{
    /**
     * Cache type patterns to check (order matters: memcached before memcache).
     *
     * @var array<string>
     */
    private const CACHE_TYPES = [ 'redis', 'memcached', 'memcache', 'apcu', 'xcache', 'wincache' ];
    /**
     * Get the unique ability name.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/get-cache-type';
    }

    /**
     * Get the ability category.
     *
     * @return string Category name.
     */
    public function getCategory(): string
    {
        return 'cache';
    }

    /**
     * Get the human-readable label.
     *
     * @return string Ability label.
     */
    public function getLabel(): string
    {
        return 'Get Cache Type';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Identify the WordPress object cache implementation type (e.g., Redis, Memcached, APCu, or default).';
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
            'properties' => new \stdClass(),
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
    public function getAnnotations(): array
    {
        $annotations               = parent::getAnnotations();
        $annotations['mcp.public'] = true;
        return $annotations;
    }

    /**
     * Get the required WordPress capability.
     *
     * @return string WordPress capability name.
     */
    public function getRequiredCapability(): string
    {
        return 'manage_options';
    }

    /**
     * Get the operation type.
     *
     * @return string Operation type ('read' or 'write').
     */
    public function getOperationType(): string
    {
        return 'read';
    }

    /**
     * Execute the ability.
     *
     * @param array<string, mixed> $input Validated input data.
     * @return array<string, mixed> Cache type information.
     */
    public function doExecute(array $input): array
    {
        global $wp_object_cache;

        $persistent   = wp_using_ext_object_cache();
        $drop_in_path = WP_CONTENT_DIR . '/object-cache.php';
        $drop_in      = file_exists($drop_in_path);

        // Detect cache type.
        $type = $this->detectCacheType($wp_object_cache, $drop_in_path);

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
    private function detectCacheType($cache, string $drop_in_path): string
    {
        if (! wp_using_ext_object_cache()) {
            return 'default';
        }

        $type = $this->detectFromClassName($cache);
        if ('unknown' !== $type) {
            return $type;
        }

        return $this->detectFromDropInFile($drop_in_path);
    }

    /**
     * Detect cache type from the cache object's class name.
     *
     * @param mixed $cache The global cache object.
     * @return string The detected cache type or 'unknown'.
     */
    private function detectFromClassName($cache): string
    {
        if (! is_object($cache)) {
            return 'unknown';
        }

        $class_name = strtolower(get_class($cache));

        foreach (self::CACHE_TYPES as $type) {
            if (str_contains($class_name, $type)) {
                return $type;
            }
        }

        return 'unknown';
    }

    /**
     * Detect cache type from the drop-in file content.
     *
     * Uses early returns for error conditions - intentional guard clause pattern.
     *
     * @param string $drop_in_path Path to the drop-in file.
     * @return string The detected cache type or 'unknown'.
     */
    private function detectFromDropInFile(string $drop_in_path): string // NOSONAR S1142 - guard clauses are intentional
    {
        if (! file_exists($drop_in_path) || ! is_readable($drop_in_path)) {
            return 'unknown';
        }

        $content = file_get_contents($drop_in_path, false, null, 0, 2048);
        if (false === $content) {
            return 'unknown';
        }

        $content_lower = strtolower($content);

        // Only check types that commonly appear in drop-in files.
        $file_types = [ 'redis', 'memcached', 'memcache', 'apcu' ];
        foreach ($file_types as $type) {
            if (str_contains($content_lower, $type)) {
                return $type;
            }
        }

        return 'unknown';
    }
}
