<?php
/**
 * Central ability registration and management.
 *
 * @package FAWpmcp\Abilities
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities;

use InvalidArgumentException;

/**
 * Registry for ability registration and retrieval.
 *
 * Provides central management for:
 * - Registering abilities
 * - Retrieving abilities by name or category
 * - Preventing duplicate registrations
 * - Listing all abilities
 *
 * @package FAWpmcp\Abilities
 */
final class AbilityRegistry {
    /**
     * Registered abilities indexed by name.
     *
     * @var array<string, AbstractAbility>
     */
    private array $abilities = [];

    /**
     * Register an ability.
     *
     * @param AbstractAbility $ability Ability to register.
     * @return void
     * @throws InvalidArgumentException If ability is already registered.
     */
    public function register( AbstractAbility $ability ): void {
        $name = $ability->getName();

        if ( $this->has( $name ) ) {
            throw new InvalidArgumentException(
                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Internal exception message.
                sprintf( 'Ability "%s" is already registered.', $name )
            );
        }

        $this->abilities[ $name ] = $ability;
    }

    /**
     * Get an ability by name.
     *
     * @param string $name Ability name.
     * @return AbstractAbility|null Ability or null if not found.
     */
    public function get( string $name ): ?AbstractAbility {
        return $this->abilities[ $name ] ?? null;
    }

    /**
     * Check if an ability is registered.
     *
     * @param string $name Ability name.
     * @return bool True if registered.
     */
    public function has( string $name ): bool {
        return isset( $this->abilities[ $name ] );
    }

    /**
     * Get all registered abilities.
     *
     * @return array<string, AbstractAbility> Abilities indexed by name.
     */
    public function all(): array {
        return $this->abilities;
    }

    /**
     * Get abilities by category.
     *
     * @param string $category Category name.
     * @return array<string, AbstractAbility> Abilities in category.
     */
    public function byCategory( string $category ): array {
        return array_filter(
            $this->abilities,
            fn( AbstractAbility $ability ) => $ability->getCategory() === $category
        );
    }

    /**
     * Get abilities by operation type.
     *
     * @param string $operation Operation type ('read' or 'write').
     * @return array<string, AbstractAbility> Abilities with operation type.
     */
    public function byOperation( string $operation ): array {
        return array_filter(
            $this->abilities,
            fn( AbstractAbility $ability ) => $ability->getOperationType() === $operation
        );
    }

    /**
     * Unregister an ability.
     *
     * @param string $name Ability name.
     * @return bool True if ability was removed, false if not found.
     */
    public function unregister( string $name ): bool {
        if ( ! $this->has( $name ) ) {
            return false;
        }

        unset( $this->abilities[ $name ] );
        return true;
    }

    /**
     * Get all unique categories.
     *
     * @return array<string> Unique category names.
     */
    public function categories(): array {
        $categories = array_map(
            fn( AbstractAbility $ability ) => $ability->getCategory(),
            $this->abilities
        );

        return array_values( array_unique( $categories ) );
    }

    /**
     * Get all ability names.
     *
     * @return array<string> Ability names.
     */
    public function names(): array {
        return array_keys( $this->abilities );
    }

    /**
     * Get count of registered abilities.
     *
     * @return int Number of registered abilities.
     */
    public function count(): int {
        return count( $this->abilities );
    }

    /**
     * Remove all registered abilities.
     *
     * @return void
     */
    public function clear(): void {
        $this->abilities = [];
    }

    /**
     * Convert all abilities to registration arrays.
     *
     * @return array<array<string, mixed>> Array of registration arrays.
     */
    public function toArray(): array {
        return array_values(
            array_map(
                fn( AbstractAbility $ability ) => $ability->toRegistrationArray(),
                $this->abilities
            )
        );
    }
}
