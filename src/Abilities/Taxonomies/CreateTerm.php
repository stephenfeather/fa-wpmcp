<?php

/**
 * CreateTerm ability - creates a new taxonomy term.
 *
 * @package FAWpmcp\Abilities\Taxonomies
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Taxonomies;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\PostCreationException;

/**
 * Ability to create a new WordPress taxonomy term.
 *
 * Features:
 * - Create terms in any taxonomy (category, post_tag, custom)
 * - Support hierarchical taxonomies (parent term)
 * - Set name, slug, description
 * - Input sanitization
 *
 * @package FAWpmcp\Abilities\Taxonomies
 */
final class CreateTerm extends AbstractAbility
{
    /**
     * Get the unique ability name.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/create-term';
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
        return 'Create Term';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Create a new WordPress taxonomy term (category, tag, or custom taxonomy term) with name, slug, description, and optional parent for hierarchical taxonomies.';
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
                'taxonomy'    => array(
                    'type'        => 'string',
                    'description' => 'Taxonomy name (e.g., category, post_tag, or custom taxonomy slug).',
                ),
                'name'        => array(
                    'type'        => 'string',
                    'description' => 'The term name.',
                ),
                'slug'        => array(
                    'type'        => 'string',
                    'description' => 'Optional slug for the term. Auto-generated from name if not provided.',
                ),
                'description' => array(
                    'type'        => 'string',
                    'description' => 'Optional term description.',
                ),
                'parent'      => array(
                    'type'        => 'integer',
                    'description' => 'Optional parent term ID (for hierarchical taxonomies like categories).',
                    'minimum'     => 0,
                ),
            ),
            'required'   => array( 'taxonomy', 'name' ),
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
                'term_id'  => array(
                    'type'        => 'integer',
                    'description' => 'The ID of the created term.',
                ),
                'name'     => array(
                    'type'        => 'string',
                    'description' => 'The term name.',
                ),
                'slug'     => array(
                    'type'        => 'string',
                    'description' => 'The term slug.',
                ),
                'taxonomy' => array(
                    'type'        => 'string',
                    'description' => 'The taxonomy name.',
                ),
                'link'     => array(
                    'type'        => 'string',
                    'description' => 'The term archive link.',
                ),
                'created'  => array(
                    'type'        => 'boolean',
                    'description' => 'Whether the creation was successful.',
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
        return 'manage_categories';
    }

    /**
     * Get the operation type.
     *
     * @return string 'write' for create operations.
     */
    public function getOperationType(): string
    {
        return 'write';
    }

    /**
     * Get ability annotations.
     *
     * Create operations are non-idempotent - repeated calls create new resources.
     *
     * @return array<string, mixed> Annotations array.
     */
    public function getAnnotations(): array
    {
        $annotations               = parent::getAnnotations();
        $annotations['idempotent'] = false;
        return $annotations;
    }

    /**
     * Execute the ability.
     *
     * @param array<string, mixed> $input Validated input data.
     * @return array<string, mixed> Created term data.
     * @throws PostCreationException If creation fails.
     */
    public function doExecute(array $input): array
    {
        // Pure transformation: build term data with sanitization.
        $term_data = $this->buildTermData($input);

        // Side effect: create term in database.
        $result = wp_insert_term($input['name'], $input['taxonomy'], $term_data);

        // Error handling.
        if (is_wp_error($result)) {
            throw new PostCreationException(
                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Internal exception message.
                'Failed to create term: ' . $result->get_error_message()
            );
        }

        $term_id = $result['term_id'];

        // Pure transformation: format response.
        return $this->formatResponse($term_id, $input['taxonomy']);
    }

    /**
     * Build term data array with sanitization.
     *
     * Pure function - sanitizes and transforms input into term data.
     *
     * @param array<string, mixed> $input Input parameters.
     * @return array<string, mixed> Sanitized term data.
     */
    private function buildTermData(array $input): array
    {
        $term_data = array();

        // Add slug if provided.
        if (isset($input['slug']) && '' !== $input['slug']) {
            $term_data['slug'] = sanitize_title($input['slug']);
        }

        // Add description if provided.
        if (isset($input['description']) && '' !== $input['description']) {
            $term_data['description'] = sanitize_textarea_field($input['description']);
        }

        // Add parent if provided.
        if (isset($input['parent'])) {
            $term_data['parent'] = (int) $input['parent'];
        }

        return $term_data;
    }

    /**
     * Format the response after term creation.
     *
     * @param int    $term_id  Created term ID.
     * @param string $taxonomy Taxonomy name.
     * @return array<string, mixed> Response data.
     */
    private function formatResponse(int $term_id, string $taxonomy): array
    {
        $term = get_term($term_id, $taxonomy);

        if (is_wp_error($term) || null === $term) {
            return array(
                'term_id'  => $term_id,
                'name'     => '',
                'slug'     => '',
                'taxonomy' => $taxonomy,
                'link'     => '',
                'created'  => true,
            );
        }

        return array(
            'term_id'  => $term_id,
            'name'     => $term->name,
            'slug'     => $term->slug,
            'taxonomy' => $term->taxonomy,
            'link'     => get_term_link($term),
            'created'  => true,
        );
    }
}
