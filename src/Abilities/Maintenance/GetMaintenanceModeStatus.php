<?php

/**
 * GetMaintenanceModeStatus ability - checks WordPress maintenance mode status.
 *
 * @package FAWpmcp\Abilities\Maintenance
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Maintenance;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Ability to check WordPress maintenance mode status.
 *
 * Returns whether maintenance mode is active, when it was activated,
 * and how long it has been active.
 *
 * @package FAWpmcp\Abilities\Maintenance
 */
final class GetMaintenanceModeStatus extends AbstractAbility
{
    /**
     * Get the unique ability name.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/get-maintenance-mode-status';
    }

    /**
     * Get the ability category.
     *
     * @return string Category name.
     */
    public function getCategory(): string
    {
        return 'maintenance';
    }

    /**
     * Get the human-readable label.
     *
     * @return string Ability label.
     */
    public function getLabel(): string
    {
        return 'Get Maintenance Mode Status';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Check if WordPress maintenance mode is active and get details about when it was activated.';
    }

    /**
     * Get the input schema.
     *
     * @return array<string, mixed> JSON Schema array.
     */
    public function getInputSchema(): array
    {
        return array(
            'type'       => 'object',
            'properties' => new \stdClass(),
        );
    }

    /**
     * Get the output schema.
     *
     * @return array<string, mixed> JSON Schema array.
     */
    public function getOutputSchema(): array
    {
        return array(
            'type'       => 'object',
            'properties' => array(
                'active'       => array(
                    'type'        => 'boolean',
                    'description' => 'Whether maintenance mode is currently active.',
                ),
                'activated_at' => array(
                    'type'        => 'integer',
                    'description' => 'Unix timestamp when maintenance mode was activated (0 if not active).',
                ),
                'duration'     => array(
                    'type'        => 'integer',
                    'description' => 'Seconds since maintenance mode was activated (0 if not active).',
                ),
                'message'      => array(
                    'type'        => 'string',
                    'description' => 'Human-readable status message.',
                ),
            ),
        );
    }

    /**
     * Get ability annotations.
     *
     * @return array<string, mixed> Annotations array.
     */
    public function getAnnotations(): array
    {
        $annotations               = parent::getAnnotations();
        $annotations['mcp.public'] = true;
        return $annotations;
    }

    /**
     * Get the required WordPress capability.
     *
     * @return string WordPress capability name.
     */
    public function getRequiredCapability(): string
    {
        return 'manage_options';
    }

    /**
     * Get the operation type.
     *
     * @return string Operation type ('read' or 'write').
     */
    public function getOperationType(): string
    {
        return 'read';
    }

    /**
     * Execute the ability.
     *
     * @param array<string, mixed> $input Validated input data.
     * @return array<string, mixed> Maintenance mode status.
     */
    public function doExecute(array $input): array
    {
        $maintenance_file = ABSPATH . '.maintenance';

        if (! file_exists($maintenance_file)) {
            return array(
                'active'       => false,
                'activated_at' => 0,
                'duration'     => 0,
                'message'      => 'Maintenance mode is not active.',
            );
        }

        // Read the maintenance file to get the timestamp.
        $upgrading = 0;
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        $content = file_get_contents($maintenance_file);
        if (false !== $content && preg_match('/\$upgrading\s*=\s*(\d+)/', $content, $matches)) {
            $upgrading = (int) $matches[1];
        }

        $duration = ( $upgrading > 0 ) ? ( time() - $upgrading ) : 0;

        return array(
            'active'       => true,
            'activated_at' => $upgrading,
            'duration'     => $duration,
            'message'      => sprintf(
                'Maintenance mode active since %s (%d seconds ago).',
                gmdate('Y-m-d H:i:s', $upgrading),
                $duration
            ),
        );
    }
}
