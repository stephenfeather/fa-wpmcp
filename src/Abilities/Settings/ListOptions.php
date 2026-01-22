<?php
/**
 * ListOptions ability - lists WordPress options.
 *
 * @package FAWpmcp\Abilities\Settings
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Settings;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Settings\OptionAccessPolicy;

/**
 * Ability to list WordPress options.
 *
 * Queries the options table with filtering and pagination support.
 *
 * @package FAWpmcp\Abilities\Settings
 */
final class ListOptions extends AbstractAbility {
	/**
	 * Get the unique ability name.
	 *
	 * @return string Ability name.
	 */
	public function getName(): string {
		return 'fa-wpmcp/list-options';
	}

	/**
	 * Get the ability category.
	 *
	 * @return string Category name.
	 */
	public function getCategory(): string {
		return 'settings';
	}

	/**
	 * Get the human-readable label.
	 *
	 * @return string Ability label.
	 */
	public function getLabel(): string {
		return 'List Options';
	}

	/**
	 * Get the ability description.
	 *
	 * @return string Description.
	 */
	public function getDescription(): string {
		return 'List WordPress options with optional search filtering and pagination.';
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
				'search' => array(
					'type'        => 'string',
					'description' => 'Search term to filter option names.',
				),
				'limit'  => array(
					'type'        => 'integer',
					'description' => 'Maximum number of options to return.',
					'default'     => 100,
					'minimum'     => 1,
					'maximum'     => 1000,
				),
				'offset' => array(
					'type'        => 'integer',
					'description' => 'Number of options to skip.',
					'default'     => 0,
					'minimum'     => 0,
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
				'options' => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'option_name'  => array( 'type' => 'string' ),
							'option_value' => array(),
						),
					),
				),
				'total'   => array(
					'type'        => 'integer',
					'description' => 'Total number of options matching the search.',
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
		return 'manage_options';
	}

	/**
	 * Execute the ability.
	 *
	 * @param array<string, mixed> $input Validated input data.
	 * @return array<string, mixed> Options list.
	 */
	public function doExecute( array $input ): array {
		global $wpdb;

		$search = $input['search'] ?? null;
		$limit  = $input['limit'] ?? 100;
		$offset = $input['offset'] ?? 0;

		$where_clauses = array();
		$args          = array();

		$allowed_options   = OptionAccessPolicy::getAllowedOptions();
		$protected_options = OptionAccessPolicy::getProtectedOptions();

		if ( ! empty( $allowed_options ) ) {
			$placeholders    = implode( ',', array_fill( 0, count( $allowed_options ), '%s' ) );
			$where_clauses[] = "option_name IN ({$placeholders})";
			$args            = array_merge( $args, $allowed_options );
		} elseif ( ! empty( $protected_options ) ) {
			$placeholders    = implode( ',', array_fill( 0, count( $protected_options ), '%s' ) );
			$where_clauses[] = "option_name NOT IN ({$placeholders})";
			$args            = array_merge( $args, $protected_options );
		}

		if ( $search ) {
			$where_clauses[] = 'option_name LIKE %s';
			$args[]          = '%' . $wpdb->esc_like( $search ) . '%';
		}

		$where = $where_clauses ? 'WHERE ' . implode( ' AND ', $where_clauses ) : '';

		// Prepare and execute query for options.
		$query       = "SELECT option_name, option_value FROM {$wpdb->options} {$where} ORDER BY option_name ASC LIMIT %d OFFSET %d";
		$query_args  = array_merge( $args, array( $limit, $offset ) );
		$sql         = $wpdb->prepare( $query, ...$query_args ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results     = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// Get total count.
		$count_query = "SELECT COUNT(*) FROM {$wpdb->options} {$where}";
		if ( ! empty( $args ) ) {
			$count_sql = $wpdb->prepare( $count_query, ...$args ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		} else {
			$count_sql = $count_query;
		}
		$total = (int) $wpdb->get_var( $count_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// Format results.
		$options = array_map(
			function ( $row ) {
				return array(
					'option_name'  => $row->option_name,
					'option_value' => maybe_unserialize( $row->option_value ),
				);
			},
			$results
		);

		return array(
			'options' => $options,
			'total'   => $total,
		);
	}
}
