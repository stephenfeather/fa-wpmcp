<?php

/**
 * GetWidgetAbility - retrieves details of a specific widget.
 *
 * @package FAWpmcp\Abilities\Widgets
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Widgets;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\WidgetNotFoundException;

/**
 * Ability to get details of a specific widget.
 *
 * Returns widget details including id, name, id_base, instance, sidebar_id, position, and settings.
 *
 * @package FAWpmcp\Abilities\Widgets
 */
final class GetWidgetAbility extends AbstractAbility
{
    /**
     * Get the unique ability name.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/get-widget';
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
        return 'Get Widget';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Get details of a specific WordPress widget by its ID.';
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
                'widget_id' => array(
                    'type'        => 'string',
                    'description' => 'The widget ID to retrieve (e.g., "text-2").',
                ),
            ),
            'required'   => array( 'widget_id' ),
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
                'id'         => array(
                    'type'        => 'string',
                    'description' => 'The widget ID.',
                ),
                'name'       => array(
                    'type'        => 'string',
                    'description' => 'The widget display name.',
                ),
                'id_base'    => array(
                    'type'        => 'string',
                    'description' => 'The widget type identifier.',
                ),
                'instance'   => array(
                    'type'        => 'integer',
                    'description' => 'The widget instance number.',
                ),
                'sidebar_id' => array(
                    'type'        => 'string',
                    'description' => 'The sidebar ID where the widget is placed.',
                ),
                'position'   => array(
                    'type'        => 'integer',
                    'description' => 'The widget position in the sidebar.',
                ),
                'settings'   => array(
                    'type'        => 'object',
                    'description' => 'The widget settings.',
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
     * @return array<string, mixed> Widget details.
     * @throws WidgetNotFoundException If widget does not exist.
     */
    public function doExecute(array $input): array
    {
        global $wp_registered_widgets;

        $widget_id = (string) $input['widget_id'];

        if (!isset($wp_registered_widgets[$widget_id])) {
            throw new WidgetNotFoundException(
                sprintf('Widget "%s" not found.', $widget_id)
            );
        }

        $registered = $wp_registered_widgets[$widget_id];

        // Parse widget ID to get id_base and instance number.
        $id_base  = $this->getWidgetIdBase($widget_id);
        $instance = $this->getWidgetInstanceNumber($widget_id);

        // Get widget settings.
        $settings     = array();
        $all_settings = get_option("widget_{$id_base}");
        if (is_array($all_settings) && isset($all_settings[$instance])) {
            $settings = $all_settings[$instance];
        }

        // Find sidebar containing this widget.
        $sidebar_id = $this->findWidgetSidebar($widget_id);
        $position   = $this->findWidgetPosition($widget_id, $sidebar_id);

        return array(
            'id'         => $widget_id,
            'name'       => $registered['name'] ?? '',
            'id_base'    => $id_base,
            'instance'   => $instance,
            'sidebar_id' => $sidebar_id,
            'position'   => $position,
            'settings'   => $settings,
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

    /**
     * Find the sidebar containing a widget.
     *
     * @param string $widget_id Widget ID.
     * @return string Sidebar ID or empty string if not in any sidebar.
     */
    private function findWidgetSidebar(string $widget_id): string
    {
        $sidebars_widgets = wp_get_sidebars_widgets();

        foreach ($sidebars_widgets as $sidebar_id => $widgets) {
            if (is_array($widgets) && in_array($widget_id, $widgets, true)) {
                return (string) $sidebar_id;
            }
        }

        return '';
    }

    /**
     * Find the position of a widget in a sidebar.
     *
     * @param string $widget_id  Widget ID.
     * @param string $sidebar_id Sidebar ID.
     * @return int Position (0-indexed) or -1 if not found.
     */
    private function findWidgetPosition(string $widget_id, string $sidebar_id): int
    {
        if ('' === $sidebar_id) {
            return -1;
        }

        $sidebars_widgets = wp_get_sidebars_widgets();

        if (!isset($sidebars_widgets[$sidebar_id]) || !is_array($sidebars_widgets[$sidebar_id])) {
            return -1;
        }

        $position = array_search($widget_id, $sidebars_widgets[$sidebar_id], true);

        return (false === $position) ? -1 : (int) $position;
    }
}
