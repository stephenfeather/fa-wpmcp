<?php

/**
 * UnscheduleCronEventAbility - removes scheduled WP-Cron events.
 *
 * @package FAWpmcp\Abilities\Cron
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Cron;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Ability to unschedule WordPress cron events.
 *
 * Can remove a specific event by timestamp or all events for a hook.
 *
 * @package FAWpmcp\Abilities\Cron
 */
final class UnscheduleCronEventAbility extends AbstractAbility
{
    /**
     * Get the unique ability name.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/unschedule-cron-event';
    }

    /**
     * Get the ability category.
     *
     * @return string Category name.
     */
    public function getCategory(): string
    {
        return 'cron';
    }

    /**
     * Get the human-readable label.
     *
     * @return string Ability label.
     */
    public function getLabel(): string
    {
        return 'Unschedule Cron Event';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Remove scheduled WordPress cron events. Specify timestamp to remove a specific event, or omit to remove all events for the hook.';
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
            'properties' => array(
                'hook'      => array(
                    'type'        => 'string',
                    'description' => 'The hook name to unschedule.',
                ),
                'timestamp' => array(
                    'type'        => 'integer',
                    'description' => 'Unix timestamp of the specific event to remove. Omit to remove all events for the hook.',
                ),
            ),
            'required'   => array( 'hook' ),
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
                'success'       => array(
                    'type'        => 'boolean',
                    'description' => 'Whether the operation was successful.',
                ),
                'removed_count' => array(
                    'type'        => 'integer',
                    'description' => 'Number of events removed.',
                ),
                'error'         => array(
                    'type'        => 'string',
                    'description' => 'Error message if operation failed.',
                ),
            ),
        );
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
     * @return string 'write' for this ability.
     */
    public function getOperationType(): string
    {
        return 'write';
    }

    /**
     * Get ability annotations.
     *
     * @return array<string, mixed> Annotations array.
     */
    public function getAnnotations(): array
    {
        return array(
            'readonly'     => false,
            'destructive'  => true,
            'idempotent'   => true,
            'instructions' => $this->getDescription(),
        );
    }

    /**
     * Execute the ability.
     *
     * @param array<string, mixed> $input Validated input data.
     * @return array<string, mixed> Unschedule result.
     */
    public function doExecute(array $input): array
    {
        $hook      = (string) $input['hook'];
        $timestamp = $input['timestamp'] ?? null;
        $args      = array();

        // If timestamp provided, unschedule specific event.
        if (null !== $timestamp) {
            return $this->unscheduleSpecificEvent($hook, (int) $timestamp);
        }

        // Otherwise, clear all events for the hook.
        return $this->clearScheduledHook($hook, $args);
    }

    /**
     * Unschedule a specific event by timestamp.
     *
     * @param string $hook      Hook name.
     * @param int    $timestamp Event timestamp.
     * @return array<string, mixed> Result.
     */
    private function unscheduleSpecificEvent(string $hook, int $timestamp): array
    {
        // First, find the event to get its args.
        $cron_array = _get_cron_array();

        if (empty($cron_array) || ! isset($cron_array[ $timestamp ][ $hook ])) {
            return array(
                'success'       => true,
                'removed_count' => 0,
            );
        }

        // Get the first event's args (they should all be the same for the same hash).
        $events = $cron_array[ $timestamp ][ $hook ];
        $args   = reset($events)['args'] ?? array();

        $result = wp_unschedule_event($timestamp, $hook, $args);

        if (is_wp_error($result)) {
            return array(
                'success'       => false,
                'removed_count' => 0,
                'error'         => $result->get_error_message(),
            );
        }

        return array(
            'success'       => true,
            'removed_count' => 1,
        );
    }

    /**
     * Clear all scheduled events for a hook.
     *
     * @param string              $hook Hook name.
     * @param array<string,mixed> $args Event arguments.
     * @return array<string, mixed> Result.
     */
    private function clearScheduledHook(string $hook, array $args): array
    {
        $result = wp_clear_scheduled_hook($hook, $args);

        if (is_wp_error($result)) {
            return array(
                'success'       => false,
                'removed_count' => 0,
                'error'         => $result->get_error_message(),
            );
        }

        return array(
            'success'       => true,
            'removed_count' => (int) $result,
        );
    }
}
