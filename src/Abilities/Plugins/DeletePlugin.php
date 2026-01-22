<?php
declare(strict_types=1);
namespace FAWpmcp\Abilities\Plugins;
use FAWpmcp\Abilities\AbstractAbility;

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
	public function getName(): string {
		return 'fa-wpmcp/delete-plugin';
	}
	public function getCategory(): string {
		return 'plugins';
	}
	public function getLabel(): string {
		return 'Delete Plugin';
	}
	public function getDescription(): string {
		return 'Delete a WordPress plugin.';
	}
	public function getOperationType(): string {
		return 'write';
	}
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
	public function getOutputSchema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'plugin'  => array( 'type' => 'string' ),
				'deleted' => array( 'type' => 'boolean' ),
			),
		);
	}
	public function getRequiredCapability(): string {
		return 'delete_plugins';
	}
	public function doExecute( array $input ): array {
		throw new \RuntimeException(
			'Plugin deletion is not yet implemented. This ability requires WordPress delete_plugins() integration.'
		);
	}
}
