<?php
/**
 * ListTerms ability - retrieves terms from a taxonomy.
 *
 * @package FAWpmcp\Abilities\Taxonomies
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Taxonomies;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Ability to list WordPress taxonomy terms with filtering.
 *
 * Supports:
 * - Multiple taxonomies (category, post_tag, custom)
 * - Hierarchical and flat taxonomies
 * - Search by name
 * - Hide empty terms option
 * - Pagination
 *
 * @package FAWpmcp\Abilities\Taxonomies
 */
final class ListTerms extends AbstractAbility {

	/**
	 * Maximum terms per page limit.
	 *
	 * @var int
	 */
	private const MAX_PER_PAGE = 100;

	/**
	 * Default terms per page.
	 *
	 * @var int
	 */
	private const DEFAULT_PER_PAGE = 10;

	/**
	 * Get the unique ability name.
	 *
	 * @return string Ability name.
	 */
	public function getName(): string {
		return 'fa-wpmcp/list-terms';
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
		return 'List Terms';
	}

	/**
	 * Get the ability description.
	 *
	 * @return string Description.
	 */
	public function getDescription(): string {
		return 'Retrieve terms from WordPress taxonomies (categories, tags, or custom taxonomies) with optional filtering by name, parent, and visibility. Supports hierarchical relationships.';
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
				'taxonomy'   => array(
					'type'        => 'string',
					'description' => 'Taxonomy name (e.g., category, post_tag, or custom taxonomy slug).',
					'default'     => 'category',
				),
				'page'       => array(
					'type'        => 'integer',
					'description' => 'Page number for pagination.',
					'minimum'     => 1,
					'default'     => 1,
				),
				'per_page'   => array(
					'type'        => 'integer',
					'description' => 'Number of terms per page (max 100).',
					'minimum'     => 1,
					'maximum'     => self::MAX_PER_PAGE,
					'default'     => self::DEFAULT_PER_PAGE,
				),
				'hide_empty' => array(
					'type'        => 'boolean',
					'description' => 'Hide terms with no posts assigned.',
					'default'     => false,
				),
				'parent'     => array(
					'type'        => 'integer',
					'description' => 'Filter by parent term ID (0 for root terms).',
					'minimum'     => 0,
				),
				'search'     => array(
					'type'        => 'string',
					'description' => 'Search terms by name.',
				),
				'orderby'    => array(
					'type'        => 'string',
					'description' => 'Field to order by.',
					'enum'        => array( 'name', 'count', 'term_id', 'slug' ),
					'default'     => 'name',
				),
				'order'      => array(
					'type'        => 'string',
					'description' => 'Sort order.',
					'enum'        => array( 'ASC', 'DESC' ),
					'default'     => 'ASC',
				),
			),
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
				'terms'        => array(
					'type'  => 'array',
					'items' => array(
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
						),
					),
				),
				'total'        => array(
					'type'        => 'integer',
					'description' => 'Total number of terms matching query.',
				),
				'pages'        => array(
					'type'        => 'integer',
					'description' => 'Total number of pages.',
				),
				'current_page' => array(
					'type'        => 'integer',
					'description' => 'Current page number.',
				),
				'per_page'     => array(
					'type'        => 'integer',
					'description' => 'Number of terms per page.',
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
	 * @return array<string, mixed> Terms list with pagination.
	 */
	public function doExecute( array $input ): array {
		// Pure transformation: build query args.
		$query_args = $this->buildQueryArgs( $input );

		// Side effect: query terms.
		$terms = get_terms( $query_args );

		// Handle errors.
		if ( is_wp_error( $terms ) ) {
			return array(
				'terms'        => array(),
				'total'        => 0,
				'pages'        => 0,
				'current_page' => 1,
				'per_page'     => $input['per_page'] ?? self::DEFAULT_PER_PAGE,
			);
		}

		// Get total count for pagination.
		$count_args          = $query_args;
		$count_args['count'] = true;
		$total               = (int) get_terms( $count_args );

		// Pure transformation: format results.
		return $this->formatResults( $terms, $total, $input );
	}

	/**
	 * Build get_terms arguments from input.
	 *
	 * Pure function - transforms input into query args without side effects.
	 *
	 * @param array<string, mixed> $input Input parameters.
	 * @return array<string, mixed> get_terms arguments.
	 */
	private function buildQueryArgs( array $input ): array {
		$per_page = isset( $input['per_page'] )
			? min( (int) $input['per_page'], self::MAX_PER_PAGE )
			: self::DEFAULT_PER_PAGE;

		$page = $input['page'] ?? 1;

		$args = array(
			'taxonomy'   => $input['taxonomy'] ?? 'category',
			'hide_empty' => $input['hide_empty'] ?? false,
			'number'     => $per_page,
			'offset'     => ( $page - 1 ) * $per_page,
			'orderby'    => $input['orderby'] ?? 'name',
			'order'      => $input['order'] ?? 'ASC',
		);

		// Add parent filter.
		if ( isset( $input['parent'] ) ) {
			$args['parent'] = (int) $input['parent'];
		}

		// Add search filter.
		if ( isset( $input['search'] ) && '' !== $input['search'] ) {
			$args['search'] = $input['search'];
		}

		return $args;
	}

	/**
	 * Format terms results into output array.
	 *
	 * Pure function - transforms term objects without side effects.
	 *
	 * @param array<\WP_Term>      $terms Terms array.
	 * @param int                  $total Total count.
	 * @param array<string, mixed> $input Original input parameters.
	 * @return array<string, mixed> Formatted results.
	 */
	private function formatResults( array $terms, int $total, array $input ): array {
		$formatted = array();

		foreach ( $terms as $term ) {
			$formatted[] = $this->formatTerm( $term );
		}

		$per_page = isset( $input['per_page'] )
			? min( (int) $input['per_page'], self::MAX_PER_PAGE )
			: self::DEFAULT_PER_PAGE;

		$pages = $per_page > 0 ? (int) ceil( $total / $per_page ) : 0;

		return array(
			'terms'        => $formatted,
			'total'        => $total,
			'pages'        => $pages,
			'current_page' => $input['page'] ?? 1,
			'per_page'     => $per_page,
		);
	}

	/**
	 * Format a single term for output.
	 *
	 * Pure function - transforms term object into formatted array.
	 *
	 * @param \WP_Term $term Term object.
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
		);
	}
}
