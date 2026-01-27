<?php

/**
 * GetCoreVersionAbility - retrieves WordPress core version information.
 *
 * @package FAWpmcp\Abilities\Core
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Core;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Ability to get WordPress core version information.
 *
 * Returns the current WordPress version, PHP version, MySQL version,
 * and optional extra version details.
 *
 * @package FAWpmcp\Abilities\Core
 */
final class GetCoreVersionAbility extends AbstractAbility
{
    /**
     * Get the unique ability name.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/get-core-version';
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
        return 'Get Core Version';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Get WordPress core version along with PHP and database version information.';
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
                'extra' => array(
                    'type'        => 'boolean',
                    'description' => 'Include extra version details (git hash if available).',
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
                'wordpress_version' => array(
                    'type'        => 'string',
                    'description' => 'WordPress version string.',
                ),
                'php_version'       => array(
                    'type'        => 'string',
                    'description' => 'PHP version string.',
                ),
                'mysql_version'     => array(
                    'type'        => 'string',
                    'description' => 'MySQL/MariaDB version string.',
                ),
                'is_multisite'      => array(
                    'type'        => 'boolean',
                    'description' => 'Whether this is a multisite installation.',
                ),
                'db_charset'        => array(
                    'type'        => 'string',
                    'description' => 'Database character set.',
                ),
                'db_collate'        => array(
                    'type'        => 'string',
                    'description' => 'Database collation.',
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
     * @return array<string, mixed> WordPress version information.
     */
    public function doExecute(array $input): array
    {
        global $wpdb;

        $result = array(
            'wordpress_version' => get_bloginfo('version'),
            'php_version'       => PHP_VERSION,
            'mysql_version'     => $this->getMysqlVersion($wpdb),
            'is_multisite'      => is_multisite(),
            'db_charset'        => defined('DB_CHARSET') ? DB_CHARSET : '',
            'db_collate'        => defined('DB_COLLATE') ? DB_COLLATE : '',
        );

        // Add extra details if requested.
        $extra = $input['extra'] ?? false;
        if ($extra) {
            $result['wp_local_package'] = $this->getWpLocalPackage();
            $result['site_url']         = site_url();
            $result['home_url']         = home_url();
        }

        return $result;
    }

    /**
     * Get MySQL/MariaDB version.
     *
     * @param \wpdb|null $wpdb WordPress database object.
     * @return string Version string.
     */
    private function getMysqlVersion(?\wpdb $wpdb): string
    {
        if (null === $wpdb) {
            return 'unknown';
        }

        // Try to get server info from the database connection.
        if (method_exists($wpdb, 'db_version')) {
            return (string) $wpdb->db_version();
        }

        return 'unknown';
    }

    /**
     * Get WordPress local package (language).
     *
     * @return string Local package string.
     */
    private function getWpLocalPackage(): string
    {
        $locale = get_locale();
        return $locale ?: 'en_US';
    }
}
