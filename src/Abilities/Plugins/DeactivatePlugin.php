<?php
declare(strict_types=1);
namespace FAWpmcp\Abilities\Plugins;
use FAWpmcp\Abilities\AbstractAbility;
final class DeactivatePlugin extends AbstractAbility {
	public function getName(): string {
		return 'fa-wpmcp/deactivate-plugin';
	}
	public function getCategory(): string {
		return 'plugins';
	}
	public function getLabel(): string {
		return 'Deactivate Plugin';
	}
	public function getDescription(): string {
		return 'Deactivate a WordPress plugin.';
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
				'plugin'      => array( 'type' => 'string' ),
				'deactivated' => array( 'type' => 'boolean' ),
			),
		);
	}
	public function getRequiredCapability(): string {
		return 'activate_plugins';
	}
	public function doExecute( array $input ): array {
		$plugin = $input['plugin'];
		deactivate_plugins( $plugin );
		return array(
			'plugin'      => $plugin,
			'deactivated' => true,
		);
	}
}
