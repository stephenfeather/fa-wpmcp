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
     * The .env filename with leading slash for path concatenation.
     *
     * @var string
     */
    private const ENV_FILENAME = '/.env';
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

        // 2. Get and validate ABSPATH.
        $abspath = defined('ABSPATH') ? ABSPATH : '';
        if ($abspath === '') {
            throw new DotenvException('ABSPATH is not defined.');
        }

        // Normalize path separators and remove trailing slash.
        $abspath = rtrim(str_replace('\\', '/', $abspath), '/');

        // 3. Check candidate paths in priority order.
        $candidates = $this->buildCandidatePaths($abspath);
        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        throw new DotenvException(
            'Could not locate .env file. Checked: ' . implode(', ', array_filter(
                array_merge([$filtered_path ?: null], $candidates)
            ))
        );
    }

    /**
     * Build list of candidate .env file paths in priority order.
     *
     * @param string $abspath Normalized ABSPATH.
     * @return array<int, string> Candidate paths.
     */
    private function buildCandidatePaths(string $abspath): array
    {
        $candidates = array();

        // Bedrock: ABSPATH is /path/to/project/web/wp/
        // .env is at /path/to/project/.env (2 levels up).
        if (str_ends_with($abspath, '/web/wp')) {
            $candidates[] = dirname($abspath, 2) . self::ENV_FILENAME;
        }

        // Standard WordPress fallback (one level up from ABSPATH).
        $candidates[] = dirname($abspath) . self::ENV_FILENAME;

        // WordPress root (some setups have .env in WordPress root).
        $candidates[] = $abspath . self::ENV_FILENAME;

        return $candidates;
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
