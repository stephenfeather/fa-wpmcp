<?php

/**
 * ListPostTypes ability - retrieves registered post type definitions.
 *
 * @package FAWpmcp\Abilities\PostTypes
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\PostTypes;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Ability to list WordPress post type definitions (not posts).
 *
 * Returns post type registration details including:
 * - Name and labels
 * - Visibility settings (public, show_ui, show_in_rest)
 * - Hierarchy type
 * - REST API base
 *
 * @package FAWpmcp\Abilities\PostTypes
 */
final class ListPostTypes extends AbstractAbility
{
    /**
     * Get the unique ability name.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/list-post-types';
    }

    /**
     * Get the ability category.
     *
     * @return string Category name.
     */
    public function getCategory(): string
    {
        return 'post-types';
    }

    /**
     * Get the human-readable label.
     *
     * @return string Ability label.
     */
    public function getLabel(): string
    {
        return 'List Post Types';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Retrieve registered WordPress post type definitions with optional filtering by visibility and hierarchy type.';
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
                'public'          => array(
                    'type'        => 'boolean',
                    'description' => 'Filter by public visibility.',
                ),
                'show_ui'         => array(
                    'type'        => 'boolean',
                    'description' => 'Filter by admin UI visibility.',
                ),
                'hierarchical'    => array(
                    'type'        => 'boolean',
                    'description' => 'Filter by hierarchy type (true = pages, false = posts).',
                ),
                'capability_type' => array(
                    'type'        => 'string',
                    'description' => 'Filter by capability type (e.g., post, page).',
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
                'post_types' => array(
                    'type'        => 'array',
                    'description' => 'List of registered post types.',
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
                            'rest_base'    => array( 'type' => 'string' ),
                        ),
                    ),
                ),
                'total'      => array(
                    'type'        => 'integer',
                    'description' => 'Total number of post types matching query.',
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
     * Get ability annotations.
     *
     * @return array<string, mixed> Annotations array.
     */
    public function getAnnotations(): array
    {
        $annotations               = parent::getAnnotations();
        $annotations['mcp.public'] = true;
        return $annotations;
    }

    /**
     * Execute the ability.
     *
     * @param array<string, mixed> $input Validated input data.
     * @return array<string, mixed> Post types list.
     */
    public function doExecute(array $input): array
    {
        // Build query args from input.
        $args = $this->buildQueryArgs($input);

        // Get post types as objects.
        $post_types = get_post_types($args, 'objects');

        // Format results.
        $formatted = array();
        foreach ($post_types as $post_type) {
            $formatted[] = $this->formatPostType($post_type);
        }

        return array(
            'post_types' => $formatted,
            'total'      => count($formatted),
        );
    }

    /**
     * Build get_post_types arguments from input.
     *
     * Pure function - transforms input into query args.
     *
     * @param array<string, mixed> $input Input parameters.
     * @return array<string, mixed> get_post_types arguments.
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

        // Filter by capability type.
        if (isset($input['capability_type']) && '' !== $input['capability_type']) {
            $args['capability_type'] = $input['capability_type'];
        }

        return $args;
    }

    /**
     * Format a post type object for output.
     *
     * Pure function - transforms post type data.
     *
     * @param \WP_Post_Type $post_type The post type object.
     * @return array<string, mixed> Formatted post type data.
     */
    private function formatPostType(\WP_Post_Type $post_type): array
    {
        return array(
            'name'         => $post_type->name,
            'label'        => $post_type->label,
            'description'  => $post_type->description ?? '',
            'public'       => (bool) $post_type->public,
            'hierarchical' => (bool) $post_type->hierarchical,
            'show_ui'      => (bool) $post_type->show_ui,
            'show_in_rest' => (bool) $post_type->show_in_rest,
            'rest_base'    => $post_type->rest_base ?? $post_type->name,
        );
    }
}
