<?php
declare(strict_types=1);
namespace FAWpmcp\Abilities\Plugins;
use FAWpmcp\Abilities\AbstractAbility;
final class ActivatePlugin extends AbstractAbility {
	public function getName(): string {
		return 'fa-wpmcp/activate-plugin';
	}
	public function getCategory(): string {
		return 'plugins';
	}
	public function getLabel(): string {
		return 'Activate Plugin';
	}
	public function getDescription(): string {
		return 'Activate a WordPress plugin.';
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
				'plugin'    => array( 'type' => 'string' ),
				'activated' => array( 'type' => 'boolean' ),
			),
		);
	}
	public function getRequiredCapability(): string {
		return 'activate_plugins';
	}
	public function doExecute( array $input ): array {
		$plugin = $input['plugin'];
		$result = activate_plugin( $plugin );
		return array(
			'plugin'    => $plugin,
			'activated' => null === $result,
		);
	}
}
