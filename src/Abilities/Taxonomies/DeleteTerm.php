<?php
/**
 * DeleteTerm ability - deletes a WordPress taxonomy term.
 *
 * @package FAWpmcp\Abilities\Taxonomies
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Taxonomies;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\TermDeletionException;
use FAWpmcp\Exceptions\TermNotFoundException;

/**
 * Ability to delete a WordPress taxonomy term.
 *
 * Note: WordPress terms do not support trash - deletion is always permanent.
 *
 * @package FAWpmcp\Abilities\Taxonomies
 */
final class DeleteTerm extends AbstractAbility {
	/**
	 * Get the unique ability name.
	 *
	 * @return string Ability name.
	 */
	public function getName(): string {
		return 'fa-wpmcp/delete-term';
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
		return 'Delete Term';
	}

	/**
	 * Get the ability description.
	 *
	 * @return string Description.
	 */
	public function getDescription(): string {
		return 'Delete a WordPress taxonomy term. Note: Terms are always permanently deleted (no trash support in WordPress).';
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
					'description' => 'The ID of the term to delete.',
					'minimum'     => 1,
				),
				'taxonomy' => array(
					'type'        => 'string',
					'description' => 'The taxonomy the term belongs to (e.g., category, post_tag, or custom taxonomy).',
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
				'term_id'  => array(
					'type'        => 'integer',
					'description' => 'The ID of the deleted term.',
				),
				'taxonomy' => array(
					'type'        => 'string',
					'description' => 'The taxonomy the term belonged to.',
				),
				'action'   => array(
					'type'        => 'string',
					'description' => 'The action performed (always "deleted" for terms).',
					'enum'        => array( 'deleted' ),
				),
				'success'  => array(
					'type'        => 'boolean',
					'description' => 'Whether the operation succeeded.',
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
	 * @return string Operation type ('read' or 'write').
	 */
	public function getOperationType(): string {
		return 'write';
	}

	/**
	 * Get ability annotations.
	 *
	 * Marks this ability as destructive and non-idempotent.
	 *
	 * @return array<string, mixed> Annotations array.
	 */
	public function getAnnotations(): array {
		$annotations                = parent::getAnnotations();
		$annotations['destructive'] = true;
		$annotations['idempotent']  = false;
		return $annotations;
	}

	/**
	 * Execute the ability.
	 *
	 * @param array<string, mixed> $input Validated input data.
	 * @return array<string, mixed> Deletion result.
	 * @throws TermNotFoundException If term does not exist.
	 * @throws TermDeletionException If deletion fails.
	 */
	public function doExecute( array $input ): array {
		$term_id  = (int) $input['term_id'];
		$taxonomy = (string) $input['taxonomy'];

		// Verify term exists.
		$term = get_term( $term_id, $taxonomy );
		if ( is_wp_error( $term ) || null === $term ) {
			throw new TermNotFoundException( "Term {$term_id} not found in taxonomy '{$taxonomy}'." );
		}

		// Delete the term (WordPress terms have no trash - always permanent).
		$result = wp_delete_term( $term_id, $taxonomy );

		if ( is_wp_error( $result ) ) {
			throw new TermDeletionException( "Failed to delete term {$term_id}: " . $result->get_error_message() );
		}

		if ( false === $result ) {
			throw new TermDeletionException( "Failed to delete term {$term_id}." );
		}

		return array(
			'term_id'  => $term_id,
			'taxonomy' => $taxonomy,
			'action'   => 'deleted',
			'success'  => true,
		);
	}
}
