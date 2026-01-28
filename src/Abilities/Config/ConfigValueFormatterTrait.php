<?php

/**
 * Trait for formatting configuration constant values.
 *
 * @package FAWpmcp\Abilities\Config
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Config;

/**
 * Provides value formatting for config abilities.
 *
 * @package FAWpmcp\Abilities\Config
 */
trait ConfigValueFormatterTrait
{
    /**
     * Format a constant value as a string for output.
     *
     * Converts PHP values to human-readable string representations.
     *
     * @param mixed $value The constant value.
     * @return string Formatted value.
     */
    private function formatValue(mixed $value): string
    {
        return match (true) {
            is_bool($value) => $value ? 'true' : 'false',
            is_null($value) => 'null',
            is_array($value) => json_encode($value, JSON_THROW_ON_ERROR) ?: '[]',
            default => (string) $value,
        };
    }
}
