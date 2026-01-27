<?php

/**
 * DeleteWidgetAbility - removes a widget from a sidebar.
 *
 * @package FAWpmcp\Abilities\Widgets
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Widgets;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\WidgetNotFoundException;

/**
 * Ability to delete a WordPress widget.
 *
 * Removes a widget from its sidebar and optionally deletes its settings.
 *
 * @package FAWpmcp\Abilities\Widgets
 */
final class DeleteWidgetAbility extends AbstractAbility
{
    /**
     * Get the unique ability name.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/delete-widget';
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
        return 'Delete Widget';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Remove a widget from a WordPress sidebar.';
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
                    'description' => 'The widget ID to delete (e.g., "text-2").',
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
                'deleted'   => array(
                    'type'        => 'boolean',
                    'description' => 'Whether the widget was deleted.',
                ),
                'widget_id' => array(
                    'type'        => 'string',
                    'description' => 'The deleted widget ID.',
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
     * @return array<string, mixed> Deletion result.
     * @throws WidgetNotFoundException If widget does not exist.
     */
    public function doExecute(array $input): array
    {
        global $wp_registered_widgets;

        $widget_id = (string) $input['widget_id'];

        // Verify widget exists.
        if (!isset($wp_registered_widgets[$widget_id])) {
            throw new WidgetNotFoundException(
                sprintf('Widget "%s" not found.', $widget_id)
            );
        }

        // Parse widget ID to get id_base and instance number.
        $id_base  = $this->getWidgetIdBase($widget_id);
        $instance = $this->getWidgetInstanceNumber($widget_id);

        // Remove widget from all sidebars.
        $sidebars_widgets = wp_get_sidebars_widgets();
        foreach ($sidebars_widgets as $sidebar_id => $widgets) {
            if (is_array($widgets)) {
                $key = array_search($widget_id, $widgets, true);
                if (false !== $key) {
                    unset($sidebars_widgets[$sidebar_id][$key]);
                    // Re-index array.
                    $sidebars_widgets[$sidebar_id] = array_values($sidebars_widgets[$sidebar_id]);
                }
            }
        }
        wp_set_sidebars_widgets($sidebars_widgets);

        // Remove widget settings.
        $all_settings = get_option("widget_{$id_base}", array());
        if (isset($all_settings[$instance])) {
            unset($all_settings[$instance]);
            update_option("widget_{$id_base}", $all_settings);
        }

        return array(
            'deleted'   => true,
            'widget_id' => $widget_id,
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
}
