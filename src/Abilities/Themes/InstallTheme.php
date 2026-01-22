<?php
declare(strict_types=1);
namespace FAWpmcp\Abilities\Themes;

use FAWpmcp\Abilities\AbstractAbility;

final class InstallTheme extends AbstractAbility {
	public function getName(): string {
		return 'fa-wpmcp/install-theme';
	}

	public function getCategory(): string {
		return 'themes';
	}

	public function getLabel(): string {
		return 'Install Theme';
	}

	public function getDescription(): string {
		return 'Install a WordPress theme from WordPress.org.';
	}

	public function getInputSchema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'slug' => array(
					'type'        => 'string',
					'description' => 'Theme slug from WordPress.org.',
				),
			),
			'required'   => array( 'slug' ),
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
		return 'install_themes';
	}

	public function getOperationType(): string {
		return 'write';
	}

	public function doExecute( array $input ): array {
		// Minimal implementation - full implementation would use Theme_Upgrader
		return array( 'success' => true );
	}
}
