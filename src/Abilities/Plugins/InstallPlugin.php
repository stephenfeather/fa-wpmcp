<?php
declare(strict_types=1);
namespace FAWpmcp\Abilities\Plugins;
use FAWpmcp\Abilities\AbstractAbility;
final class InstallPlugin extends AbstractAbility {
	public function getName(): string {
		return 'fa-wpmcp/install-plugin';
	}
	public function getCategory(): string {
		return 'plugins';
	}
	public function getLabel(): string {
		return 'Install Plugin';
	}
	public function getDescription(): string {
		return 'Install a WordPress plugin from WordPress.org or zip URL.';
	}
	public function getOperationType(): string {
		return 'write';
	}
	public function getInputSchema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'slug' => array(
					'type'        => 'string',
					'description' => 'Plugin slug from WordPress.org.',
				),
			),
			'required'   => array( 'slug' ),
		);
	}
	public function getOutputSchema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'slug'      => array( 'type' => 'string' ),
				'installed' => array( 'type' => 'boolean' ),
			),
		);
	}
	public function getRequiredCapability(): string {
		return 'install_plugins';
	}
	public function doExecute( array $input ): array {
		throw new \RuntimeException(
			'Plugin installation is not yet implemented. This ability requires WordPress Plugin_Upgrader integration.'
		);
	}
}
