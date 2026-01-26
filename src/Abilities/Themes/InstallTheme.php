<?php

/**
 * Install Theme ability for WordPress MCP.
 *
 * @package FAWpmcp\Abilities\Themes
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Themes;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\ThemeInstallationException;

/**
 * Install Theme Ability (INTENTIONALLY UNIMPLEMENTED)
 *
 * This ability is marked as out-of-scope for the current release.
 * It will throw a RuntimeException when called, clearly indicating to API consumers
 * that this functionality is not yet available.
 *
 * @since 1.0.0-alpha-2
 * @see https://developer.wordpress.org/reference/classes/theme_upgrader/
 *
 * SECURITY CONSIDERATIONS FOR FUTURE IMPLEMENTATION:
 * - Must validate theme slug against WordPress.org API before installation
 * - Should verify theme integrity (checksums, signatures)
 * - Must prevent installation from arbitrary URLs (SSRF vulnerability)
 * - Requires filesystem write permission validation
 * - Should implement dry-run mode for testing
 * - Must handle activation separately with explicit user consent
 * - Should log all installation attempts for audit trail
 * - Consider implementing theme allowlist/blocklist
 * - Must prevent child theme installation without parent
 * - Should validate theme compatibility with WordPress version
 *
 * IMPLEMENTATION REQUIREMENTS:
 * - Integration with WordPress Theme_Upgrader class
 * - Proper error handling for network failures
 * - Support for updating existing themes vs fresh install
 * - Handling of theme dependencies (parent themes)
 * - Validation of theme structure and required files (style.css)
 * - Must not auto-activate themes after installation
 *
 * @package FAWpmcp\Abilities\Themes
 */
final class InstallTheme extends AbstractAbility
{
    /**
     * Returns the ability identifier.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'fa-wpmcp/install-theme';
    }

    /**
     * Returns the ability category.
     *
     * @return string
     */
    public function getCategory(): string
    {
        return 'themes';
    }

    /**
     * Returns the display label.
     *
     * @return string
     */
    public function getLabel(): string
    {
        return 'Install Theme';
    }

    /**
     * Returns the ability description.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'Install a WordPress theme from WordPress.org.';
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
                    'description' => 'Theme slug from WordPress.org.',
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
                'success' => array( 'type' => 'boolean' ),
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
        return 'install_themes';
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
     * Executes the ability.
     *
     * @param array $input Input parameters.
     * @return array
     * @throws ThemeInstallationException Always thrown as this ability is not yet implemented.
     */
    public function doExecute(array $input): array
    {
        throw new ThemeInstallationException(
            'Theme installation is not yet implemented. This ability requires WordPress Theme_Upgrader integration.'
        );
    }
}
