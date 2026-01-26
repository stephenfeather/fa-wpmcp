<?php

/**
 * UpdateTerm ability - updates an existing taxonomy term.
 *
 * @package FAWpmcp\Abilities\Taxonomies
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Taxonomies;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\PostNotFoundException;
use FAWpmcp\Exceptions\PostUpdateException;

/**
 * Ability to update an existing WordPress taxonomy term.
 *
 * Features:
 * - Partial updates (only provided fields are updated)
 * - Update name, slug, description, parent
 * - Input sanitization
 * - Works with any taxonomy
 *
 * @package FAWpmcp\Abilities\Taxonomies
 */
final class UpdateTerm extends AbstractAbility {

	/**
	 * Get the unique ability name.
	 *
	 * @return string Ability name.
	 */
	public function getName(): string {
		return 'fa-wpmcp/update-term';
	}

	/**
	 * Get the ability category.
	 *
	 * @return string Category name.
	 */
	public function getCategory(): string {
		return 'taxonomies';
	}

	/**
	 * Get the human-readable label.
	 *
	 * @return string Ability label.
	 */
	public function getLabel(): string {
		return 'Update Term';
	}

	/**
	 * Get the ability description.
	 *
	 * @return string Description.
	 */
	public function getDescription(): string {
		return 'Update an existing WordPress taxonomy term. Only provided fields will be updated. Supports name, slug, description, and parent changes.';
	}

	/**
	 * Get the input schema.
	 *
	 * @return array<string, mixed> JSON Schema array.
	 */
	public function getInputSchema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'term_id'     => array(
					'type'        => 'integer',
					'description' => 'The ID of the term to update.',
					'minimum'     => 1,
				),
				'taxonomy'    => array(
					'type'        => 'string',
					'description' => 'Taxonomy name (required to identify the term).',
				),
				'name'        => array(
					'type'        => 'string',
					'description' => 'The new term name.',
				),
				'slug'        => array(
					'type'        => 'string',
					'description' => 'The new term slug.',
				),
				'description' => array(
					'type'        => 'string',
					'description' => 'The new term description.',
				),
				'parent'      => array(
					'type'        => 'integer',
					'description' => 'The new parent term ID (for hierarchical taxonomies).',
					'minimum'     => 0,
				),
			),
			'required'   => array( 'term_id', 'taxonomy' ),
		);
	}

	/**
	 * Get the output schema.
	 *
	 * @return array<string, mixed> JSON Schema array.
	 */
	public function getOutputSchema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'term_id' => array(
					'type'        => 'integer',
					'description' => 'The ID of the updated term.',
				),
				'name'    => array(
					'type'        => 'string',
					'description' => 'The term name.',
				),
				'slug'    => array(
					'type'        => 'string',
					'description' => 'The term slug.',
				),
				'link'    => array(
					'type'        => 'string',
					'description' => 'The term archive link.',
				),
				'updated' => array(
					'type'        => 'boolean',
					'description' => 'Whether the update was successful.',
				),
			),
		);
	}

	/**
	 * Get the required WordPress capability.
	 *
	 * @return string WordPress capability name.
	 */
	public function getRequiredCapability(): string {
		return 'manage_categories';
	}

	/**
	 * Get the operation type.
	 *
	 * @return string 'write' for update operations.
	 */
	public function getOperationType(): string {
		return 'write';
	}

	/**
	 * Execute the ability.
	 *
	 * @param array<string, mixed> $input Validated input data.
	 * @return array<string, mixed> Updated term data.
	 * @throws PostNotFoundException If term not found.
	 * @throws PostUpdateException If update fails.
	 */
	public function doExecute( array $input ): array {
		$term_id  = (int) $input['term_id'];
		$taxonomy = $input['taxonomy'];

		// Side effect: verify term exists.
		$term = get_term( $term_id, $taxonomy );

		if ( is_wp_error( $term ) || null === $term ) {
			throw new PostNotFoundException( 'Term not found' );
		}

		// Pure transformation: build update data with sanitization.
		$update_data = $this->buildUpdateData( $input );

		// Side effect: update term in database.
		$result = wp_update_term( $term_id, $taxonomy, $update_data );

		// Error handling.
		if ( is_wp_error( $result ) ) {
			throw new PostUpdateException(
                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Internal exception message.
				'Failed to update term: ' . $result->get_error_message()
			);
		}

		// Pure transformation: format response.
		return $this->formatResponse( $term_id, $taxonomy );
	}

	/**
	 * Build update data array with sanitization.
	 *
	 * Pure function - sanitizes and transforms input into update data.
	 * Only includes fields that are provided in input.
	 *
	 * @param array<string, mixed> $input Input parameters.
	 * @return array<string, mixed> Sanitized update data.
	 */
	private function buildUpdateData( array $input ): array {
		$update_data = array();

		// Add name if provided.
		if ( isset( $input['name'] ) && '' !== $input['name'] ) {
			$update_data['name'] = sanitize_text_field( $input['name'] );
		}

		// Add slug if provided.
		if ( isset( $input['slug'] ) && '' !== $input['slug'] ) {
			$update_data['slug'] = sanitize_title( $input['slug'] );
		}

		// Add description if provided.
		if ( isset( $input['description'] ) ) {
			$update_data['description'] = sanitize_textarea_field( $input['description'] );
		}

		// Add parent if provided.
		if ( isset( $input['parent'] ) ) {
			$update_data['parent'] = (int) $input['parent'];
		}

		return $update_data;
	}

	/**
	 * Format the response after term update.
	 *
	 * @param int    $term_id  Updated term ID.
	 * @param string $taxonomy Taxonomy name.
	 * @return array<string, mixed> Response data.
	 */
	private function formatResponse( int $term_id, string $taxonomy ): array {
		$term = get_term( $term_id, $taxonomy );

		if ( is_wp_error( $term ) || null === $term ) {
			return array(
				'term_id' => $term_id,
				'name'    => '',
				'slug'    => '',
				'link'    => '',
				'updated' => true,
			);
		}

		return array(
			'term_id' => $term_id,
			'name'    => $term->name,
			'slug'    => $term->slug,
			'link'    => get_term_link( $term ),
			'updated' => true,
		);
	}
}
