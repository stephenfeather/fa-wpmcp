<?php

/**
 * ListWidgetsAbility - lists all widgets in a specific sidebar.
 *
 * @package FAWpmcp\Abilities\Widgets
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Widgets;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\SidebarNotFoundException;

/**
 * Ability to list all widgets in a specific sidebar.
 *
 * Returns an array of widgets with id, name, id_base, position, and settings.
 *
 * @package FAWpmcp\Abilities\Widgets
 */
final class ListWidgetsAbility extends AbstractAbility
{
    /**
     * Get the unique ability name.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/list-widgets';
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
        return 'List Widgets';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'List all widgets in a specific WordPress sidebar.';
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
                    'description' => 'The sidebar ID to list widgets from.',
                ),
            ),
            'required'   => array( 'sidebar_id' ),
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
                'widgets' => array(
                    'type'        => 'array',
                    'description' => 'List of widgets in the sidebar.',
                    'items'       => array(
                        'type'       => 'object',
                        'properties' => array(
                            'id'       => array( 'type' => 'string' ),
                            'name'     => array( 'type' => 'string' ),
                            'id_base'  => array( 'type' => 'string' ),
                            'position' => array( 'type' => 'integer' ),
                            'settings' => array( 'type' => 'object' ),
                        ),
                    ),
                ),
                'total'   => array(
                    'type'        => 'integer',
                    'description' => 'Total number of widgets.',
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
     * @return array<string, mixed> List of widgets.
     * @throws SidebarNotFoundException If sidebar does not exist.
     */
    public function doExecute(array $input): array
    {
        global $wp_registered_sidebars, $wp_registered_widgets;

        $sidebar_id = (string) $input['sidebar_id'];

        // Verify sidebar exists.
        if (!is_array($wp_registered_sidebars) || !isset($wp_registered_sidebars[$sidebar_id])) {
            throw new SidebarNotFoundException(
                sprintf('Sidebar "%s" not found.', $sidebar_id)
            );
        }

        $sidebars_widgets = wp_get_sidebars_widgets();
        $widgets          = array();

        if (isset($sidebars_widgets[$sidebar_id]) && is_array($sidebars_widgets[$sidebar_id])) {
            $position = 0;
            foreach ($sidebars_widgets[$sidebar_id] as $widget_id) {
                $widget_data = $this->getWidgetData($widget_id);
                if (null !== $widget_data) {
                    $widget_data['position'] = $position;
                    $widgets[]               = $widget_data;
                }
                $position++;
            }
        }

        return array(
            'widgets' => $widgets,
            'total'   => count($widgets),
        );
    }

    /**
     * Get widget data by widget ID.
     *
     * @param string $widget_id Widget ID (e.g., "text-2").
     * @return array<string, mixed>|null Widget data or null if not found.
     */
    private function getWidgetData(string $widget_id): ?array
    {
        global $wp_registered_widgets;

        if (!isset($wp_registered_widgets[$widget_id])) {
            return null;
        }

        $registered = $wp_registered_widgets[$widget_id];

        // Parse widget ID to get id_base and instance number.
        $id_base  = $this->getWidgetIdBase($widget_id);
        $instance = $this->getWidgetInstanceNumber($widget_id);

        // Get widget settings.
        $settings      = array();
        $all_settings  = get_option("widget_{$id_base}");
        if (is_array($all_settings) && isset($all_settings[$instance])) {
            $settings = $all_settings[$instance];
        }

        return array(
            'id'       => $widget_id,
            'name'     => $registered['name'] ?? '',
            'id_base'  => $id_base,
            'settings' => $settings,
        );
    }

    /**
     * Extract id_base from widget ID.
     *
     * @param string $widget_id Widget ID (e.g., "text-2").
     * @return string Widget id_base (e.g., "text").
     */
    private function getWidgetIdBase(string $widget_id): string
    {
        // Widget ID format: {id_base}-{instance_number}
        $last_dash = strrpos($widget_id, '-');
        if (false === $last_dash) {
            return $widget_id;
        }
        return substr($widget_id, 0, $last_dash);
    }

    /**
     * Extract instance number from widget ID.
     *
     * @param string $widget_id Widget ID (e.g., "text-2").
     * @return int Widget instance number (e.g., 2).
     */
    private function getWidgetInstanceNumber(string $widget_id): int
    {
        $last_dash = strrpos($widget_id, '-');
        if (false === $last_dash) {
            return 0;
        }
        return (int) substr($widget_id, $last_dash + 1);
    }
}
