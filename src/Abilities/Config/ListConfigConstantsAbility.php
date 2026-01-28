<?php

/**
 * ListConfigConstantsAbility - lists WordPress configuration constants.
 *
 * @package FAWpmcp\Abilities\Config
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Config;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Ability to list WordPress configuration constants.
 *
 * Returns WordPress configuration constants defined in wp-config.php
 * and core. Sensitive constants (passwords, keys, salts) are excluded
 * for security.
 *
 * @package FAWpmcp\Abilities\Config
 */
final class ListConfigConstantsAbility extends AbstractAbility
{
    use ConfigValueFormatterTrait;

    /**
     * Constants considered safe to expose.
     *
     * @var array<string>
     */
    private const SAFE_CONSTANTS = array(
        // Database (excluding password).
        'DB_NAME',
        'DB_USER',
        'DB_HOST',
        'DB_CHARSET',
        'DB_COLLATE',
        // Paths and URLs.
        'ABSPATH',
        'WP_CONTENT_DIR',
        'WP_CONTENT_URL',
        'WP_PLUGIN_DIR',
        'WP_PLUGIN_URL',
        'WPMU_PLUGIN_DIR',
        'WPMU_PLUGIN_URL',
        'WP_TEMP_DIR',
        'UPLOADS',
        'WP_HOME',
        'WP_SITEURL',
        // Debug.
        'WP_DEBUG',
        'WP_DEBUG_LOG',
        'WP_DEBUG_DISPLAY',
        'SCRIPT_DEBUG',
        'SAVEQUERIES',
        // Performance and caching.
        'WP_CACHE',
        'WP_MEMORY_LIMIT',
        'WP_MAX_MEMORY_LIMIT',
        'COMPRESS_CSS',
        'COMPRESS_SCRIPTS',
        'CONCATENATE_SCRIPTS',
        'ENFORCE_GZIP',
        // Security settings.
        'DISALLOW_FILE_EDIT',
        'DISALLOW_FILE_MODS',
        'FORCE_SSL_ADMIN',
        'FORCE_SSL_LOGIN',
        // Multisite.
        'WP_ALLOW_MULTISITE',
        'MULTISITE',
        'SUBDOMAIN_INSTALL',
        'DOMAIN_CURRENT_SITE',
        'PATH_CURRENT_SITE',
        'SITE_ID_CURRENT_SITE',
        'BLOG_ID_CURRENT_SITE',
        'SUNRISE',
        // Cron.
        'DISABLE_WP_CRON',
        'ALTERNATE_WP_CRON',
        'WP_CRON_LOCK_TIMEOUT',
        // Auto-updates.
        'AUTOMATIC_UPDATER_DISABLED',
        'WP_AUTO_UPDATE_CORE',
        // Media.
        'IMAGE_EDIT_OVERWRITE',
        'MEDIA_TRASH',
        // Post revisions.
        'WP_POST_REVISIONS',
        'AUTOSAVE_INTERVAL',
        'EMPTY_TRASH_DAYS',
        // Environment.
        'WP_ENVIRONMENT_TYPE',
        'WP_DEVELOPMENT_MODE',
        // Language.
        'WPLANG',
        'WP_LANG_DIR',
        // Other useful constants.
        'FS_METHOD',
        'FS_CHMOD_DIR',
        'FS_CHMOD_FILE',
        'WP_DEFAULT_THEME',
        'TEMPLATEPATH',
        'STYLESHEETPATH',
    );

    /**
     * Get the unique ability name.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/list-config-constants';
    }

    /**
     * Get the ability category.
     *
     * @return string Category name.
     */
    public function getCategory(): string
    {
        return 'config';
    }

    /**
     * Get the human-readable label.
     *
     * @return string Ability label.
     */
    public function getLabel(): string
    {
        return 'List Config Constants';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'List WordPress configuration constants. Sensitive values (passwords, keys, salts) are excluded.';
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
                'category' => array(
                    'type'        => 'string',
                    'description' => 'Filter by category: database, paths, debug, performance, security, multisite, cron, updates, media, revisions, environment, language, filesystem, or all.',
                    'enum'        => array(
                        'all',
                        'database',
                        'paths',
                        'debug',
                        'performance',
                        'security',
                        'multisite',
                        'cron',
                        'updates',
                        'media',
                        'revisions',
                        'environment',
                        'language',
                        'filesystem',
                    ),
                    'default'     => 'all',
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
                'constants' => array(
                    'type'        => 'array',
                    'description' => 'List of configuration constants.',
                    'items'       => array(
                        'type'       => 'object',
                        'properties' => array(
                            'name'     => array(
                                'type'        => 'string',
                                'description' => 'Constant name.',
                            ),
                            'value'    => array(
                                'type'        => 'string',
                                'description' => 'Constant value (as string).',
                            ),
                            'type'     => array(
                                'type'        => 'string',
                                'description' => 'PHP type of the value.',
                            ),
                            'category' => array(
                                'type'        => 'string',
                                'description' => 'Constant category.',
                            ),
                        ),
                    ),
                ),
                'total'     => array(
                    'type'        => 'integer',
                    'description' => 'Total number of constants returned.',
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
     * @return array<string, mixed> List of configuration constants.
     */
    public function doExecute(array $input): array
    {
        $categoryFilter = $input['category'] ?? 'all';
        $constants = array();

        foreach (self::SAFE_CONSTANTS as $constantName) {
            if (! defined($constantName)) {
                continue;
            }

            $category = $this->getConstantCategory($constantName);

            if ($categoryFilter !== 'all' && $category !== $categoryFilter) {
                continue;
            }

            $value = constant($constantName);

            $constants[] = array(
                'name'     => $constantName,
                'value'    => $this->formatValue($value),
                'type'     => gettype($value),
                'category' => $category,
            );
        }

        // Sort by name for consistent output.
        usort($constants, function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        return array(
            'constants' => $constants,
            'total'     => count($constants),
        );
    }

    /**
     * Get the category for a constant.
     *
     * @param string $name Constant name.
     * @return string Category name.
     */
    private function getConstantCategory(string $name): string
    {
        $categoryMap = array(
            'database'    => array('DB_NAME', 'DB_USER', 'DB_HOST', 'DB_CHARSET', 'DB_COLLATE'),
            'paths'       => array(
                'ABSPATH',
                'WP_CONTENT_DIR',
                'WP_CONTENT_URL',
                'WP_PLUGIN_DIR',
                'WP_PLUGIN_URL',
                'WPMU_PLUGIN_DIR',
                'WPMU_PLUGIN_URL',
                'WP_TEMP_DIR',
                'UPLOADS',
                'WP_HOME',
                'WP_SITEURL',
                'TEMPLATEPATH',
                'STYLESHEETPATH',
            ),
            'debug'       => array(
                'WP_DEBUG',
                'WP_DEBUG_LOG',
                'WP_DEBUG_DISPLAY',
                'SCRIPT_DEBUG',
                'SAVEQUERIES',
            ),
            'performance' => array(
                'WP_CACHE',
                'WP_MEMORY_LIMIT',
                'WP_MAX_MEMORY_LIMIT',
                'COMPRESS_CSS',
                'COMPRESS_SCRIPTS',
                'CONCATENATE_SCRIPTS',
                'ENFORCE_GZIP',
            ),
            'security'    => array(
                'DISALLOW_FILE_EDIT',
                'DISALLOW_FILE_MODS',
                'FORCE_SSL_ADMIN',
                'FORCE_SSL_LOGIN',
            ),
            'multisite'   => array(
                'WP_ALLOW_MULTISITE',
                'MULTISITE',
                'SUBDOMAIN_INSTALL',
                'DOMAIN_CURRENT_SITE',
                'PATH_CURRENT_SITE',
                'SITE_ID_CURRENT_SITE',
                'BLOG_ID_CURRENT_SITE',
                'SUNRISE',
            ),
            'cron'        => array('DISABLE_WP_CRON', 'ALTERNATE_WP_CRON', 'WP_CRON_LOCK_TIMEOUT'),
            'updates'     => array('AUTOMATIC_UPDATER_DISABLED', 'WP_AUTO_UPDATE_CORE'),
            'media'       => array('IMAGE_EDIT_OVERWRITE', 'MEDIA_TRASH'),
            'revisions'   => array('WP_POST_REVISIONS', 'AUTOSAVE_INTERVAL', 'EMPTY_TRASH_DAYS'),
            'environment' => array('WP_ENVIRONMENT_TYPE', 'WP_DEVELOPMENT_MODE'),
            'language'    => array('WPLANG', 'WP_LANG_DIR'),
            'filesystem'  => array(
                'FS_METHOD',
                'FS_CHMOD_DIR',
                'FS_CHMOD_FILE',
                'WP_DEFAULT_THEME',
            ),
        );

        foreach ($categoryMap as $category => $constants) {
            if (in_array($name, $constants, true)) {
                return $category;
            }
        }

        return 'other';
    }
}
