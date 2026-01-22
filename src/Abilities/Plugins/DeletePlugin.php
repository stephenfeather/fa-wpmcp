<?php
declare(strict_types=1);
namespace FAWpmcp\Abilities\Plugins;
use FAWpmcp\Abilities\AbstractAbility;
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
