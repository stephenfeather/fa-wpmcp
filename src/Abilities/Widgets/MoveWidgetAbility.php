<?php

/**
 * MoveWidgetAbility - moves a widget between sidebars.
 *
 * @package FAWpmcp\Abilities\Widgets
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Widgets;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\SidebarNotFoundException;
use FAWpmcp\Exceptions\WidgetNotFoundException;

/**
 * Ability to move a WordPress widget between sidebars.
 *
 * Moves a widget from its current sidebar to a target sidebar.
 *
 * @package FAWpmcp\Abilities\Widgets
 */
final class MoveWidgetAbility extends AbstractAbility
{
    /**
     * Get the unique ability name.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/move-widget';
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
        return 'Move Widget';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Move a widget from one sidebar to another.';
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
                'widget_id'         => array(
                    'type'        => 'string',
                    'description' => 'The widget ID to move (e.g., "text-2").',
                ),
                'target_sidebar_id' => array(
                    'type'        => 'string',
                    'description' => 'The target sidebar ID.',
                ),
                'position'          => array(
                    'type'        => 'integer',
                    'description' => 'Optional position in the target sidebar (0-indexed). Defaults to end.',
                ),
            ),
            'required'   => array( 'widget_id', 'target_sidebar_id' ),
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
                'widget_id'    => array(
                    'type'        => 'string',
                    'description' => 'The moved widget ID.',
                ),
                'from_sidebar' => array(
                    'type'        => 'string',
                    'description' => 'The original sidebar ID.',
                ),
                'to_sidebar'   => array(
                    'type'        => 'string',
                    'description' => 'The target sidebar ID.',
                ),
                'position'     => array(
                    'type'        => 'integer',
                    'description' => 'The new position in the target sidebar.',
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
            'idempotent'   => true,
            'instructions' => $this->getDescription(),
        );
    }

    /**
     * Execute the ability.
     *
     * @param array<string, mixed> $input Validated input data.
     * @return array<string, mixed> Move result.
     * @throws WidgetNotFoundException If widget does not exist.
     * @throws SidebarNotFoundException If target sidebar does not exist.
     */
    public function doExecute(array $input): array
    {
        global $wp_registered_sidebars, $wp_registered_widgets;

        $widget_id         = (string) $input['widget_id'];
        $target_sidebar_id = (string) $input['target_sidebar_id'];
        $position          = $input['position'] ?? null;

        // Verify widget exists.
        if (!isset($wp_registered_widgets[$widget_id])) {
            throw new WidgetNotFoundException(
                sprintf('Widget "%s" not found.', $widget_id)
            );
        }

        // Verify target sidebar exists.
        if (!is_array($wp_registered_sidebars) || !isset($wp_registered_sidebars[$target_sidebar_id])) {
            throw new SidebarNotFoundException(
                sprintf('Sidebar "%s" not found.', $target_sidebar_id)
            );
        }

        // Find current sidebar.
        $sidebars_widgets = wp_get_sidebars_widgets();
        $from_sidebar     = '';

        foreach ($sidebars_widgets as $sidebar_id => $widgets) {
            if (is_array($widgets)) {
                $key = array_search($widget_id, $widgets, true);
                if (false !== $key) {
                    $from_sidebar = (string) $sidebar_id;
                    // Remove from current sidebar.
                    unset($sidebars_widgets[$sidebar_id][$key]);
                    $sidebars_widgets[$sidebar_id] = array_values($sidebars_widgets[$sidebar_id]);
                    break;
                }
            }
        }

        // Ensure target sidebar array exists.
        if (!isset($sidebars_widgets[$target_sidebar_id])) {
            $sidebars_widgets[$target_sidebar_id] = array();
        }

        // Add to target sidebar.
        if (null !== $position && $position >= 0 && $position < count($sidebars_widgets[$target_sidebar_id])) {
            array_splice($sidebars_widgets[$target_sidebar_id], $position, 0, array( $widget_id ));
            $final_position = $position;
        } else {
            $sidebars_widgets[$target_sidebar_id][] = $widget_id;
            $final_position                         = count($sidebars_widgets[$target_sidebar_id]) - 1;
        }

        wp_set_sidebars_widgets($sidebars_widgets);

        return array(
            'widget_id'    => $widget_id,
            'from_sidebar' => $from_sidebar,
            'to_sidebar'   => $target_sidebar_id,
            'position'     => $final_position,
        );
    }
}
