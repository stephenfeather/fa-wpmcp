<?php
/**
 * Options-based permission settings loader.
 *
 * @package FAWpmcp\Permissions
 */

declare(strict_types=1);

namespace FAWpmcp\Permissions;

use FAWpmcp\ValueObjects\PermissionSettings;

/**
 * Loads permission settings from WordPress options.
 *
 * Provides factory method to create PermissionSettings value object
 * from WordPress options storage.
 *
 * @package FAWpmcp\Permissions
 */
final class OptionsPermissionSettings {
    /**
     * Option name for permission settings.
     *
     * @var string
     */
    private const OPTION_NAME = 'fa_wpmcp_permissions';

    /**
     * Load permission settings from WordPress options.
     *
     * Returns default settings if option doesn't exist.
     *
     * @return PermissionSettings Permission settings value object.
     */
    public static function load(): PermissionSettings {
        $options = get_option( self::OPTION_NAME, array() );

        if ( ! is_array( $options ) ) {
            $options = array();
        }

        return new PermissionSettings(
            global_read_enabled: $options['global_read_enabled'] ?? true,
            global_write_enabled: $options['global_write_enabled'] ?? false,
            category_settings: $options['category_settings'] ?? array(),
            ability_settings: $options['ability_settings'] ?? array(),
        );
    }

    /**
     * Save permission settings to WordPress options.
     *
     * @param PermissionSettings $settings Settings to save.
     * @return void
     */
    public static function save( PermissionSettings $settings ): void {
        $options = array(
            'global_read_enabled'  => $settings->global_read_enabled,
            'global_write_enabled' => $settings->global_write_enabled,
            'category_settings'    => $settings->category_settings,
            'ability_settings'     => $settings->ability_settings,
        );

        update_option( self::OPTION_NAME, $options );
    }
}
