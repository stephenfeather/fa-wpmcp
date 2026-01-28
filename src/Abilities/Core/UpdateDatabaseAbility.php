<?php

/**
 * UpdateDatabaseAbility - runs WordPress database updates.
 *
 * @package FAWpmcp\Abilities\Core
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Core;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Ability to run WordPress database updates.
 *
 * Executes database schema updates that may be required after
 * WordPress core updates. Supports dry-run mode for preview.
 *
 * @package FAWpmcp\Abilities\Core
 */
final class UpdateDatabaseAbility extends AbstractAbility
{
    /**
     * Get the unique ability name.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/update-database';
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
        return 'Update Database';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Run WordPress database schema updates. Use dry_run=true to preview changes without applying them.';
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
                'dry_run' => array(
                    'type'        => 'boolean',
                    'description' => 'Preview database updates without applying them.',
                    'default'     => true,
                ),
                'network' => array(
                    'type'        => 'boolean',
                    'description' => 'Update all sites in multisite network.',
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
                'update_required' => array(
                    'type'        => 'boolean',
                    'description' => 'Whether database update is required.',
                ),
                'dry_run'         => array(
                    'type'        => 'boolean',
                    'description' => 'Whether this was a dry run.',
                ),
                'current_db_version' => array(
                    'type'        => 'string',
                    'description' => 'Current database version.',
                ),
                'target_db_version'  => array(
                    'type'        => 'string',
                    'description' => 'Target database version (from WordPress files).',
                ),
                'updated'         => array(
                    'type'        => 'boolean',
                    'description' => 'Whether the database was updated (false if dry run).',
                ),
                'sites_updated'   => array(
                    'type'        => 'integer',
                    'description' => 'Number of sites updated (multisite only).',
                ),
                'message'         => array(
                    'type'        => 'string',
                    'description' => 'Status message.',
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
     * Get the operation type.
     *
     * @return string 'write' since this modifies the database.
     */
    public function getOperationType(): string
    {
        return 'write';
    }

    /**
     * Get ability annotations.
     *
     * @return array<string, mixed> Annotations array.
     */
    public function getAnnotations(): array
    {
        return array(
            'readonly'     => false,
            'destructive'  => true,  // Database modifications are destructive.
            'idempotent'   => true,  // Running twice produces same result.
            'instructions' => $this->getDescription(),
        );
    }

    /**
     * Execute the ability.
     *
     * @param array<string, mixed> $input Validated input data.
     * @return array<string, mixed> Update results.
     */
    public function doExecute(array $input): array
    {
        $dry_run = $input['dry_run'] ?? true;
        $network = $input['network'] ?? false;

        // Get version information.
        $current_db_version = get_option('db_version', '0');
        $target_db_version  = $this->getTargetDbVersion();

        $update_required = version_compare($current_db_version, $target_db_version, '<');

        $result = array(
            'update_required'    => $update_required,
            'dry_run'            => $dry_run,
            'current_db_version' => $current_db_version,
            'target_db_version'  => $target_db_version,
            'updated'            => false,
            'sites_updated'      => 0,
            'message'            => '',
        );

        if (! $update_required) {
            $result['message'] = 'Database is already up to date.';
            return $result;
        }

        if ($dry_run) {
            $result['message'] = sprintf(
                'Database update required: %s -> %s. Run with dry_run=false to apply.',
                $current_db_version,
                $target_db_version
            );
            return $result;
        }

        // Actually perform the update.
        if ($network && is_multisite()) {
            $sites_updated = $this->updateNetworkDatabases();
            $result['sites_updated'] = $sites_updated;
            $result['updated']       = true;
            $result['message']       = sprintf(
                'Database updated on %d sites from %s to %s.',
                $sites_updated,
                $current_db_version,
                $target_db_version
            );
        } else {
            $this->runDatabaseUpdate();
            $result['updated']       = true;
            $result['sites_updated'] = 1;
            $result['message']       = sprintf(
                'Database updated from %s to %s.',
                $current_db_version,
                $target_db_version
            );
        }

        return $result;
    }

    /**
     * Get the target database version from WordPress core.
     *
     * @return string Target database version.
     */
    private function getTargetDbVersion(): string
    {
        global $wp_db_version;

        return (string) ($wp_db_version ?? '0');
    }

    /**
     * Run the database update for a single site.
     *
     * @return void
     */
    private function runDatabaseUpdate(): void
    {
        // Include WordPress upgrade functions (procedural file, cannot use 'use' keyword).
        if (! function_exists('wp_upgrade')) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php'; // NOSONAR - WordPress procedural include
        }

        // Run the database upgrade.
        wp_upgrade();
    }

    /**
     * Update databases for all sites in a multisite network.
     *
     * @return int Number of sites updated.
     */
    private function updateNetworkDatabases(): int
    {
        if (! is_multisite()) {
            return 0;
        }

        $sites = get_sites(
            array(
                'number' => 0, // All sites.
            )
        );

        $count = 0;

        foreach ($sites as $site) {
            switch_to_blog($site->blog_id);
            $this->runDatabaseUpdate();
            restore_current_blog();
            $count++;
        }

        return $count;
    }
}
