<?php
/**
 * DeleteTransient ability - deletes transient(s).
 *
 * @package FAWpmcp\Abilities\Transients
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Transients;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Ability to delete WordPress transient(s).
 *
 * Supports deleting a single transient by key, all transients, or only expired transients.
 * Also supports network (site) transients for multisite.
 *
 * @package FAWpmcp\Abilities\Transients
 */
final class DeleteTransient extends AbstractAbility {
	/**
	 * Get the unique ability name.
	 *
	 * @return string Ability name.
	 */
	public function getName(): string {
		return 'fa-wpmcp/delete-transient';
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
		return 'Delete Transient';
	}

	/**
	 * Get the ability description.
	 *
	 * @return string Description.
	 */
	public function getDescription(): string {
		return 'Delete WordPress transient(s). Can delete a single transient by key, all transients, or only expired transients.';
	}

	/**
	 * Get the operation type.
	 *
	 * @return string Operation type.
	 */
	public function getOperationType(): string {
		return 'delete';
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
				'key'     => array(
					'type'        => 'string',
					'description' => 'The transient key to delete. Required unless "all" or "expired" is true.',
				),
				'all'     => array(
					'type'        => 'boolean',
					'description' => 'Delete all transients. Default: false.',
					'default'     => false,
				),
				'expired' => array(
					'type'        => 'boolean',
					'description' => 'Delete only expired transients. Default: false.',
					'default'     => false,
				),
				'network' => array(
					'type'        => 'boolean',
					'description' => 'Whether to delete network (site) transients for multisite. Default: false.',
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
				'deleted_count' => array(
					'type'        => 'integer',
					'description' => 'Number of transients deleted.',
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
	 * Get ability annotations.
	 *
	 * Marks this ability as destructive but idempotent.
	 *
	 * @return array<string, mixed> Annotations array.
	 */
	public function getAnnotations(): array {
		$annotations                = parent::getAnnotations();
		$annotations['readonly']    = false;
		$annotations['destructive'] = true;
		$annotations['idempotent']  = true;
		return $annotations;
	}

	/**
	 * Execute the ability.
	 *
	 * @param array<string, mixed> $input Validated input data.
	 * @return array<string, mixed> Deletion result.
	 * @throws \InvalidArgumentException If no action is specified.
	 */
	public function doExecute( array $input ): array {
		$key     = $input['key'] ?? '';
		$all     = ! empty( $input['all'] );
		$expired = ! empty( $input['expired'] );
		$network = ! empty( $input['network'] );

		// Validate that at least one action is specified.
		if ( empty( $key ) && ! $all && ! $expired ) {
			throw new \InvalidArgumentException( 'Must specify key, all, or expired parameter' );
		}

		// Handle single key deletion.
		if ( ! empty( $key ) ) {
			return $this->deleteSingleTransient( $key, $network );
		}

		// Handle bulk deletion.
		if ( $expired ) {
			return $this->deleteExpiredTransients( $network );
		}

		if ( $all ) {
			return $this->deleteAllTransients( $network );
		}

		return array( 'deleted_count' => 0 );
	}

	/**
	 * Delete a single transient by key.
	 *
	 * @param string $key     Transient key.
	 * @param bool   $network Whether to delete network transient.
	 * @return array<string, int> Result with deleted_count.
	 */
	private function deleteSingleTransient( string $key, bool $network ): array {
		if ( $network ) {
			$deleted = delete_site_transient( $key );
		} else {
			$deleted = delete_transient( $key );
		}

		return array(
			'deleted_count' => $deleted ? 1 : 0,
		);
	}

	/**
	 * Delete all transients.
	 *
	 * @param bool $network Whether to delete network transients.
	 * @return array<string, int> Result with deleted_count.
	 */
	private function deleteAllTransients( bool $network ): array {
		global $wpdb;

		if ( $network ) {
			$table        = $wpdb->sitemeta;
			$name_column  = 'meta_key';
			$prefix       = '_site_transient_';
		} else {
			$table        = $wpdb->options;
			$name_column  = 'option_name';
			$prefix       = '_transient_';
		}

		// Delete all transients (both values and timeouts).
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE {$name_column} LIKE %s",
				$prefix . '%'
			)
		);

		return array(
			'deleted_count' => (int) $deleted,
		);
	}

	/**
	 * Delete only expired transients.
	 *
	 * @param bool $network Whether to delete network transients.
	 * @return array<string, int> Result with deleted_count.
	 */
	private function deleteExpiredTransients( bool $network ): array {
		global $wpdb;

		$time = time();

		if ( $network ) {
			$table          = $wpdb->sitemeta;
			$name_column    = 'meta_key';
			$value_column   = 'meta_value';
			$timeout_prefix = '_site_transient_timeout_';
			$value_prefix   = '_site_transient_';
		} else {
			$table          = $wpdb->options;
			$name_column    = 'option_name';
			$value_column   = 'option_value';
			$timeout_prefix = '_transient_timeout_';
			$value_prefix   = '_transient_';
		}

		// Find expired transient timeout keys.
		$expired_timeouts = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT {$name_column} FROM {$table}
				WHERE {$name_column} LIKE %s
				AND CAST({$value_column} AS UNSIGNED) < %d
				AND CAST({$value_column} AS UNSIGNED) > 0",
				$timeout_prefix . '%',
				$time
			)
		);

		if ( empty( $expired_timeouts ) ) {
			return array( 'deleted_count' => 0 );
		}

		$deleted_count = 0;

		// Delete each expired transient and its timeout.
		foreach ( $expired_timeouts as $timeout_key ) {
			$transient_name = str_replace( $timeout_prefix, '', $timeout_key );
			$value_key      = $value_prefix . $transient_name;

			// Delete the value.
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$table} WHERE {$name_column} = %s",
					$value_key
				)
			);

			// Delete the timeout.
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$table} WHERE {$name_column} = %s",
					$timeout_key
				)
			);

			$deleted_count++;
		}

		return array(
			'deleted_count' => $deleted_count,
		);
	}
}
