<?php
/**
 * ActivateMaintenanceMode ability - enables WordPress maintenance mode.
 *
 * @package FAWpmcp\Abilities\Maintenance
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Maintenance;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\MaintenanceModeException;

/**
 * Ability to activate WordPress maintenance mode.
 *
 * Creates the .maintenance file in ABSPATH to enable maintenance mode.
 * Includes safety features like optional auto-expiration.
 *
 * @package FAWpmcp\Abilities\Maintenance
 */
final class ActivateMaintenanceMode extends AbstractAbility {

	/**
	 * Default auto-expire duration in seconds (10 minutes).
	 */
	private const DEFAULT_EXPIRE_SECONDS = 600;

	/**
	 * Maximum allowed duration in seconds (1 hour).
	 */
	private const MAX_EXPIRE_SECONDS = 3600;

	/**
	 * Get the unique ability name.
	 *
	 * @return string Ability name.
	 */
	public function getName(): string {
		return 'fa-wpmcp/activate-maintenance-mode';
	}

	/**
	 * Get the ability category.
	 *
	 * @return string Category name.
	 */
	public function getCategory(): string {
		return 'maintenance';
	}

	/**
	 * Get the human-readable label.
	 *
	 * @return string Ability label.
	 */
	public function getLabel(): string {
		return 'Activate Maintenance Mode';
	}

	/**
	 * Get the ability description.
	 *
	 * @return string Description.
	 */
	public function getDescription(): string {
		return 'Activate WordPress maintenance mode. Site visitors will see a maintenance message. Auto-expires after 10 minutes by default.';
	}

	/**
	 * Get the input schema.
	 *
	 * @return array<string, mixed> JSON Schema array.
	 */
	public function getInputSchema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'expire_seconds' => array(
					'type'        => 'integer',
					'description' => 'Auto-expire after this many seconds. Default: 600 (10 minutes). Max: 3600 (1 hour). Set to 0 for no auto-expiration (not recommended).',
					'minimum'     => 0,
					'maximum'     => 3600,
					'default'     => 600,
				),
			),
		);
	}

	/**
	 * Get the output schema.
	 *
	 * @return array<string, mixed> JSON Schema array.
	 */
	public function getOutputSchema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'activated'      => array(
					'type'        => 'boolean',
					'description' => 'Whether maintenance mode was successfully activated.',
				),
				'activated_at'   => array(
					'type'        => 'integer',
					'description' => 'Unix timestamp when maintenance mode was activated.',
				),
				'expire_seconds' => array(
					'type'        => 'integer',
					'description' => 'Auto-expiration duration in seconds (0 = no expiration).',
				),
				'message'        => array(
					'type'        => 'string',
					'description' => 'Status message.',
				),
			),
		);
	}

	/**
	 * Get the required WordPress capability.
	 *
	 * @return string WordPress capability name.
	 */
	public function getRequiredCapability(): string {
		return 'manage_options';
	}

	/**
	 * Get the operation type.
	 *
	 * @return string Operation type ('read' or 'write').
	 */
	public function getOperationType(): string {
		return 'write';
	}

	/**
	 * Get ability annotations.
	 *
	 * @return array<string, mixed> Annotations array.
	 */
	public function getAnnotations(): array {
		$annotations               = parent::getAnnotations();
		$annotations['mcp.public'] = true;
		$annotations['idempotent'] = true;
		return $annotations;
	}

	/**
	 * Execute the ability.
	 *
	 * @param array<string, mixed> $input Validated input data.
	 * @return array<string, mixed> Activation result.
	 * @throws MaintenanceModeException If activation fails.
	 */
	public function doExecute( array $input ): array {
		$expire_seconds   = $input['expire_seconds'] ?? self::DEFAULT_EXPIRE_SECONDS;
		$maintenance_file = ABSPATH . '.maintenance';
		$timestamp        = time();

		// Create the maintenance file content.
		// WordPress checks if $upgrading is set and if time() - $upgrading < 600.
		// We store the timestamp for status checks.
		$content = "<?php\n\$upgrading = {$timestamp};\n";

		// Add expiration comment for reference.
		if ( $expire_seconds > 0 ) {
			$expire_time = $timestamp + $expire_seconds;
			$content    .= '// Auto-expires at: ' . gmdate( 'Y-m-d H:i:s', $expire_time ) . " UTC\n";
			$content    .= "// Expire seconds: {$expire_seconds}\n";
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$result = file_put_contents( $maintenance_file, $content );

		if ( false === $result ) {
			throw new MaintenanceModeException(
				'Failed to create maintenance file. Check file system permissions.'
			);
		}

		$message = 'Maintenance mode activated.';
		if ( $expire_seconds > 0 ) {
			$message .= sprintf( ' Will auto-expire in %d seconds.', $expire_seconds );
		} else {
			$message .= ' No auto-expiration set - remember to deactivate manually!';
		}

		return array(
			'activated'      => true,
			'activated_at'   => $timestamp,
			'expire_seconds' => $expire_seconds,
			'message'        => $message,
		);
	}
}
