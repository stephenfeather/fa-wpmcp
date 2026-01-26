<?php

/**
 * Permission checking logic (pure functions).
 *
 * @package FAWpmcp\Permissions
 */

declare(strict_types=1);

namespace FAWpmcp\Permissions;

use FAWpmcp\ValueObjects\PermissionSettings;
use FAWpmcp\ValueObjects\Result;

/**
 * Pure functions for permission checking.
 *
 * No side effects, no database access, no WordPress functions.
 * All logic is based solely on the PermissionSettings input.
 *
 * @package FAWpmcp\Permissions
 */
final class PermissionChecker
{
    /**
     * Check full permission hierarchy.
     *
     * Pure function: same inputs always produce same result.
     * Checks in order: Global → Category → Ability
     *
     * @param PermissionSettings $settings       Permission settings.
     * @param string             $ability_name   Ability name (e.g., 'fa-wpmcp/list-posts').
     * @param string             $operation_type Operation type ('read' or 'write').
     * @param string|null        $category       Optional category (e.g., 'posts-pages').
     * @return Result Success if allowed, failure with error code if blocked.
     */
    public static function check(
        PermissionSettings $settings,
        string $ability_name,
        string $operation_type,
        ?string $category = null
    ): Result {
        // 1. Check global settings.
        $global_result = self::checkGlobal($settings, $operation_type);
        if (! $global_result->is_success) {
            return $global_result;
        }

        // 2. Check category settings (if category provided).
        if (null !== $category) {
            $category_result = self::checkCategory($settings, $category, $operation_type);
            if (! $category_result->is_success) {
                return $category_result;
            }
        }

        // 3. Check ability-specific settings.
        return self::checkAbility($settings, $ability_name);
    }

    /**
     * Check global permission settings.
     *
     * @param PermissionSettings $settings       Permission settings.
     * @param string             $operation_type Operation type ('read' or 'write').
     * @return Result Success if allowed, failure if blocked.
     */
    public static function checkGlobal(PermissionSettings $settings, string $operation_type): Result
    {
        $enabled = match ($operation_type) {
            'read'  => $settings->global_read_enabled,
            'write' => $settings->global_write_enabled,
            default => false,
        };

        return $enabled
            ? Result::success(true)
            : Result::failure('ability_disabled', "Global {$operation_type} is disabled");
    }

    /**
     * Check category-level permissions.
     *
     * @param PermissionSettings $settings       Permission settings.
     * @param string             $category       Category name.
     * @param string             $operation_type Operation type ('read' or 'write').
     * @return Result Success if allowed, failure if blocked.
     */
    public static function checkCategory(
        PermissionSettings $settings,
        string $category,
        string $operation_type
    ): Result {
        $category_settings = $settings->category_settings[ $category ] ?? null;

        if (null === $category_settings) {
            // No category override, inherit from global (allowed).
            return Result::success(true);
        }

        $key     = "enable_{$operation_type}";
        $enabled = $category_settings[ $key ] ?? true;

        return $enabled
            ? Result::success(true)
            : Result::failure('ability_disabled', "Category {$category} {$operation_type} is disabled");
    }

    /**
     * Check ability-level permissions.
     *
     * @param PermissionSettings $settings     Permission settings.
     * @param string             $ability_name Ability name.
     * @return Result Success if allowed, failure if blocked.
     */
    public static function checkAbility(PermissionSettings $settings, string $ability_name): Result
    {
        $ability_settings = $settings->ability_settings[ $ability_name ] ?? null;

        if (null === $ability_settings) {
            // No ability override, inherit from higher levels (allowed).
            return Result::success(true);
        }

        $enabled = $ability_settings['enabled'] ?? true;

        return $enabled
            ? Result::success(true)
            : Result::failure('ability_disabled', "Ability {$ability_name} is disabled");
    }
}
