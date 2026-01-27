<?php

/**
 * CheckCoreUpdatesAbility - checks for available WordPress core updates.
 *
 * @package FAWpmcp\Abilities\Core
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Core;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Ability to check for available WordPress core updates.
 *
 * Returns information about available minor and major WordPress updates.
 *
 * @package FAWpmcp\Abilities\Core
 */
final class CheckCoreUpdatesAbility extends AbstractAbility
{
    /**
     * Get the unique ability name.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/check-core-updates';
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
        return 'Check Core Updates';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Check for available WordPress core updates (minor and major versions).';
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
                'minor' => array(
                    'type'        => 'boolean',
                    'description' => 'Only show minor version updates (e.g., 6.9.1 to 6.9.2).',
                    'default'     => false,
                ),
                'major' => array(
                    'type'        => 'boolean',
                    'description' => 'Only show major version updates (e.g., 6.9 to 7.0).',
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
                'current_version' => array(
                    'type'        => 'string',
                    'description' => 'Currently installed WordPress version.',
                ),
                'updates'         => array(
                    'type'        => 'array',
                    'description' => 'List of available updates.',
                    'items'       => array(
                        'type'       => 'object',
                        'properties' => array(
                            'version'  => array( 'type' => 'string' ),
                            'response' => array( 'type' => 'string' ),
                            'download' => array( 'type' => 'string' ),
                            'locale'   => array( 'type' => 'string' ),
                            'packages' => array( 'type' => 'object' ),
                        ),
                    ),
                ),
                'update_available' => array(
                    'type'        => 'boolean',
                    'description' => 'Whether any updates are available.',
                ),
                'last_checked'     => array(
                    'type'        => 'string',
                    'description' => 'ISO 8601 timestamp of last update check.',
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
        return 'update_core';
    }

    /**
     * Execute the ability.
     *
     * @param array<string, mixed> $input Validated input data.
     * @return array<string, mixed> Update information.
     */
    public function doExecute(array $input): array
    {
        // Force an update check.
        wp_version_check();

        $update_data     = get_site_transient('update_core');
        $current_version = get_bloginfo('version');

        $updates = array();
        $minor_only = $input['minor'] ?? false;
        $major_only = $input['major'] ?? false;

        if ($update_data && isset($update_data->updates) && is_array($update_data->updates)) {
            foreach ($update_data->updates as $update) {
                // Skip the current version (response = 'latest').
                if ('latest' === $update->response) {
                    continue;
                }

                // Filter by minor/major if requested.
                if ($minor_only || $major_only) {
                    $is_minor = $this->isMinorUpdate($current_version, $update->version);

                    if ($minor_only && ! $is_minor) {
                        continue;
                    }
                    if ($major_only && $is_minor) {
                        continue;
                    }
                }

                $updates[] = array(
                    'version'  => $update->version,
                    'response' => $update->response,
                    'download' => $update->download ?? '',
                    'locale'   => $update->locale ?? 'en_US',
                    'packages' => isset($update->packages) ? (array) $update->packages : array(),
                );
            }
        }

        $last_checked = '';
        if ($update_data && isset($update_data->last_checked)) {
            $last_checked = gmdate('c', $update_data->last_checked);
        }

        return array(
            'current_version'  => $current_version,
            'updates'          => $updates,
            'update_available' => count($updates) > 0,
            'last_checked'     => $last_checked,
        );
    }

    /**
     * Determine if an update is a minor version update.
     *
     * Minor updates change the patch version (e.g., 6.9.1 -> 6.9.2).
     * Major updates change the major or minor version (e.g., 6.9 -> 6.10 or 7.0).
     *
     * @param string $current Current version.
     * @param string $update  Update version.
     * @return bool True if minor update, false if major update.
     */
    private function isMinorUpdate(string $current, string $update): bool
    {
        $current_parts = explode('.', $current);
        $update_parts  = explode('.', $update);

        // Compare major.minor parts.
        $current_major_minor = ($current_parts[0] ?? '0') . '.' . ($current_parts[1] ?? '0');
        $update_major_minor  = ($update_parts[0] ?? '0') . '.' . ($update_parts[1] ?? '0');

        return $current_major_minor === $update_major_minor;
    }
}
