<?php
/**
 * GetTerm ability - retrieves a single term by ID.
 *
 * @package FAWpmcp\Abilities\Taxonomies
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Taxonomies;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\PostNotFoundException;

/**
 * Ability to retrieve a single WordPress taxonomy term by ID.
 *
 * Returns complete term data including:
 * - Basic term fields (name, slug, description)
 * - Taxonomy information
 * - Parent term (for hierarchical taxonomies)
 * - Post count
 * - Term link
 *
 * @package FAWpmcp\Abilities\Taxonomies
 */
final class GetTerm extends AbstractAbility {
	/**
	 * Get the unique ability name.
	 *
	 * @return string Ability name.
	 */
	public function getName(): string {
		return 'fa-wpmcp/get-term';
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
		return 'Get Term';
	}

	/**
	 * Get the ability description.
	 *
	 * @return string Description.
	 */
	public function getDescription(): string {
		return 'Retrieve a single WordPress taxonomy term by ID with full details including name, slug, description, parent (for hierarchical), count, and link.';
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
				'term_id'  => array(
					'type'        => 'integer',
					'description' => 'The ID of the term to retrieve.',
					'minimum'     => 1,
				),
				'taxonomy' => array(
					'type'        => 'string',
					'description' => 'Optional taxonomy name to validate (category, post_tag, or custom taxonomy).',
				),
			),
			'required'   => array( 'term_id' ),
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
				'term' => array(
					'type'       => 'object',
					'properties' => array(
						'term_id'     => array( 'type' => 'integer' ),
						'name'        => array( 'type' => 'string' ),
						'slug'        => array( 'type' => 'string' ),
						'description' => array( 'type' => 'string' ),
						'parent'      => array( 'type' => 'integer' ),
						'count'       => array( 'type' => 'integer' ),
						'taxonomy'    => array( 'type' => 'string' ),
						'link'        => array( 'type' => 'string' ),
						'meta'        => array( 'type' => 'object' ),
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
	public function getRequiredCapability(): string {
		return 'read';
	}

	/**
	 * Execute the ability.
	 *
	 * @param array<string, mixed> $input Validated input data.
	 * @return array<string, mixed> Term data.
	 * @throws PostNotFoundException If term not found.
	 */
	public function doExecute( array $input ): array {
		$term_id  = (int) $input['term_id'];
		$taxonomy = $input['taxonomy'] ?? '';

		// Side effect: fetch term from database.
		$term = get_term( $term_id, $taxonomy );

		if ( is_wp_error( $term ) || null === $term ) {
			throw new PostNotFoundException( 'Term not found' );
		}

		// Pure transformation: format term data.
		return array(
			'term' => $this->formatTerm( $term ),
		);
	}

	/**
	 * Format a WP_Term object into output array.
	 *
	 * Pure function - transforms term data without side effects.
	 *
	 * @param \WP_Term $term The term object.
	 * @return array<string, mixed> Formatted term data.
	 */
	private function formatTerm( \WP_Term $term ): array {
		return array(
			'term_id'     => $term->term_id,
			'name'        => $term->name,
			'slug'        => $term->slug,
			'description' => $term->description,
			'parent'      => $term->parent,
			'count'       => $term->count,
			'taxonomy'    => $term->taxonomy,
			'link'        => get_term_link( $term ),
			'meta'        => $this->getTermMeta( $term->term_id ),
		);
	}

	/**
	 * Get term meta, filtering out internal keys.
	 *
	 * Pure function - filters and formats meta data.
	 *
	 * @param int $term_id Term ID.
	 * @return array<string, mixed> Filtered meta data.
	 */
	private function getTermMeta( int $term_id ): array {
		$meta = get_term_meta( $term_id );

		if ( ! is_array( $meta ) ) {
			return array();
		}

		// Filter out internal WordPress meta keys.
		$filtered = array();
		foreach ( $meta as $key => $value ) {
			// Skip internal keys that start with underscore.
			if ( strpos( $key, '_' ) === 0 ) {
				continue;
			}
			$filtered[ $key ] = is_array( $value ) && 1 === count( $value ) ? $value[0] : $value;
		}

		return $filtered;
	}
}
