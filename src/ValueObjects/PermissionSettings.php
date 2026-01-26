<?php

/**
 * Permission settings value object.
 *
 * @package FAWpmcp\ValueObjects
 */

declare(strict_types=1);

namespace FAWpmcp\ValueObjects;

/**
 * Immutable permission settings.
 *
 * Holds the complete permission configuration for the plugin.
 * Uses readonly properties for immutability. Modifications create
 * new instances via with* methods.
 *
 * @package FAWpmcp\ValueObjects
 */
final readonly class PermissionSettings
{
    /**
     * Constructor.
     *
     * @param bool                                $global_read_enabled  Global read permission.
     * @param bool                                $global_write_enabled Global write permission.
     * @param array<string, array<string, mixed>> $category_settings   Category-level permissions.
     * @param array<string, array<string, mixed>> $ability_settings    Ability-level permissions.
     */
    public function __construct(
        public bool $global_read_enabled,
        public bool $global_write_enabled,
        public array $category_settings,
        public array $ability_settings,
    ) {
    }

    /**
     * Create new instance with modified global read setting.
     *
     * @param bool $enabled New global read setting.
     * @return self New instance with updated setting.
     */
    public function withGlobalRead(bool $enabled): self
    {
        return new self(
            $enabled,
            $this->global_write_enabled,
            $this->category_settings,
            $this->ability_settings
        );
    }

    /**
     * Create new instance with modified global write setting.
     *
     * @param bool $enabled New global write setting.
     * @return self New instance with updated setting.
     */
    public function withGlobalWrite(bool $enabled): self
    {
        return new self(
            $this->global_read_enabled,
            $enabled,
            $this->category_settings,
            $this->ability_settings
        );
    }
}
