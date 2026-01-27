<?php

/**
 * Parser for .env file format.
 *
 * @package FAWpmcp\Abilities\Dotenv
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Dotenv;

use FAWpmcp\Exceptions\DotenvException;

/**
 * Parses and modifies .env files while preserving structure.
 *
 * Supports:
 * - KEY=value
 * - KEY="value with spaces"
 * - KEY='value'
 * - export KEY=value
 * - # comments
 * - Blank lines
 *
 * @package FAWpmcp\Abilities\Dotenv
 */
final class EnvFileParser
{
    /**
     * Export prefix used in .env files.
     *
     * @var string
     */
    private const EXPORT_PREFIX = 'export ';
    /**
     * Parse a .env file into key-value pairs.
     *
     * @param string $content File content.
     * @return array<string, string> Parsed variables (key => value).
     */
    public function parse(string $content): array
    {
        $variables = array();
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Skip empty lines and comments.
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            // Remove export prefix if present.
            if (str_starts_with($trimmed, self::EXPORT_PREFIX)) {
                $trimmed = substr($trimmed, strlen(self::EXPORT_PREFIX));
            }

            // Find the first = sign.
            $equals_pos = strpos($trimmed, '=');
            if ($equals_pos === false) {
                continue;
            }

            $key = trim(substr($trimmed, 0, $equals_pos));
            $value = substr($trimmed, $equals_pos + 1);

            // Parse the value (handle quotes).
            $value = $this->parseValue($value);

            if ($key !== '') {
                $variables[$key] = $value;
            }
        }

        return $variables;
    }

    /**
     * Parse a value, handling quotes.
     *
     * @param string $value Raw value from .env line.
     * @return string Parsed value.
     */
    private function parseValue(string $value): string
    {
        $value = trim($value);

        // Handle double quotes.
        if (str_starts_with($value, '"') && str_ends_with($value, '"') && strlen($value) >= 2) {
            return stripslashes(substr($value, 1, -1));
        }

        // Handle single quotes.
        if (str_starts_with($value, "'") && str_ends_with($value, "'") && strlen($value) >= 2) {
            return substr($value, 1, -1);
        }

        // Strip inline comments (only if not quoted).
        $comment_pos = strpos($value, ' #');
        if ($comment_pos !== false) {
            $value = rtrim(substr($value, 0, $comment_pos));
        }

        return $value;
    }

    /**
     * Set a variable in the .env content.
     *
     * @param string $content Original file content.
     * @param string $key     Variable name.
     * @param string $value   Variable value.
     * @param bool   $quote   Whether to quote the value.
     * @return array{content: string, action: string} Modified content and action taken.
     */
    public function setValue(string $content, string $key, string $value, bool $quote = false): array
    {
        $lines = explode("\n", $content);
        $found = false;
        $formatted_value = $quote ? '"' . addslashes($value) . '"' : $value;

        foreach ($lines as $index => $line) {
            $trimmed = trim($line);

            // Skip empty lines and comments.
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            // Check if this line defines the key we're looking for.
            $line_info = $this->parseLineKey($trimmed);
            if ($line_info !== null && $line_info['key'] === $key) {
                $prefix = $line_info['has_export'] ? self::EXPORT_PREFIX : '';
                $lines[$index] = $prefix . $key . '=' . $formatted_value;
                $found = true;
                break;
            }
        }

        if ($found) {
            return array(
                'content' => implode("\n", $lines),
                'action'  => 'updated',
            );
        }

        // Append new variable.
        $new_line = $key . '=' . $formatted_value;

        // Ensure there's a newline before appending if content doesn't end with one.
        if ($content !== '' && !str_ends_with($content, "\n")) {
            $content .= "\n";
        }

        return array(
            'content' => $content . $new_line . "\n",
            'action'  => 'created',
        );
    }

    /**
     * Delete a variable from the .env content.
     *
     * @param string $content Original file content.
     * @param string $key     Variable name to delete.
     * @return array{content: string, deleted: bool} Modified content and whether deletion occurred.
     */
    public function deleteValue(string $content, string $key): array
    {
        $lines = explode("\n", $content);
        $new_lines = array();
        $deleted = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Keep empty lines and comments.
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                $new_lines[] = $line;
                continue;
            }

            // Check if this line defines the key we're deleting.
            $line_info = $this->parseLineKey($trimmed);
            if ($line_info !== null && $line_info['key'] === $key) {
                $deleted = true;
                continue; // Skip this line (delete it).
            }

            $new_lines[] = $line;
        }

        return array(
            'content' => implode("\n", $new_lines),
            'deleted' => $deleted,
        );
    }

    /**
     * Check if a key exists in the .env content.
     *
     * @param string $content File content.
     * @param string $key     Variable name.
     * @return bool True if the key exists.
     */
    public function hasKey(string $content, string $key): bool
    {
        $variables = $this->parse($content);
        return array_key_exists($key, $variables);
    }

    /**
     * Get a single value from the .env content.
     *
     * @param string $content File content.
     * @param string $key     Variable name.
     * @return string|null Value or null if not found.
     */
    public function getValue(string $content, string $key): ?string
    {
        $variables = $this->parse($content);
        return $variables[$key] ?? null;
    }

    /**
     * Parse a line to extract the key and export status.
     *
     * @param string $line Trimmed line from .env file.
     * @return array{key: string, has_export: bool}|null Key info or null if not a variable line.
     */
    private function parseLineKey(string $line): ?array
    {
        $check_line = $line;
        $has_export = false;

        if (str_starts_with($check_line, self::EXPORT_PREFIX)) {
            $check_line = substr($check_line, strlen(self::EXPORT_PREFIX));
            $has_export = true;
        }

        $equals_pos = strpos($check_line, '=');
        if ($equals_pos === false) {
            return null;
        }

        return array(
            'key'        => trim(substr($check_line, 0, $equals_pos)),
            'has_export' => $has_export,
        );
    }
}
