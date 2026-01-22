<?php
declare(strict_types=1);
namespace FAWpmcp\Abilities\Themes;

use FAWpmcp\Abilities\AbstractAbility;

final class ActivateTheme extends AbstractAbility {
	public function getName(): string {
		return 'fa-wpmcp/activate-theme';
	}

	public function getCategory(): string {
		return 'themes';
	}

	public function getLabel(): string {
		return 'Activate Theme';
	}

	public function getDescription(): string {
		return 'Activate a WordPress theme.';
	}

	public function getInputSchema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'stylesheet' => array(
					'type'        => 'string',
					'description' => 'Theme stylesheet name to activate.',
				),
			),
			'required'   => array( 'stylesheet' ),
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
		return 'switch_themes';
	}

	public function getOperationType(): string {
		return 'write';
	}

	public function doExecute( array $input ): array {
		switch_theme( $input['stylesheet'] );
		return array( 'success' => true );
	}
}
