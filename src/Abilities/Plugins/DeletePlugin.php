<?php
/**
 * Delete Plugin ability for WordPress MCP.
 *
 * @package FAWpmcp\Abilities\Plugins
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Plugins;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\PluginDeletionException;

/**
 * Delete Plugin Ability (INTENTIONALLY UNIMPLEMENTED)
 *
 * This ability is marked as out-of-scope for the current release.
 * It will throw a RuntimeException when called, clearly indicating to API consumers
 * that this functionality is not yet available.
 *
 * @since 1.0.0-alpha-2
 * @see https://developer.wordpress.org/reference/functions/delete_plugins/
 *
 * SECURITY CONSIDERATIONS FOR FUTURE IMPLEMENTATION:
 * - DESTRUCTIVE OPERATION: Must require explicit user confirmation
 * - Must prevent deletion of critical plugins (including self)
 * - Should verify plugin is deactivated before deletion
 * - Requires filesystem write permission validation
 * - Must handle protected plugins (blocked via filters)
 * - Should create backup before deletion (optional)
 * - Must log all deletion attempts for audit trail
 * - Should implement plugin deletion blocklist (protect critical plugins)
 * - Must handle failed deletions gracefully (cleanup, rollback)
 * - Consider implementing "soft delete" with restore option
 *
 * IMPLEMENTATION REQUIREMENTS:
 * - Integration with WordPress delete_plugins() function
 * - Proper validation of plugin file path (prevent directory traversal)
 * - Support for multisite network-activated plugins
 * - Handling of plugin data cleanup (options, tables)
 * - Override getAnnotations() to set destructive=true, idempotent=false
 */
final class DeletePlugin extends AbstractAbility {

	/**
	 * Returns the ability identifier.
	 *
	 * @return string
	 */
	public function getName(): string {
		return 'fa-wpmcp/delete-plugin';
	}

	/**
	 * Returns the ability category.
	 *
	 * @return string
	 */
	public function getCategory(): string {
		return 'plugins';
	}

	/**
	 * Returns the display label.
	 *
	 * @return string
	 */
	public function getLabel(): string {
		return 'Delete Plugin';
	}

	/**
	 * Returns the ability description.
	 *
	 * @return string
	 */
	public function getDescription(): string {
		return 'Delete a WordPress plugin.';
	}

	/**
	 * Returns the operation type.
	 *
	 * @return string
	 */
	public function getOperationType(): string {
		return 'write';
	}

	/**
	 * Returns the JSON Schema for input validation.
	 *
	 * @return array
	 */
	public function getInputSchema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'plugin' => array(
					'type'        => 'string',
					'description' => 'Plugin file path.',
				),
			),
			'required'   => array( 'plugin' ),
		);
	}

	/**
	 * Returns the JSON Schema for output.
	 *
	 * @return array
	 */
	public function getOutputSchema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'plugin'  => array( 'type' => 'string' ),
				'deleted' => array( 'type' => 'boolean' ),
			),
		);
	}

	/**
	 * Returns the WordPress capability required.
	 *
	 * @return string
	 */
	public function getRequiredCapability(): string {
		return 'delete_plugins';
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
	 * Executes the ability.
	 *
	 * @param array $input Input parameters.
	 * @return array
	 * @throws PluginDeletionException Always, as this ability is not yet implemented.
	 */
	public function doExecute( array $input ): array {
		throw new PluginDeletionException(
			'Plugin deletion is not yet implemented. This ability requires WordPress delete_plugins() integration.'
		);
	}
}
