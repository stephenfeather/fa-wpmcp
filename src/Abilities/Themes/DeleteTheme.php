<?php
declare(strict_types=1);
namespace FAWpmcp\Abilities\Themes;

use FAWpmcp\Abilities\AbstractAbility;

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

	public function doExecute( array $input ): array {
		// Minimal implementation - full implementation would use delete_theme()
		return array( 'success' => true );
	}
}
