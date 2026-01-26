<?php
/**
 * ListTransients ability - lists transients with optional filtering.
 *
 * @package FAWpmcp\Abilities\Transients
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Transients;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Ability to list WordPress transients with optional filtering.
 *
 * Queries the database directly for transients stored with the _transient_ prefix.
 * Supports search and exclude patterns for filtering results.
 *
 * @package FAWpmcp\Abilities\Transients
 */
final class ListTransients extends AbstractAbility {
	/**
	 * Get the unique ability name.
	 *
	 * @return string Ability name.
	 */
	public function getName(): string {
		return 'fa-wpmcp/list-transients';
	}

	/**
	 * Get the ability category.
	 *
	 * @return string Category name.
	 */
	public function getCategory(): string {
		return 'transients';
	}

	/**
	 * Get the human-readable label.
	 *
	 * @return string Ability label.
	 */
	public function getLabel(): string {
		return 'List Transients';
	}

	/**
	 * Get the ability description.
	 *
	 * @return string Description.
	 */
	public function getDescription(): string {
		return 'List WordPress transients with optional search and exclude patterns. Returns transient names, values, and expiration times.';
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
				'search'  => array(
					'type'        => 'string',
					'description' => 'Search pattern to filter transient names (uses SQL LIKE).',
				),
				'exclude' => array(
					'type'        => 'string',
					'description' => 'Exclude pattern to filter out transient names (uses SQL NOT LIKE).',
				),
				'network' => array(
					'type'        => 'boolean',
					'description' => 'Whether to list network (site) transients for multisite. Default: false.',
					'default'     => false,
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
				'transients' => array(
					'type'        => 'array',
					'description' => 'List of transients.',
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'name'       => array( 'type' => 'string' ),
							'value'      => array( 'description' => 'The transient value.' ),
							'expiration' => array(
								'type'        => 'integer',
								'description' => 'Unix timestamp when transient expires, or 0 for no expiration.',
							),
						),
					),
				),
				'total'      => array(
					'type'        => 'integer',
					'description' => 'Total number of transients found.',
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
	 * @return array<string, mixed> List of transients.
	 */
	public function doExecute( array $input ): array {
		global $wpdb;

		$search  = $input['search'] ?? '';
		$exclude = $input['exclude'] ?? '';
		$network = ! empty( $input['network'] );

		// Determine table and prefix based on network flag.
		if ( $network ) {
			$table          = $wpdb->sitemeta;
			$prefix         = '_site_transient_';
			$timeout_prefix = '_site_transient_timeout_';
			$name_column    = 'meta_key';
			$value_column   = 'meta_value';
		} else {
			$table          = $wpdb->options;
			$prefix         = '_transient_';
			$timeout_prefix = '_transient_timeout_';
			$name_column    = 'option_name';
			$value_column   = 'option_value';
		}

		// Build the query.
		$query = "SELECT {$name_column}, {$value_column} FROM {$table} WHERE {$name_column} LIKE %s";
		$args  = array( $prefix . '%' );

		// Exclude timeout entries from main results.
		$query .= " AND {$name_column} NOT LIKE %s";
		$args[] = $timeout_prefix . '%';

		// Apply search filter.
		if ( ! empty( $search ) ) {
			$query .= " AND {$name_column} LIKE %s";
			$args[] = $prefix . $wpdb->esc_like( $search ) . '%';
		}

		// Apply exclude filter.
		if ( ! empty( $exclude ) ) {
			$query .= " AND {$name_column} NOT LIKE %s";
			$args[] = $prefix . $wpdb->esc_like( $exclude ) . '%';
		}

		// Execute query.
		$results = $wpdb->get_results( $wpdb->prepare( $query, $args ) );

		// Process results.
		$transients = array();
		$timeouts   = $this->getTimeouts( $wpdb, $table, $timeout_prefix, $name_column, $value_column );

		foreach ( $results as $row ) {
			$full_name = $row->$name_column;
			$name      = str_replace( $prefix, '', $full_name );
			$value     = maybe_unserialize( $row->$value_column );

			// Get expiration time.
			$timeout_key = $timeout_prefix . $name;
			$expiration  = isset( $timeouts[ $timeout_key ] ) ? (int) $timeouts[ $timeout_key ] : 0;

			$transients[] = array(
				'name'       => $name,
				'value'      => $value,
				'expiration' => $expiration,
			);
		}

		return array(
			'transients' => $transients,
			'total'      => count( $transients ),
		);
	}

	/**
	 * Get all timeout values for transients.
	 *
	 * @param object $wpdb          WordPress database object.
	 * @param string $table         Table name.
	 * @param string $timeout_prefix Timeout prefix.
	 * @param string $name_column   Name column.
	 * @param string $value_column  Value column.
	 * @return array<string, string> Timeout values keyed by timeout option name.
	 */
	private function getTimeouts( $wpdb, string $table, string $timeout_prefix, string $name_column, string $value_column ): array {
		$query   = "SELECT {$name_column}, {$value_column} FROM {$table} WHERE {$name_column} LIKE %s";
		$results = $wpdb->get_results( $wpdb->prepare( $query, array( $timeout_prefix . '%' ) ) );

		$timeouts = array();
		foreach ( $results as $row ) {
			$timeouts[ $row->$name_column ] = $row->$value_column;
		}

		return $timeouts;
	}
}
