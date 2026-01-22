<?php
declare(strict_types=1);
namespace FAWpmcp\Abilities\Plugins;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\PluginNotFoundException;

final class GetPlugin extends AbstractAbility {
	public function getName(): string {
		return 'fa-wpmcp/get-plugin';
	}

	public function getCategory(): string {
		return 'plugins';
	}

	public function getLabel(): string {
		return 'Get Plugin';
	}

	public function getDescription(): string {
		return 'Get details about a specific WordPress plugin.';
	}

	public function getInputSchema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'plugin' => array(
					'type'        => 'string',
					'description' => 'Plugin file path (e.g., plugin-dir/plugin-file.php).',
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
				'name'    => array( 'type' => 'string' ),
				'version' => array( 'type' => 'string' ),
				'active'  => array( 'type' => 'boolean' ),
			),
		);
	}

	public function getRequiredCapability(): string {
		return 'activate_plugins';
	}

	public function doExecute( array $input ): array {
		$plugin_file = $input['plugin'];
		$all_plugins = get_plugins();

		if ( ! isset( $all_plugins[ $plugin_file ] ) ) {
			throw new PluginNotFoundException( "Plugin '{$plugin_file}' not found." );
		}

		$plugin_data = $all_plugins[ $plugin_file ];

		return array(
			'plugin'  => $plugin_file,
			'name'    => $plugin_data['Name'],
			'version' => $plugin_data['Version'],
			'active'  => is_plugin_active( $plugin_file ),
		);
	}
}
