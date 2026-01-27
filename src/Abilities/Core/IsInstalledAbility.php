<?php

/**
 * IsInstalledAbility - checks if WordPress is properly installed.
 *
 * @package FAWpmcp\Abilities\Core
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Core;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Ability to check if WordPress is properly installed.
 *
 * Verifies WordPress installation status including database tables
 * and network configuration for multisite.
 *
 * @package FAWpmcp\Abilities\Core
 */
final class IsInstalledAbility extends AbstractAbility
{
    /**
     * Get the unique ability name.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/is-installed';
    }

    /**
     * Get the ability category.
     *
     * @return string Category name.
     */
    public function getCategory(): string
    {
        return 'core';
    }

    /**
     * Get the human-readable label.
     *
     * @return string Ability label.
     */
    public function getLabel(): string
    {
        return 'Is Installed';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Check if WordPress is properly installed and configured.';
    }

    /**
     * Get the input schema.
     *
     * @return array<string, mixed> JSON Schema array.
     */
    public function getInputSchema(): array
    {
        return array(
            'type'       => 'object',
            'properties' => array(
                'network' => array(
                    'type'        => 'boolean',
                    'description' => 'Check if multisite network is installed (not just enabled).',
                    'default'     => false,
                ),
            ),
        );
    }

    /**
     * Get the output schema.
     *
     * @return array<string, mixed> JSON Schema array.
     */
    public function getOutputSchema(): array
    {
        return array(
            'type'       => 'object',
            'properties' => array(
                'installed'        => array(
                    'type'        => 'boolean',
                    'description' => 'Whether WordPress is installed.',
                ),
                'database_ready'   => array(
                    'type'        => 'boolean',
                    'description' => 'Whether database tables exist.',
                ),
                'has_admin_user'   => array(
                    'type'        => 'boolean',
                    'description' => 'Whether at least one admin user exists.',
                ),
                'is_multisite'     => array(
                    'type'        => 'boolean',
                    'description' => 'Whether multisite is enabled.',
                ),
                'network_installed' => array(
                    'type'        => 'boolean',
                    'description' => 'Whether multisite network tables exist (if network check requested).',
                ),
                'site_url'         => array(
                    'type'        => 'string',
                    'description' => 'Site URL.',
                ),
                'db_prefix'        => array(
                    'type'        => 'string',
                    'description' => 'Database table prefix.',
                ),
            ),
        );
    }

    /**
     * Get the required WordPress capability.
     *
     * @return string WordPress capability name.
     */
    public function getRequiredCapability(): string
    {
        return 'manage_options';
    }

    /**
     * Execute the ability.
     *
     * @param array<string, mixed> $input Validated input data.
     * @return array<string, mixed> Installation status.
     */
    public function doExecute(array $input): array
    {
        global $wpdb;

        $check_network = $input['network'] ?? false;

        // Check if WordPress is installed.
        $is_installed = is_blog_installed();

        // Check if database tables exist.
        $database_ready = $this->checkDatabaseTables($wpdb);

        // Check for admin user.
        $has_admin_user = $this->hasAdminUser();

        // Multisite checks.
        $is_multisite       = is_multisite();
        $network_installed = false;

        if ($check_network) {
            $network_installed = $this->checkNetworkTables($wpdb);
        }

        return array(
            'installed'         => $is_installed,
            'database_ready'    => $database_ready,
            'has_admin_user'    => $has_admin_user,
            'is_multisite'      => $is_multisite,
            'network_installed' => $network_installed,
            'site_url'          => site_url(),
            'db_prefix'         => $wpdb->prefix ?? '',
        );
    }

    /**
     * Check if required WordPress database tables exist.
     *
     * @param \wpdb|null $wpdb WordPress database object.
     * @return bool True if tables exist.
     */
    private function checkDatabaseTables(?\wpdb $wpdb): bool
    {
        if (null === $wpdb) {
            return false;
        }

        $required_tables = array(
            'posts',
            'postmeta',
            'options',
            'users',
            'usermeta',
        );

        foreach ($required_tables as $table) {
            $table_name = $wpdb->prefix . $table;
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    'SHOW TABLES LIKE %s',
                    $table_name
                )
            );

            if (! $exists) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if multisite network tables exist.
     *
     * @param \wpdb|null $wpdb WordPress database object.
     * @return bool True if network tables exist.
     */
    private function checkNetworkTables(?\wpdb $wpdb): bool
    {
        if (null === $wpdb) {
            return false;
        }

        $network_tables = array(
            'site',
            'sitemeta',
            'blogs',
            'blogmeta',
        );

        foreach ($network_tables as $table) {
            $table_name = $wpdb->base_prefix . $table;
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    'SHOW TABLES LIKE %s',
                    $table_name
                )
            );

            if (! $exists) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if at least one admin user exists.
     *
     * @return bool True if admin user exists.
     */
    private function hasAdminUser(): bool
    {
        $admins = get_users(
            array(
                'role'   => 'administrator',
                'number' => 1,
            )
        );

        return ! empty($admins);
    }
}
