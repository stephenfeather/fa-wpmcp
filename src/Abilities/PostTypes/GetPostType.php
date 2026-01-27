<?php

/**
 * GetPostType ability - retrieves a single post type definition.
 *
 * @package FAWpmcp\Abilities\PostTypes
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\PostTypes;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\PostNotFoundException;

/**
 * Ability to retrieve a single WordPress post type definition by name.
 *
 * Returns complete post type registration details including:
 * - Name and all labels
 * - Visibility settings
 * - Hierarchy type
 * - Capabilities
 * - Rewrite rules
 * - Supported features
 * - Associated taxonomies
 *
 * @package FAWpmcp\Abilities\PostTypes
 */
final class GetPostType extends AbstractAbility
{
    /**
     * Get the unique ability name.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/get-post-type';
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
        return 'Get Post Type';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Retrieve a single WordPress post type definition by name with full details including labels, visibility, capabilities, and rewrite rules.';
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
                'post_type' => array(
                    'type'        => 'string',
                    'description' => 'Post type name (e.g., post, page, or custom post type slug).',
                ),
            ),
            'required'   => array( 'post_type' ),
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
                'post_type' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'name'         => array( 'type' => 'string' ),
                        'label'        => array( 'type' => 'string' ),
                        'labels'       => array( 'type' => 'object' ),
                        'description'  => array( 'type' => 'string' ),
                        'public'       => array( 'type' => 'boolean' ),
                        'hierarchical' => array( 'type' => 'boolean' ),
                        'show_ui'      => array( 'type' => 'boolean' ),
                        'show_in_rest' => array( 'type' => 'boolean' ),
                        'rest_base'    => array( 'type' => 'string' ),
                        'cap'          => array( 'type' => 'object' ),
                        'rewrite'      => array(
                            'oneOf' => array(
                                array( 'type' => 'boolean' ),
                                array( 'type' => 'object' ),
                            ),
                        ),
                        'supports'     => array(
                            'type'  => 'array',
                            'items' => array( 'type' => 'string' ),
                        ),
                        'taxonomies'   => array(
                            'type'  => 'array',
                            'items' => array( 'type' => 'string' ),
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
     * @return array<string, mixed> Post type data.
     * @throws PostNotFoundException If post type not found.
     */
    public function doExecute(array $input): array
    {
        $post_type_name = (string) $input['post_type'];

        // Get the post type object.
        $post_type = get_post_type_object($post_type_name);

        // Handle not found.
        if (null === $post_type) {
            throw new PostNotFoundException('Post type not found');
        }

        // Format and return.
        return array(
            'post_type' => $this->formatPostType($post_type),
        );
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
            'labels'       => $this->formatLabels($post_type->labels),
            'description'  => $post_type->description ?? '',
            'public'       => (bool) $post_type->public,
            'hierarchical' => (bool) $post_type->hierarchical,
            'show_ui'      => (bool) $post_type->show_ui,
            'show_in_rest' => (bool) $post_type->show_in_rest,
            'rest_base'    => is_string($post_type->rest_base) ? $post_type->rest_base : ($post_type->name ?? ''),
            'cap'          => $this->formatCapabilities($post_type->cap),
            'rewrite'      => $this->formatRewrite($post_type->rewrite),
            'supports'     => $this->formatSupports($post_type->supports),
            'taxonomies'   => (array) $post_type->taxonomies,
        );
    }

    /**
     * Format post type labels object to array.
     *
     * @param object|null $labels Labels object.
     * @return array<string, string> Formatted labels.
     */
    private function formatLabels($labels): array
    {
        if (null === $labels || ! is_object($labels)) {
            return array();
        }

        return (array) $labels;
    }

    /**
     * Format post type capabilities object to array.
     *
     * @param object|null $cap Capabilities object.
     * @return array<string, string> Formatted capabilities.
     */
    private function formatCapabilities($cap): array
    {
        if (null === $cap || ! is_object($cap)) {
            return array();
        }

        return (array) $cap;
    }

    /**
     * Format rewrite rules.
     *
     * @param mixed $rewrite Rewrite configuration.
     * @return mixed Formatted rewrite config.
     */
    private function formatRewrite($rewrite)
    {
        if (is_array($rewrite)) {
            return $rewrite;
        }

        if (is_object($rewrite)) {
            return (array) $rewrite;
        }

        return (bool) $rewrite;
    }

    /**
     * Format supports array.
     *
     * @param mixed $supports Supports configuration.
     * @return array<int, string> Formatted supports array.
     */
    private function formatSupports($supports): array
    {
        if (is_array($supports)) {
            return array_values($supports);
        }

        return array();
    }
}
