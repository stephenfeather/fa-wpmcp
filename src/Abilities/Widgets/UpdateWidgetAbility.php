<?php

/**
 * UpdateWidgetAbility - updates a widget's settings.
 *
 * @package FAWpmcp\Abilities\Widgets
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Widgets;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\WidgetNotFoundException;
use FAWpmcp\Exceptions\WidgetCreationException;

/**
 * Ability to update a WordPress widget's settings.
 *
 * Updates the settings of an existing widget instance.
 *
 * @package FAWpmcp\Abilities\Widgets
 */
final class UpdateWidgetAbility extends AbstractAbility
{
    /**
     * Get the unique ability name.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/update-widget';
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
        return 'Update Widget';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Update the settings of an existing WordPress widget.';
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
                    'description' => 'The widget ID to update (e.g., "text-2").',
                ),
                'settings'  => array(
                    'type'        => 'object',
                    'description' => 'The new widget settings.',
                ),
            ),
            'required'   => array( 'widget_id', 'settings' ),
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
                'widget_id' => array(
                    'type'        => 'string',
                    'description' => 'The updated widget ID.',
                ),
                'settings'  => array(
                    'type'        => 'object',
                    'description' => 'The new widget settings.',
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
     * @return array<string, mixed> Updated widget details.
     * @throws WidgetNotFoundException If widget does not exist.
     * @throws WidgetCreationException If update fails.
     */
    public function doExecute(array $input): array
    {
        global $wp_registered_widgets;

        $widget_id = (string) $input['widget_id'];
        $settings  = $input['settings'];

        // Verify widget exists.
        if (!isset($wp_registered_widgets[$widget_id])) {
            throw new WidgetNotFoundException(
                sprintf('Widget "%s" not found.', $widget_id)
            );
        }

        // Parse widget ID to get id_base and instance number.
        $id_base  = $this->getWidgetIdBase($widget_id);
        $instance = $this->getWidgetInstanceNumber($widget_id);

        // Update widget settings.
        $all_settings            = get_option("widget_{$id_base}", array());
        $all_settings[$instance] = $settings;
        $update_result           = update_option("widget_{$id_base}", $all_settings);

        if (false === $update_result) {
            // Check if settings are actually unchanged.
            $current_settings = get_option("widget_{$id_base}", array());
            if (!isset($current_settings[$instance]) || $current_settings[$instance] !== $settings) {
                throw new WidgetCreationException(
                    sprintf('Failed to update widget settings for "%s".', $widget_id)
                );
            }
        }

        return array(
            'widget_id' => $widget_id,
            'settings'  => $settings,
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
