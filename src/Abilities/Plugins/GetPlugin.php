<?php
/**
 * Get Plugin ability - retrieves details about a specific WordPress plugin.
 *
 * @package FAWpmcp\Abilities\Plugins
 */

declare(strict_types=1);
namespace FAWpmcp\Abilities\Plugins;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\PluginNotFoundException;

/**
 * Get Plugin ability.
 *
 * @package FAWpmcp\Abilities\Plugins
 */
final class GetPlugin extends AbstractAbility {
	/**
	 * Returns the ability identifier.
	 *
	 * @return string
	 */
	public function getName(): string {
		return 'fa-wpmcp/get-plugin';
	}

	/**
	 * Returns the ability category.
	 *
	 * @return string
	 */
	public function getCategory(): string {
		return 'plugins';
	}

	/**
	 * Returns the display label.
	 *
	 * @return string
	 */
	public function getLabel(): string {
		return 'Get Plugin';
	}

	/**
	 * Returns the ability description.
	 *
	 * @return string
	 */
	public function getDescription(): string {
		return 'Get details about a specific WordPress plugin.';
	}

	/**
	 * Returns the JSON Schema for input validation.
	 *
	 * @return array
	 */
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

	/**
	 * Returns the JSON Schema for output.
	 *
	 * @return array
	 */
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

	/**
	 * Returns the WordPress capability required.
	 *
	 * @return string
	 */
	public function getRequiredCapability(): string {
		return 'activate_plugins';
	}

	/**
	 * Executes the ability.
	 *
	 * @param array $input Input parameters.
	 * @return array
	 * @throws PluginNotFoundException If the specified plugin is not found.
	 */
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
