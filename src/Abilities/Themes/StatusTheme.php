<?php

/**
 * StatusTheme ability - gets status details for a WordPress theme.
 *
 * @package FAWpmcp\Abilities\Themes
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Themes;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Ability to get status details for a WordPress theme.
 *
 * @package FAWpmcp\Abilities\Themes
 */
final class StatusTheme extends AbstractAbility
{
    /**
     * Returns the ability identifier.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'fa-wpmcp/status-theme';
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
        return 'Theme Status';
    }

    /**
     * Returns the ability description.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'Get status details for a WordPress theme.';
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
                'stylesheet' => array(
                    'type'        => 'string',
                    'description' => 'Theme stylesheet name.',
                ),
            ),
            'required'   => array( 'stylesheet' ),
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
                'name'    => array( 'type' => 'string' ),
                'status'  => array( 'type' => 'string' ),
                'version' => array( 'type' => 'string' ),
                'author'  => array( 'type' => 'string' ),
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
        return 'switch_themes';
    }

    /**
     * Executes the ability.
     *
     * @param array $input Input parameters.
     * @return array
     */
    public function doExecute(array $input): array
    {
        $theme        = wp_get_theme($input['stylesheet']);
        $active_theme = get_option('stylesheet');
        $is_active    = $theme->get_stylesheet() === $active_theme;

        return array(
            'name'    => $theme->get('Name'),
            'status'  => $is_active ? 'Active' : 'Inactive',
            'version' => $theme->get('Version'),
            'author'  => $theme->get('Author'),
        );
    }
}
