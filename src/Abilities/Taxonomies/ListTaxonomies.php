<?php

/**
 * ListTaxonomies ability - retrieves registered taxonomy definitions.
 *
 * @package FAWpmcp\Abilities\Taxonomies
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Taxonomies;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Ability to list WordPress taxonomy definitions (not terms).
 *
 * Returns taxonomy registration details including:
 * - Name and labels
 * - Visibility settings (public, show_ui, show_in_rest)
 * - Hierarchy type
 * - Associated post types
 *
 * @package FAWpmcp\Abilities\Taxonomies
 */
final class ListTaxonomies extends AbstractAbility
{
    /**
     * Get the unique ability name.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/list-taxonomies';
    }

    /**
     * Get the ability category.
     *
     * @return string Category name.
     */
    public function getCategory(): string
    {
        return 'taxonomies';
    }

    /**
     * Get the human-readable label.
     *
     * @return string Ability label.
     */
    public function getLabel(): string
    {
        return 'List Taxonomies';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Retrieve registered WordPress taxonomy definitions with optional filtering by post type, visibility, and hierarchy type.';
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
                'object_type'  => array(
                    'type'        => 'string',
                    'description' => 'Filter by post type (e.g., post, page).',
                ),
                'public'       => array(
                    'type'        => 'boolean',
                    'description' => 'Filter by public visibility.',
                ),
                'show_ui'      => array(
                    'type'        => 'boolean',
                    'description' => 'Filter by admin UI visibility.',
                ),
                'hierarchical' => array(
                    'type'        => 'boolean',
                    'description' => 'Filter by hierarchy type (true = categories, false = tags).',
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
                'taxonomies' => array(
                    'type'        => 'array',
                    'description' => 'List of registered taxonomies.',
                    'items'       => array(
                        'type'       => 'object',
                        'properties' => array(
                            'name'         => array( 'type' => 'string' ),
                            'label'        => array( 'type' => 'string' ),
                            'description'  => array( 'type' => 'string' ),
                            'public'       => array( 'type' => 'boolean' ),
                            'hierarchical' => array( 'type' => 'boolean' ),
                            'show_ui'      => array( 'type' => 'boolean' ),
                            'show_in_rest' => array( 'type' => 'boolean' ),
                            'rest_base'    => array(
                                'type' => 'string',
                                'description' => 'REST API base, or empty string if disabled.',
                            ),
                            'object_type'  => array(
                                'type'  => 'array',
                                'items' => array( 'type' => 'string' ),
                            ),
                        ),
                    ),
                ),
                'total'      => array(
                    'type'        => 'integer',
                    'description' => 'Total number of taxonomies matching query.',
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
        return 'read';
    }

    /**
     * Execute the ability.
     *
     * @param array<string, mixed> $input Validated input data.
     * @return array<string, mixed> Taxonomies list.
     */
    public function doExecute(array $input): array
    {
        // Build query args from input.
        $args = $this->buildQueryArgs($input);

        // Get taxonomies as objects.
        $taxonomies = get_taxonomies($args, 'objects');

        // Format results.
        $formatted = array();
        foreach ($taxonomies as $taxonomy) {
            $formatted[] = $this->formatTaxonomy($taxonomy);
        }

        return array(
            'taxonomies' => $formatted,
            'total'      => count($formatted),
        );
    }

    /**
     * Build get_taxonomies arguments from input.
     *
     * Pure function - transforms input into query args.
     *
     * @param array<string, mixed> $input Input parameters.
     * @return array<string, mixed> get_taxonomies arguments.
     */
    private function buildQueryArgs(array $input): array
    {
        $args = array();

        // Filter by public visibility.
        if (isset($input['public'])) {
            $args['public'] = (bool) $input['public'];
        }

        // Filter by admin UI visibility.
        if (isset($input['show_ui'])) {
            $args['show_ui'] = (bool) $input['show_ui'];
        }

        // Filter by hierarchy type.
        if (isset($input['hierarchical'])) {
            $args['hierarchical'] = (bool) $input['hierarchical'];
        }

        // Filter by object type (post type).
        if (isset($input['object_type']) && '' !== $input['object_type']) {
            $args['object_type'] = array( $input['object_type'] );
        }

        return $args;
    }

    /**
     * Format a taxonomy object for output.
     *
     * Pure function - transforms taxonomy data.
     *
     * @param \WP_Taxonomy $taxonomy The taxonomy object.
     * @return array<string, mixed> Formatted taxonomy data.
     */
    private function formatTaxonomy(\WP_Taxonomy $taxonomy): array
    {
        return array(
            'name'         => $taxonomy->name,
            'label'        => $taxonomy->label,
            'description'  => $taxonomy->description ?? '',
            'public'       => (bool) $taxonomy->public,
            'hierarchical' => (bool) $taxonomy->hierarchical,
            'show_ui'      => (bool) $taxonomy->show_ui,
            'show_in_rest' => (bool) $taxonomy->show_in_rest,
            'rest_base'    => is_string($taxonomy->rest_base) ? $taxonomy->rest_base : '',
            'object_type'  => (array) $taxonomy->object_type,
        );
    }
}
