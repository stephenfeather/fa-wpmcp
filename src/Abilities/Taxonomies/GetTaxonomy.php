<?php

/**
 * GetTaxonomy ability - retrieves a single taxonomy definition.
 *
 * @package FAWpmcp\Abilities\Taxonomies
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Taxonomies;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\PostNotFoundException;

/**
 * Ability to retrieve a single WordPress taxonomy definition by name.
 *
 * Returns complete taxonomy registration details including:
 * - Name and all labels
 * - Visibility settings
 * - Hierarchy type
 * - Associated post types
 * - Capabilities
 * - Rewrite rules
 *
 * @package FAWpmcp\Abilities\Taxonomies
 */
final class GetTaxonomy extends AbstractAbility
{
    /**
     * Get the unique ability name.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/get-taxonomy';
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
        return 'Get Taxonomy';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Retrieve a single WordPress taxonomy definition by name with full details including labels, visibility, capabilities, and rewrite rules.';
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
                'taxonomy' => array(
                    'type'        => 'string',
                    'description' => 'Taxonomy name (e.g., category, post_tag, or custom taxonomy slug).',
                ),
            ),
            'required'   => array( 'taxonomy' ),
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
                'taxonomy' => array(
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
                        'object_type'  => array(
                            'type'  => 'array',
                            'items' => array( 'type' => 'string' ),
                        ),
                        'cap'          => array( 'type' => 'object' ),
                        'rewrite'      => array(
                            'oneOf' => array(
                                array( 'type' => 'boolean' ),
                                array( 'type' => 'object' ),
                            ),
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
     * Execute the ability.
     *
     * @param array<string, mixed> $input Validated input data.
     * @return array<string, mixed> Taxonomy data.
     * @throws PostNotFoundException If taxonomy not found.
     */
    public function doExecute(array $input): array
    {
        $taxonomy_name = (string) $input['taxonomy'];

        // Get the taxonomy object.
        $taxonomy = get_taxonomy($taxonomy_name);

        // Handle not found.
        if (false === $taxonomy) {
            throw new PostNotFoundException('Taxonomy not found');
        }

        // Format and return.
        return array(
            'taxonomy' => $this->formatTaxonomy($taxonomy),
        );
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
            'labels'       => $this->formatLabels($taxonomy->labels),
            'description'  => $taxonomy->description ?? '',
            'public'       => (bool) $taxonomy->public,
            'hierarchical' => (bool) $taxonomy->hierarchical,
            'show_ui'      => (bool) $taxonomy->show_ui,
            'show_in_rest' => (bool) $taxonomy->show_in_rest,
            'rest_base'    => $taxonomy->rest_base ?? $taxonomy->name,
            'object_type'  => (array) $taxonomy->object_type,
            'cap'          => $this->formatCapabilities($taxonomy->cap),
            'rewrite'      => $this->formatRewrite($taxonomy->rewrite),
        );
    }

    /**
     * Format taxonomy labels object to array.
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
     * Format taxonomy capabilities object to array.
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
}
