<?php

/**
 * Security policy for environment variable access.
 *
 * @package FAWpmcp\Abilities\Dotenv
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Dotenv;

/**
 * Defines access policies for environment variables.
 *
 * Determines which variables are sensitive (should be redacted in output)
 * and which are protected (cannot be modified via MCP).
 *
 * @package FAWpmcp\Abilities\Dotenv
 */
final class EnvAccessPolicy
{
    /**
     * Patterns that indicate a sensitive variable (value should be redacted).
     *
     * @var array<string>
     */
    private const SENSITIVE_PATTERNS = array(
        'PASSWORD',
        'SECRET',
        'KEY',
        'SALT',
        'TOKEN',
        'CREDENTIAL',
        'AUTH',
    );

    /**
     * Variables that are protected from modification.
     *
     * @var array<string>
     */
    private const PROTECTED_VARIABLES = array(
        'DB_NAME',
        'DB_USER',
        'DB_PASSWORD',
        'DB_HOST',
        'WP_ENV',
        'WP_HOME',
        'WP_SITEURL',
    );

    /**
     * Suffixes that indicate a protected variable.
     *
     * @var array<string>
     */
    private const PROTECTED_SUFFIXES = array(
        '_KEY',
        '_SALT',
    );

    /**
     * Check if a variable name indicates sensitive data.
     *
     * @param string $key Variable name.
     * @return bool True if the variable is sensitive.
     */
    public function isSensitive(string $key): bool
    {
        $upper_key = strtoupper($key);

        foreach (self::SENSITIVE_PATTERNS as $pattern) {
            if (str_contains($upper_key, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a variable is protected from modification.
     *
     * @param string $key Variable name.
     * @return bool True if the variable is protected.
     */
    public function isProtected(string $key): bool
    {
        $upper_key = strtoupper($key);

        // Check exact match.
        if (in_array($upper_key, self::PROTECTED_VARIABLES, true)) {
            return true;
        }

        // Check suffixes.
        foreach (self::PROTECTED_SUFFIXES as $suffix) {
            if (str_ends_with($upper_key, $suffix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all sensitive patterns.
     *
     * @return array<string>
     */
    public function getSensitivePatterns(): array
    {
        return self::SENSITIVE_PATTERNS;
    }

    /**
     * Get all protected variables.
     *
     * @return array<string>
     */
    public function getProtectedVariables(): array
    {
        return self::PROTECTED_VARIABLES;
    }

    /**
     * Get all protected suffixes.
     *
     * @return array<string>
     */
    public function getProtectedSuffixes(): array
    {
        return self::PROTECTED_SUFFIXES;
    }
}
