<?php

/**
 * ListWidgetTypesAbility - lists all available WordPress widget types.
 *
 * @package FAWpmcp\Abilities\Widgets
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Widgets;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Ability to list all available WordPress widget types.
 *
 * Returns an array of widget types with id_base, name, and description.
 *
 * @package FAWpmcp\Abilities\Widgets
 */
final class ListWidgetTypesAbility extends AbstractAbility
{
    /**
     * Get the unique ability name.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/list-widget-types';
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
        return 'List Widget Types';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'List all available WordPress widget types that can be added to sidebars.';
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
                'widget_types' => array(
                    'type'        => 'array',
                    'description' => 'List of available widget types.',
                    'items'       => array(
                        'type'       => 'object',
                        'properties' => array(
                            'id_base'     => array( 'type' => 'string' ),
                            'name'        => array( 'type' => 'string' ),
                            'description' => array( 'type' => 'string' ),
                        ),
                    ),
                ),
                'total'        => array(
                    'type'        => 'integer',
                    'description' => 'Total number of widget types.',
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
     * @return array<string, mixed> List of widget types.
     */
    public function doExecute(array $input): array
    {
        global $wp_widget_factory;

        $widget_types = array();

        if (isset($wp_widget_factory) && isset($wp_widget_factory->widgets)) {
            foreach ($wp_widget_factory->widgets as $widget) {
                if (isset($widget->id_base)) {
                    $widget_types[] = array(
                        'id_base'     => $widget->id_base,
                        'name'        => $widget->name ?? '',
                        'description' => $widget->widget_options['description'] ?? '',
                    );
                }
            }
        }

        return array(
            'widget_types' => $widget_types,
            'total'        => count($widget_types),
        );
    }
}
