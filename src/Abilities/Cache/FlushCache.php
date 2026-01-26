<?php
/**
 * FlushCache ability - flushes the WordPress object cache.
 *
 * @package FAWpmcp\Abilities\Cache
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Cache;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\CacheOperationException;

/**
 * Ability to flush the WordPress object cache.
 *
 * Can flush the entire cache or a specific cache group (if supported).
 *
 * @package FAWpmcp\Abilities\Cache
 */
final class FlushCache extends AbstractAbility {

	/**
	 * Get the unique ability name.
	 *
	 * @return string Ability name.
	 */
	public function getName(): string {
		return 'fa-wpmcp/flush-cache';
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
		return 'Flush Cache';
	}

	/**
	 * Get the ability description.
	 *
	 * @return string Description.
	 */
	public function getDescription(): string {
		return 'Flush the WordPress object cache. Optionally flush only a specific cache group if supported.';
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
				'group' => array(
					'type'        => 'string',
					'description' => 'Optional cache group to flush. If not provided, flushes entire cache.',
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
				'flushed' => array(
					'type'        => 'boolean',
					'description' => 'Whether the cache was successfully flushed.',
				),
				'group'   => array(
					'type'        => 'string',
					'description' => 'The group that was flushed, or "all" for entire cache.',
				),
				'message' => array(
					'type'        => 'string',
					'description' => 'A message describing the result.',
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
		return 'write';
	}

	/**
	 * Get ability annotations.
	 *
	 * Marks this ability as potentially impactful but idempotent.
	 *
	 * @return array<string, mixed> Annotations array.
	 */
	public function getAnnotations(): array {
		$annotations               = parent::getAnnotations();
		$annotations['mcp.public'] = true;
		$annotations['idempotent'] = true;
		return $annotations;
	}

	/**
	 * Execute the ability.
	 *
	 * @param array<string, mixed> $input Validated input data.
	 * @return array<string, mixed> Flush result.
	 * @throws CacheOperationException If flush operation fails.
	 */
	public function doExecute( array $input ): array {
		$group = $input['group'] ?? null;

		if ( ! empty( $group ) ) {
			// Flush specific group.
			if ( ! wp_cache_supports( 'flush_group' ) ) {
				throw new CacheOperationException(
					'Cache implementation does not support group flushing. Use flush without a group parameter.'
				);
			}

			$result = wp_cache_flush_group( $group );

			if ( false === $result ) {
				throw new CacheOperationException( "Failed to flush cache group '{$group}'." );
			}

			return array(
				'flushed' => true,
				'group'   => $group,
				'message' => "Cache group '{$group}' flushed successfully.",
			);
		}

		// Flush entire cache.
		$result = wp_cache_flush();

		if ( false === $result ) {
			throw new CacheOperationException( 'Failed to flush cache.' );
		}

		return array(
			'flushed' => true,
			'group'   => 'all',
			'message' => 'Entire cache flushed successfully.',
		);
	}
}
