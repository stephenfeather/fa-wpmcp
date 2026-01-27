<?php

/**
 * Locates the .env file in Bedrock WordPress installations.
 *
 * @package FAWpmcp\Abilities\Dotenv
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Dotenv;

use FAWpmcp\Exceptions\DotenvException;

/**
 * Locates the .env file path for Bedrock WordPress.
 *
 * Detection order:
 * 1. Filter override via 'fa_wpmcp_dotenv_file_path'
 * 2. Bedrock detection (ABSPATH ends in /web/wp/)
 * 3. Standard fallback (one level up from ABSPATH)
 *
 * @package FAWpmcp\Abilities\Dotenv
 */
class EnvFileLocator
{
    /**
     * Locate the .env file path.
     *
     * @return string Full path to the .env file.
     * @throws DotenvException If the file cannot be found.
     */
    public function locate(): string
    {
        // 1. Check for filter override.
        $filtered_path = apply_filters('fa_wpmcp_dotenv_file_path', '');
        if (is_string($filtered_path) && $filtered_path !== '' && file_exists($filtered_path)) {
            return $filtered_path;
        }

        // 2. Detect Bedrock structure.
        $abspath = defined('ABSPATH') ? ABSPATH : '';
        if ($abspath === '') {
            throw new DotenvException('ABSPATH is not defined.');
        }

        // Normalize path separators and remove trailing slash.
        $abspath = rtrim(str_replace('\\', '/', $abspath), '/');

        // Bedrock: ABSPATH is /path/to/project/web/wp/
        // .env is at /path/to/project/.env (2 levels up).
        if (str_ends_with($abspath, '/web/wp')) {
            $bedrock_root = dirname($abspath, 2);
            $env_path = $bedrock_root . '/.env';
            if (file_exists($env_path)) {
                return $env_path;
            }
        }

        // 3. Standard WordPress fallback (one level up from ABSPATH).
        $standard_path = dirname($abspath) . '/.env';
        if (file_exists($standard_path)) {
            return $standard_path;
        }

        // 4. Try ABSPATH directly (some setups have .env in WordPress root).
        $wp_root_path = $abspath . '/.env';
        if (file_exists($wp_root_path)) {
            return $wp_root_path;
        }

        throw new DotenvException(
            'Could not locate .env file. Checked: ' . implode(', ', array_filter([
                $filtered_path ?: null,
                str_ends_with($abspath, '/web/wp') ? dirname($abspath, 2) . '/.env' : null,
                $standard_path,
                $wp_root_path,
            ]))
        );
    }

    /**
     * Check if a .env file exists.
     *
     * @return bool True if .env file can be located.
     */
    public function exists(): bool
    {
        try {
            $this->locate();
            return true;
        } catch (DotenvException $exception) {
            return false;
        }
    }
}
