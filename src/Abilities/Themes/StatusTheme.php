<?php
declare(strict_types=1);
namespace FAWpmcp\Abilities\Themes;

use FAWpmcp\Abilities\AbstractAbility;

final class StatusTheme extends AbstractAbility {
	public function getName(): string {
		return 'fa-wpmcp/status-theme';
	}

	public function getCategory(): string {
		return 'themes';
	}

	public function getLabel(): string {
		return 'Theme Status';
	}

	public function getDescription(): string {
		return 'Get status details for a WordPress theme.';
	}

	public function getInputSchema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'stylesheet' => array(
					'type'        => 'string',
					'description' => 'Theme stylesheet name.',
				),
			),
			'required'   => array( 'stylesheet' ),
		);
	}

	public function getOutputSchema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'name'    => array( 'type' => 'string' ),
				'status'  => array( 'type' => 'string' ),
				'version' => array( 'type' => 'string' ),
				'author'  => array( 'type' => 'string' ),
			),
		);
	}

	public function getRequiredCapability(): string {
		return 'switch_themes';
	}

	public function doExecute( array $input ): array {
		$theme        = wp_get_theme( $input['stylesheet'] );
		$active_theme = get_option( 'stylesheet' );
		$is_active    = $theme->get_stylesheet() === $active_theme;

		return array(
			'name'    => $theme->get( 'Name' ),
			'status'  => $is_active ? 'Active' : 'Inactive',
			'version' => $theme->get( 'Version' ),
			'author'  => $theme->get( 'Author' ),
		);
	}
}
