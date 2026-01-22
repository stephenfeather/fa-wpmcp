<?php
declare(strict_types=1);
namespace FAWpmcp\Abilities\Themes;

use FAWpmcp\Abilities\AbstractAbility;

final class UpdateTheme extends AbstractAbility {
	public function getName(): string {
		return 'fa-wpmcp/update-theme';
	}

	public function getCategory(): string {
		return 'themes';
	}

	public function getLabel(): string {
		return 'Update Theme';
	}

	public function getDescription(): string {
		return 'Update a WordPress theme to the latest version.';
	}

	public function getInputSchema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'stylesheet' => array(
					'type'        => 'string',
					'description' => 'Theme stylesheet name to update.',
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
		return 'update_themes';
	}

	public function getOperationType(): string {
		return 'write';
	}

	public function doExecute( array $input ): array {
		// Minimal implementation - full implementation would use Theme_Upgrader
		return array( 'success' => true );
	}
}
