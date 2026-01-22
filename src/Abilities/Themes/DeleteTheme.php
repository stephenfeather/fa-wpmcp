<?php
declare(strict_types=1);
namespace FAWpmcp\Abilities\Themes;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Delete Theme Ability (INTENTIONALLY UNIMPLEMENTED)
 *
 * This ability is marked as out-of-scope for the current release.
 * It will throw a RuntimeException when called, clearly indicating to API consumers
 * that this functionality is not yet available.
 *
 * @since 1.0.0-alpha-2
 * @see https://developer.wordpress.org/reference/functions/delete_theme/
 *
 * SECURITY CONSIDERATIONS FOR FUTURE IMPLEMENTATION:
 * - DESTRUCTIVE OPERATION: Must require explicit user confirmation
 * - Must prevent deletion of active theme
 * - Must prevent deletion of parent theme if child theme is active
 * - Should verify theme is not in use before deletion
 * - Requires filesystem write permission validation
 * - Must handle protected themes (blocked via filters)
 * - Should create backup before deletion (optional)
 * - Must log all deletion attempts for audit trail
 * - Should implement theme deletion blocklist (protect default themes)
 * - Must handle failed deletions gracefully (cleanup, rollback)
 * - Consider implementing "soft delete" with restore option
 *
 * IMPLEMENTATION REQUIREMENTS:
 * - Integration with WordPress delete_theme() function
 * - Proper validation of theme stylesheet name (prevent directory traversal)
 * - Support for multisite network-activated themes
 * - Handling of theme data cleanup (mods, options)
 * - Override getAnnotations() to set destructive=true, idempotent=false
 * - Must check for child themes before deleting parent theme
 */
final class DeleteTheme extends AbstractAbility {
	public function getName(): string {
		return 'fa-wpmcp/delete-theme';
	}

	public function getCategory(): string {
		return 'themes';
	}

	public function getLabel(): string {
		return 'Delete Theme';
	}

	public function getDescription(): string {
		return 'Delete a WordPress theme.';
	}

	public function getInputSchema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'stylesheet' => array(
					'type'        => 'string',
					'description' => 'Theme stylesheet name to delete.',
				),
			),
			'required'   => array( 'stylesheet' ),
		);
	}

	public function getOutputSchema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'success' => array( 'type' => 'boolean' ),
			),
		);
	}

	public function getRequiredCapability(): string {
		return 'delete_themes';
	}

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

	public function doExecute( array $input ): array {
		throw new \RuntimeException(
			'Theme deletion is not yet implemented. This ability requires WordPress delete_theme() integration.'
		);
	}
}
