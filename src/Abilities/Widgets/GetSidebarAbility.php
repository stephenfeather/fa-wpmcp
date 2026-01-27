<?php

/**
 * GetSidebarAbility - retrieves details of a specific WordPress sidebar.
 *
 * @package FAWpmcp\Abilities\Widgets
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Widgets;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\SidebarNotFoundException;

/**
 * Ability to get details of a specific WordPress sidebar.
 *
 * Returns sidebar details including id, name, description, and wrapper markup.
 *
 * @package FAWpmcp\Abilities\Widgets
 */
final class GetSidebarAbility extends AbstractAbility
{
    /**
     * Get the unique ability name.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/get-sidebar';
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
        return 'Get Sidebar';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Get details of a specific WordPress widget sidebar.';
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
                    'description' => 'The sidebar ID to retrieve.',
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
                'id'            => array(
                    'type'        => 'string',
                    'description' => 'The sidebar ID.',
                ),
                'name'          => array(
                    'type'        => 'string',
                    'description' => 'The sidebar name.',
                ),
                'description'   => array(
                    'type'        => 'string',
                    'description' => 'The sidebar description.',
                ),
                'before_widget' => array(
                    'type'        => 'string',
                    'description' => 'HTML markup before each widget.',
                ),
                'after_widget'  => array(
                    'type'        => 'string',
                    'description' => 'HTML markup after each widget.',
                ),
                'before_title'  => array(
                    'type'        => 'string',
                    'description' => 'HTML markup before the widget title.',
                ),
                'after_title'   => array(
                    'type'        => 'string',
                    'description' => 'HTML markup after the widget title.',
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
     * @return array<string, mixed> Sidebar details.
     * @throws SidebarNotFoundException If sidebar does not exist.
     */
    public function doExecute(array $input): array
    {
        global $wp_registered_sidebars;

        $sidebar_id = (string) $input['sidebar_id'];

        if (!is_array($wp_registered_sidebars) || !isset($wp_registered_sidebars[$sidebar_id])) {
            throw new SidebarNotFoundException(
                sprintf('Sidebar "%s" not found.', $sidebar_id)
            );
        }

        $sidebar = $wp_registered_sidebars[$sidebar_id];

        return array(
            'id'            => $sidebar_id,
            'name'          => $sidebar['name'] ?? '',
            'description'   => $sidebar['description'] ?? '',
            'before_widget' => $sidebar['before_widget'] ?? '',
            'after_widget'  => $sidebar['after_widget'] ?? '',
            'before_title'  => $sidebar['before_title'] ?? '',
            'after_title'   => $sidebar['after_title'] ?? '',
        );
    }
}
