<?php
/**
 * GetCacheStatus ability - retrieves WordPress object cache status and features.
 *
 * @package FAWpmcp\Abilities\Cache
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Cache;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Ability to get WordPress object cache status and supported features.
 *
 * Returns information about what cache features are available,
 * whether a persistent cache is active, and cache statistics if available.
 *
 * @package FAWpmcp\Abilities\Cache
 */
final class GetCacheStatus extends AbstractAbility {
	/**
	 * Get the unique ability name.
	 *
	 * @return string Ability name.
	 */
	public function getName(): string {
		return 'fa-wpmcp/get-cache-status';
	}

	/**
	 * Get the ability category.
	 *
	 * @return string Category name.
	 */
	public function getCategory(): string {
		return 'cache';
	}

	/**
	 * Get the human-readable label.
	 *
	 * @return string Ability label.
	 */
	public function getLabel(): string {
		return 'Get Cache Status';
	}

	/**
	 * Get the ability description.
	 *
	 * @return string Description.
	 */
	public function getDescription(): string {
		return 'Get WordPress object cache status including supported features, persistence status, and statistics.';
	}

	/**
	 * Get the input schema.
	 *
	 * @return array<string, mixed> JSON Schema array.
	 */
	public function getInputSchema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(),
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
				'persistent'       => array(
					'type'        => 'boolean',
					'description' => 'Whether the cache is persistent (survives page loads).',
				),
				'supports'         => array(
					'type'        => 'object',
					'description' => 'Cache feature support flags.',
					'properties'  => array(
						'add_multiple'    => array( 'type' => 'boolean' ),
						'set_multiple'    => array( 'type' => 'boolean' ),
						'get_multiple'    => array( 'type' => 'boolean' ),
						'delete_multiple' => array( 'type' => 'boolean' ),
						'flush_runtime'   => array( 'type' => 'boolean' ),
						'flush_group'     => array( 'type' => 'boolean' ),
					),
				),
				'global_groups'    => array(
					'type'        => 'array',
					'description' => 'List of global cache groups.',
					'items'       => array( 'type' => 'string' ),
				),
				'non_persistent_groups' => array(
					'type'        => 'array',
					'description' => 'List of non-persistent cache groups.',
					'items'       => array( 'type' => 'string' ),
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
	 * Get the operation type.
	 *
	 * @return string Operation type ('read' or 'write').
	 */
	public function getOperationType(): string {
		return 'read';
	}

	/**
	 * Execute the ability.
	 *
	 * @param array<string, mixed> $input Validated input data.
	 * @return array<string, mixed> Cache status information.
	 */
	public function doExecute( array $input ): array {
		global $wp_object_cache;

		// Check for persistent cache (external drop-in).
		$persistent = wp_using_ext_object_cache();

		// Check supported features using wp_cache_supports().
		$supports = array(
			'add_multiple'    => wp_cache_supports( 'add_multiple' ),
			'set_multiple'    => wp_cache_supports( 'set_multiple' ),
			'get_multiple'    => wp_cache_supports( 'get_multiple' ),
			'delete_multiple' => wp_cache_supports( 'delete_multiple' ),
			'flush_runtime'   => wp_cache_supports( 'flush_runtime' ),
			'flush_group'     => wp_cache_supports( 'flush_group' ),
		);

		// Get global groups if available.
		$global_groups = array();
		if ( isset( $wp_object_cache ) && isset( $wp_object_cache->global_groups ) ) {
			$global_groups = array_keys( (array) $wp_object_cache->global_groups );
		}

		// Get non-persistent groups if available.
		$non_persistent_groups = array();
		if ( isset( $wp_object_cache ) && isset( $wp_object_cache->no_mc_groups ) ) {
			$non_persistent_groups = (array) $wp_object_cache->no_mc_groups;
		}

		return array(
			'persistent'            => $persistent,
			'supports'              => $supports,
			'global_groups'         => $global_groups,
			'non_persistent_groups' => $non_persistent_groups,
		);
	}
}
