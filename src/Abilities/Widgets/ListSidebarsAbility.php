<?php

/**
 * ListSidebarsAbility - lists all registered WordPress sidebars.
 *
 * @package FAWpmcp\Abilities\Widgets
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Widgets;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Ability to list all registered WordPress sidebars.
 *
 * Returns an array of sidebars with id, name, and description.
 *
 * @package FAWpmcp\Abilities\Widgets
 */
final class ListSidebarsAbility extends AbstractAbility
{
    /**
     * Get the unique ability name.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/list-sidebars';
    }

    /**
     * Get the ability category.
     *
     * @return string Category name.
     */
    public function getCategory(): string
    {
        return 'widgets';
    }

    /**
     * Get the human-readable label.
     *
     * @return string Ability label.
     */
    public function getLabel(): string
    {
        return 'List Sidebars';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'List all registered WordPress widget sidebars.';
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
            'properties' => array(),
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
                'sidebars' => array(
                    'type'        => 'array',
                    'description' => 'List of registered sidebars.',
                    'items'       => array(
                        'type'       => 'object',
                        'properties' => array(
                            'id'          => array( 'type' => 'string' ),
                            'name'        => array( 'type' => 'string' ),
                            'description' => array( 'type' => 'string' ),
                        ),
                    ),
                ),
                'total'    => array(
                    'type'        => 'integer',
                    'description' => 'Total number of sidebars.',
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
        return 'edit_theme_options';
    }

    /**
     * Execute the ability.
     *
     * @param array<string, mixed> $input Validated input data.
     * @return array<string, mixed> List of sidebars.
     */
    public function doExecute(array $input): array
    {
        global $wp_registered_sidebars;

        $sidebars = array();

        if (is_array($wp_registered_sidebars)) {
            foreach ($wp_registered_sidebars as $sidebar_id => $sidebar) {
                $sidebars[] = array(
                    'id'          => $sidebar_id,
                    'name'        => $sidebar['name'] ?? '',
                    'description' => $sidebar['description'] ?? '',
                );
            }
        }

        return array(
            'sidebars' => $sidebars,
            'total'    => count($sidebars),
        );
    }
}
