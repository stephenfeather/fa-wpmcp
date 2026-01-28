<?php

/**
 * ListCronEventsAbility - lists all scheduled WP-Cron events.
 *
 * @package FAWpmcp\Abilities\Cron
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Cron;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Ability to list all scheduled WordPress cron events.
 *
 * Returns an array of cron events with hook names, timestamps, schedules, and arguments.
 * Supports optional filtering by hook name pattern.
 *
 * @package FAWpmcp\Abilities\Cron
 */
final class ListCronEventsAbility extends AbstractAbility
{
    /**
     * Get the unique ability name.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/list-cron-events';
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
        return 'List Cron Events';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'List all scheduled WordPress cron events with optional hook name filtering.';
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
                'hook' => array(
                    'type'        => 'string',
                    'description' => 'Optional hook name pattern to filter events (partial match).',
                ),
            ),
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
                'events' => array(
                    'type'        => 'array',
                    'description' => 'List of cron events.',
                    'items'       => array(
                        'type'       => 'object',
                        'properties' => array(
                            'hook'      => array( 'type' => 'string' ),
                            'timestamp' => array( 'type' => 'integer' ),
                            'schedule'  => array(
                                'type' => 'string',
                                'description' => 'Recurrence schedule name, or empty string for single (non-recurring) events.',
                            ),
                            'args'      => array( 'type' => 'array' ),
                        ),
                    ),
                ),
                'total'  => array(
                    'type'        => 'integer',
                    'description' => 'Total number of events found.',
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
     * Execute the ability.
     *
     * @param array<string, mixed> $input Validated input data.
     * @return array<string, mixed> List of cron events.
     */
    public function doExecute(array $input): array
    {
        $hook_filter = $input['hook'] ?? '';
        $cron_array  = _get_cron_array();

        if (empty($cron_array) || ! is_array($cron_array)) {
            return array(
                'events' => array(),
                'total'  => 0,
            );
        }

        $events = $this->extractEventsFromCronArray($cron_array, $hook_filter);

        return array(
            'events' => $events,
            'total'  => count($events),
        );
    }

    /**
     * Extract events from cron array with optional hook filtering.
     *
     * @param array<int, array<string, array<int, array<string, mixed>>>> $cron_array Cron array.
     * @param string $hook_filter Optional hook name filter.
     * @return array<int, array<string, mixed>> Extracted events.
     */
    private function extractEventsFromCronArray(array $cron_array, string $hook_filter): array
    {
        $events = array();

        foreach ($cron_array as $timestamp => $hooks) {
            $timestamp_events = $this->extractEventsAtTimestamp($hooks, (int) $timestamp, $hook_filter);
            $events           = array_merge($events, $timestamp_events);
        }

        return $events;
    }

    /**
     * Extract events at a specific timestamp with optional hook filtering.
     *
     * @param array<string, array<int, array<string, mixed>>> $hooks Hooks array at timestamp.
     * @param int $timestamp Unix timestamp.
     * @param string $hook_filter Optional hook name filter.
     * @return array<int, array<string, mixed>> Events at this timestamp.
     */
    private function extractEventsAtTimestamp(array $hooks, int $timestamp, string $hook_filter): array
    {
        $events = array();

        foreach ($hooks as $hook => $events_data) {
            if ($this->shouldSkipHook($hook, $hook_filter)) {
                continue;
            }

            foreach ($events_data as $event_data) {
                $events[] = $this->buildEventEntry($hook, $timestamp, $event_data);
            }
        }

        return $events;
    }

    /**
     * Check if a hook should be skipped based on filter.
     *
     * @param string $hook Hook name.
     * @param string $hook_filter Filter pattern.
     * @return bool True if hook should be skipped.
     */
    private function shouldSkipHook(string $hook, string $hook_filter): bool
    {
        return ! empty($hook_filter) && strpos($hook, $hook_filter) === false;
    }

    /**
     * Build a single event entry array.
     *
     * @param string $hook Hook name.
     * @param int $timestamp Unix timestamp.
     * @param array<string, mixed> $event_data Event data from cron array.
     * @return array<string, mixed> Formatted event entry.
     */
    private function buildEventEntry(string $hook, int $timestamp, array $event_data): array
    {
        $schedule = $event_data['schedule'] ?? false;

        return array(
            'hook'      => $hook,
            'timestamp' => $timestamp,
            'schedule'  => is_string($schedule) ? $schedule : '',
            'args'      => $event_data['args'] ?? array(),
        );
    }
}
