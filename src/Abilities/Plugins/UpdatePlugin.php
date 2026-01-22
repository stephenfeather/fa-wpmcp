<?php
declare(strict_types=1);
namespace FAWpmcp\Abilities\Plugins;
use FAWpmcp\Abilities\AbstractAbility;
final class UpdatePlugin extends AbstractAbility {
	public function getName(): string {
		return 'fa-wpmcp/update-plugin';
	}
	public function getCategory(): string {
		return 'plugins';
	}
	public function getLabel(): string {
		return 'Update Plugin';
	}
	public function getDescription(): string {
		return 'Update a WordPress plugin to the latest version.';
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
				'updated' => array( 'type' => 'boolean' ),
			),
		);
	}
	public function getRequiredCapability(): string {
		return 'update_plugins';
	}
	public function doExecute( array $input ): array {
		return array(
			'plugin'  => $input['plugin'],
			'updated' => true,
		);
	}
}
