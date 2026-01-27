<?php

/**
 * AddWidgetAbility - adds a new widget to a sidebar.
 *
 * @package FAWpmcp\Abilities\Widgets
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Widgets;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\SidebarNotFoundException;
use FAWpmcp\Exceptions\WidgetTypeNotFoundException;
use FAWpmcp\Exceptions\WidgetCreationException;

/**
 * Ability to add a new widget to a WordPress sidebar.
 *
 * Creates a new widget instance of the specified type and adds it to the sidebar.
 *
 * @package FAWpmcp\Abilities\Widgets
 */
final class AddWidgetAbility extends AbstractAbility
{
    /**
     * Get the unique ability name.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/add-widget';
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
        return 'Add Widget';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Add a new widget to a WordPress sidebar.';
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
                'widget_type' => array(
                    'type'        => 'string',
                    'description' => 'The widget type id_base (e.g., "text", "search", "recent-posts").',
                ),
                'sidebar_id'  => array(
                    'type'        => 'string',
                    'description' => 'The sidebar ID to add the widget to.',
                ),
                'settings'    => array(
                    'type'        => 'object',
                    'description' => 'Optional widget settings.',
                ),
                'position'    => array(
                    'type'        => 'integer',
                    'description' => 'Optional position in the sidebar (0-indexed). Defaults to end.',
                ),
            ),
            'required'   => array( 'widget_type', 'sidebar_id' ),
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
                'widget_id'  => array(
                    'type'        => 'string',
                    'description' => 'The created widget ID.',
                ),
                'sidebar_id' => array(
                    'type'        => 'string',
                    'description' => 'The sidebar ID.',
                ),
                'position'   => array(
                    'type'        => 'integer',
                    'description' => 'The widget position in the sidebar.',
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
            'destructive'  => false,
            'idempotent'   => false,
            'instructions' => $this->getDescription(),
        );
    }

    /**
     * Execute the ability.
     *
     * @param array<string, mixed> $input Validated input data.
     * @return array<string, mixed> Created widget details.
     * @throws SidebarNotFoundException If sidebar does not exist.
     * @throws WidgetTypeNotFoundException If widget type does not exist.
     * @throws WidgetCreationException If widget creation fails.
     */
    public function doExecute(array $input): array
    {
        global $wp_registered_sidebars;

        $widget_type = (string) $input['widget_type'];
        $sidebar_id  = (string) $input['sidebar_id'];
        $settings    = $input['settings'] ?? array();
        $position    = $input['position'] ?? null;

        // Verify sidebar exists.
        if (!is_array($wp_registered_sidebars) || !isset($wp_registered_sidebars[$sidebar_id])) {
            throw new SidebarNotFoundException(
                sprintf('Sidebar "%s" not found.', $sidebar_id)
            );
        }

        // Verify widget type exists.
        $this->verifyWidgetTypeExists($widget_type);

        // Get next available instance number.
        $instance_number = $this->getNextInstanceNumber($widget_type);

        // Save widget settings.
        $all_settings                    = get_option("widget_{$widget_type}", array());
        $all_settings[$instance_number]  = $settings;
        $update_result                   = update_option("widget_{$widget_type}", $all_settings);

        if (false === $update_result && $settings !== array()) {
            throw new WidgetCreationException(
                sprintf('Failed to save widget settings for "%s".', $widget_type)
            );
        }

        // Generate widget ID.
        $widget_id = "{$widget_type}-{$instance_number}";

        // Add widget to sidebar.
        $sidebars_widgets = wp_get_sidebars_widgets();

        if (!isset($sidebars_widgets[$sidebar_id])) {
            $sidebars_widgets[$sidebar_id] = array();
        }

        if (null !== $position && $position >= 0 && $position < count($sidebars_widgets[$sidebar_id])) {
            // Insert at specific position.
            array_splice($sidebars_widgets[$sidebar_id], $position, 0, array( $widget_id ));
            $final_position = $position;
        } else {
            // Add to end.
            $sidebars_widgets[$sidebar_id][] = $widget_id;
            $final_position                  = count($sidebars_widgets[$sidebar_id]) - 1;
        }

        wp_set_sidebars_widgets($sidebars_widgets);

        return array(
            'widget_id'  => $widget_id,
            'sidebar_id' => $sidebar_id,
            'position'   => $final_position,
        );
    }

    /**
     * Verify that a widget type exists in the widget factory.
     *
     * @param string $widget_type Widget type id_base.
     * @return void
     * @throws WidgetTypeNotFoundException If widget type does not exist.
     */
    private function verifyWidgetTypeExists(string $widget_type): void
    {
        global $wp_widget_factory;

        if (!isset($wp_widget_factory) || !isset($wp_widget_factory->widgets)) {
            throw new WidgetTypeNotFoundException(
                sprintf('Widget type "%s" not found.', $widget_type)
            );
        }

        foreach ($wp_widget_factory->widgets as $widget) {
            if (isset($widget->id_base) && $widget->id_base === $widget_type) {
                return; // Found it.
            }
        }

        throw new WidgetTypeNotFoundException(
            sprintf('Widget type "%s" not found.', $widget_type)
        );
    }

    /**
     * Get the next available instance number for a widget type.
     *
     * @param string $widget_type Widget type id_base.
     * @return int Next instance number.
     */
    private function getNextInstanceNumber(string $widget_type): int
    {
        $all_settings = get_option("widget_{$widget_type}", array());

        if (!is_array($all_settings) || empty($all_settings)) {
            return 2; // WordPress widgets start at instance 2.
        }

        // Find the highest existing instance number.
        $max_instance = 1;
        foreach (array_keys($all_settings) as $key) {
            if (is_int($key) && $key > $max_instance) {
                $max_instance = $key;
            }
        }

        return $max_instance + 1;
    }
}
