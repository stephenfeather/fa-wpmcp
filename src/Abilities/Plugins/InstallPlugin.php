<?php

/**
 * Install Plugin ability for WordPress MCP.
 *
 * @package FAWpmcp\Abilities\Plugins
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Plugins;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\PluginInstallationException;

/**
 * Install Plugin Ability (INTENTIONALLY UNIMPLEMENTED)
 *
 * This ability is marked as out-of-scope for the current release.
 * It will throw a RuntimeException when called, clearly indicating to API consumers
 * that this functionality is not yet available.
 *
 * @since 1.0.0-alpha-2
 * @see https://developer.wordpress.org/reference/classes/plugin_upgrader/
 *
 * SECURITY CONSIDERATIONS FOR FUTURE IMPLEMENTATION:
 * - Must validate plugin slug against WordPress.org API before installation
 * - Should verify plugin integrity (checksums, signatures)
 * - Must prevent installation from arbitrary URLs (SSRF vulnerability)
 * - Requires filesystem write permission validation
 * - Should implement dry-run mode for testing
 * - Must handle activation separately with explicit user consent
 * - Should log all installation attempts for audit trail
 * - Consider implementing plugin allowlist/blocklist
 * - Must handle failed installations gracefully (rollback, cleanup)
 *
 * IMPLEMENTATION REQUIREMENTS:
 * - Integration with WordPress Plugin_Upgrader class
 * - Proper error handling for network failures
 * - Support for updating existing plugins vs fresh install
 * - Handling of plugin dependencies
 * - Compatibility checking against WordPress version
 */
final class InstallPlugin extends AbstractAbility
{
    /**
     * Returns the ability identifier.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'fa-wpmcp/install-plugin';
    }

    /**
     * Returns the ability category.
     *
     * @return string
     */
    public function getCategory(): string
    {
        return 'plugins';
    }

    /**
     * Returns the display label.
     *
     * @return string
     */
    public function getLabel(): string
    {
        return 'Install Plugin';
    }

    /**
     * Returns the ability description.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'Install a WordPress plugin from WordPress.org or zip URL.';
    }

    /**
     * Returns the operation type.
     *
     * @return string
     */
    public function getOperationType(): string
    {
        return 'write';
    }

    /**
     * Returns the JSON Schema for input validation.
     *
     * @return array
     */
    public function getInputSchema(): array
    {
        return array(
            'type'       => 'object',
            'properties' => array(
                'slug' => array(
                    'type'        => 'string',
                    'description' => 'Plugin slug from WordPress.org.',
                ),
            ),
            'required'   => array( 'slug' ),
        );
    }

    /**
     * Returns the JSON Schema for output.
     *
     * @return array
     */
    public function getOutputSchema(): array
    {
        return array(
            'type'       => 'object',
            'properties' => array(
                'slug'      => array( 'type' => 'string' ),
                'installed' => array( 'type' => 'boolean' ),
            ),
        );
    }

    /**
     * Returns the WordPress capability required.
     *
     * @return string
     */
    public function getRequiredCapability(): string
    {
        return 'install_plugins';
    }

    /**
     * Executes the ability.
     *
     * @param array $input Input parameters.
     * @return array
     * @throws PluginInstallationException Always, as this ability is not yet implemented.
     */
    public function doExecute(array $input): array
    {
        throw new PluginInstallationException(
            'Plugin installation is not yet implemented. This ability requires WordPress Plugin_Upgrader integration.'
        );
    }
}
