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

            // Remove 'export ' prefix if present.
            if (str_starts_with($trimmed, 'export ')) {
                $trimmed = substr($trimmed, 7);
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

            // Check for export prefix.
            $check_line = $trimmed;
            $has_export = false;
            if (str_starts_with($check_line, 'export ')) {
                $check_line = substr($check_line, 7);
                $has_export = true;
            }

            // Check if this line defines the key.
            $equals_pos = strpos($check_line, '=');
            if ($equals_pos !== false) {
                $line_key = trim(substr($check_line, 0, $equals_pos));
                if ($line_key === $key) {
                    // Replace this line.
                    $prefix = $has_export ? 'export ' : '';
                    $lines[$index] = $prefix . $key . '=' . $formatted_value;
                    $found = true;
                    break;
                }
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

            // Check for export prefix.
            $check_line = $trimmed;
            if (str_starts_with($check_line, 'export ')) {
                $check_line = substr($check_line, 7);
            }

            // Check if this line defines the key.
            $equals_pos = strpos($check_line, '=');
            if ($equals_pos !== false) {
                $line_key = trim(substr($check_line, 0, $equals_pos));
                if ($line_key === $key) {
                    $deleted = true;
                    continue; // Skip this line (delete it).
                }
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
}
