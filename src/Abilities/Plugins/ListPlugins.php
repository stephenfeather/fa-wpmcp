<?php
/**
 * ListPlugins ability - lists installed WordPress plugins.
 *
 * @package FAWpmcp\Abilities\Plugins
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Plugins;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Ability to list installed WordPress plugins.
 *
 * @package FAWpmcp\Abilities\Plugins
 */
final class ListPlugins extends AbstractAbility {
	/**
	 * Returns the ability identifier.
	 *
	 * @return string
	 */
	public function getName(): string {
		return 'fa-wpmcp/list-plugins';
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
		return 'List Plugins';
	}

	/**
	 * Returns the ability description.
	 *
	 * @return string
	 */
	public function getDescription(): string {
		return 'List installed WordPress plugins with their activation status and metadata.';
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
				'status' => array(
					'type'        => 'string',
					'description' => 'Filter by status (active, inactive, all).',
					'enum'        => array( 'active', 'inactive', 'all' ),
				),
			),
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
				'plugins' => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'plugin'  => array( 'type' => 'string' ),
							'name'    => array( 'type' => 'string' ),
							'version' => array( 'type' => 'string' ),
							'active'  => array( 'type' => 'boolean' ),
						),
					),
				),
				'total'   => array( 'type' => 'integer' ),
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
	 */
	public function doExecute( array $input ): array {
		$all_plugins = get_plugins();
		$status      = $input['status'] ?? 'all';

		$plugins = array();
		foreach ( $all_plugins as $plugin_file => $plugin_data ) {
			$is_active = is_plugin_active( $plugin_file );

			if ( 'active' === $status && ! $is_active ) {
				continue;
			}
			if ( 'inactive' === $status && $is_active ) {
				continue;
			}

			$plugins[] = array(
				'plugin'  => $plugin_file,
				'name'    => $plugin_data['Name'],
				'version' => $plugin_data['Version'],
				'active'  => $is_active,
			);
		}

		return array(
			'plugins' => $plugins,
			'total'   => count( $plugins ),
		);
	}
}
