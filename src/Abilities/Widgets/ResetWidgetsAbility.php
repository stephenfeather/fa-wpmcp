<?php

/**
 * ResetWidgetsAbility - clears all widgets from a sidebar.
 *
 * @package FAWpmcp\Abilities\Widgets
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Widgets;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\SidebarNotFoundException;

/**
 * Ability to reset widgets in a WordPress sidebar.
 *
 * Removes all widgets from a specific sidebar or all sidebars.
 *
 * @package FAWpmcp\Abilities\Widgets
 */
final class ResetWidgetsAbility extends AbstractAbility
{
    /**
     * Get the unique ability name.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/reset-widgets';
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
        return 'Reset Widgets';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Clear all widgets from a sidebar or all sidebars. This is a destructive operation.';
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
                'sidebar_id' => array(
                    'type'        => 'string',
                    'description' => 'Optional sidebar ID. If omitted, all sidebars are reset.',
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
                'reset'      => array(
                    'type'        => 'boolean',
                    'description' => 'Whether the reset was successful.',
                ),
                'sidebar_id' => array(
                    'type'        => 'string',
                    'description' => 'The reset sidebar ID or "all" if all sidebars were reset.',
                ),
                'count'      => array(
                    'type'        => 'integer',
                    'description' => 'Number of widgets removed.',
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
     * Get the operation type.
     *
     * @return string 'write' for this ability.
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
            'destructive'  => true,
            'idempotent'   => true,
            'instructions' => $this->getDescription(),
        );
    }

    /**
     * Execute the ability.
     *
     * @param array<string, mixed> $input Validated input data.
     * @return array<string, mixed> Reset result.
     * @throws SidebarNotFoundException If specified sidebar does not exist.
     */
    public function doExecute(array $input): array
    {
        global $wp_registered_sidebars;

        $sidebar_id       = $input['sidebar_id'] ?? null;
        $sidebars_widgets = wp_get_sidebars_widgets();
        $removed_count    = 0;

        if (null !== $sidebar_id && '' !== $sidebar_id) {
            // Reset specific sidebar.
            if (!is_array($wp_registered_sidebars) || !isset($wp_registered_sidebars[$sidebar_id])) {
                throw new SidebarNotFoundException(
                    sprintf('Sidebar "%s" not found.', $sidebar_id)
                );
            }

            if (isset($sidebars_widgets[$sidebar_id]) && is_array($sidebars_widgets[$sidebar_id])) {
                $removed_count                 = count($sidebars_widgets[$sidebar_id]);
                $sidebars_widgets[$sidebar_id] = array();
            }

            wp_set_sidebars_widgets($sidebars_widgets);

            return array(
                'reset'      => true,
                'sidebar_id' => $sidebar_id,
                'count'      => $removed_count,
            );
        }

        // Reset all sidebars.
        foreach ($sidebars_widgets as $sid => $widgets) {
            if (is_array($widgets) && 'wp_inactive_widgets' !== $sid) {
                $removed_count          += count($widgets);
                $sidebars_widgets[$sid]  = array();
            }
        }

        wp_set_sidebars_widgets($sidebars_widgets);

        return array(
            'reset'      => true,
            'sidebar_id' => 'all',
            'count'      => $removed_count,
        );
    }
}
