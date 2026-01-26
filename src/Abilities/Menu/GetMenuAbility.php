<?php

/**
 * GetMenuAbility - retrieves details of a specific WordPress navigation menu.
 *
 * @package FAWpmcp\Abilities\Menu
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Menu;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\MenuNotFoundException;

/**
 * Ability to get details of a specific WordPress navigation menu.
 *
 * Returns menu details including term_id, name, slug, locations, and menu items.
 *
 * @package FAWpmcp\Abilities\Menu
 */
final class GetMenuAbility extends AbstractAbility
{
    /**
     * Get the unique ability name.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/get-menu';
    }

    /**
     * Get the ability category.
     *
     * @return string Category name.
     */
    public function getCategory(): string
    {
        return 'menu';
    }

    /**
     * Get the human-readable label.
     *
     * @return string Ability label.
     */
    public function getLabel(): string
    {
        return 'Get Menu';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Get details of a specific WordPress navigation menu including its items.';
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
                'menu' => array(
                    'type'        => array( 'string', 'integer' ),
                    'description' => 'Menu ID or slug to retrieve.',
                ),
            ),
            'required'   => array( 'menu' ),
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
                'term_id'   => array(
                    'type'        => 'integer',
                    'description' => 'The menu term ID.',
                ),
                'name'      => array(
                    'type'        => 'string',
                    'description' => 'The menu name.',
                ),
                'slug'      => array(
                    'type'        => 'string',
                    'description' => 'The menu slug.',
                ),
                'locations' => array(
                    'type'        => 'array',
                    'description' => 'Theme locations assigned to this menu.',
                    'items'       => array( 'type' => 'string' ),
                ),
                'items'     => array(
                    'type'        => 'array',
                    'description' => 'Menu items.',
                    'items'       => array(
                        'type'       => 'object',
                        'properties' => array(
                            'id'         => array( 'type' => 'integer' ),
                            'parent_id'  => array( 'type' => 'integer' ),
                            'title'      => array( 'type' => 'string' ),
                            'url'        => array( 'type' => 'string' ),
                            'type'       => array( 'type' => 'string' ),
                            'object'     => array( 'type' => 'string' ),
                            'object_id'  => array( 'type' => 'integer' ),
                            'menu_order' => array( 'type' => 'integer' ),
                        ),
                    ),
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
     * @return array<string, mixed> Menu details.
     * @throws MenuNotFoundException If menu does not exist.
     */
    public function doExecute(array $input): array
    {
        $menu_identifier = $input['menu'];
        $menu            = wp_get_nav_menu_object($menu_identifier);

        if (false === $menu) {
            throw new MenuNotFoundException(
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Internal exception message.
                sprintf('Menu "%s" not found.', $menu_identifier)
            );
        }

        // Get menu items.
        $menu_items = wp_get_nav_menu_items($menu->term_id);
        $items      = array();

        if (is_array($menu_items)) {
            foreach ($menu_items as $item) {
                $items[] = array(
                    'id'         => $item->ID,
                    'parent_id'  => (int) $item->menu_item_parent,
                    'title'      => $item->title,
                    'url'        => $item->url,
                    'type'       => $item->type,
                    'object'     => $item->object,
                    'object_id'  => (int) $item->object_id,
                    'menu_order' => (int) $item->menu_order,
                );
            }
        }

        // Get locations assigned to this menu.
        $all_locations  = get_nav_menu_locations();
        $menu_locations = array();
        foreach ($all_locations as $location => $menu_id) {
            if ($menu_id === $menu->term_id) {
                $menu_locations[] = $location;
            }
        }

        return array(
            'term_id'   => $menu->term_id,
            'name'      => $menu->name,
            'slug'      => $menu->slug,
            'locations' => $menu_locations,
            'items'     => $items,
        );
    }
}
