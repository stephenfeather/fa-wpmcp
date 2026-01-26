<?php

/**
 * Immutable webhook payload value object.
 *
 * @package FAWpmcp
 */

declare(strict_types=1);

namespace FAWpmcp\ValueObjects;

use DateTimeImmutable;

/**
 * Immutable webhook payload value object.
 *
 * @psalm-immutable
 */
final class WebhookPayload
{
    /**
     * Constructor.
     *
     * @param string            $event             Event name.
     * @param DateTimeImmutable $timestamp         Event timestamp.
     * @param array             $ability           Ability information.
     * @param array             $user              User information.
     * @param array             $input             Input parameters.
     * @param array             $output            Output data.
     * @param bool              $success           Success status.
     * @param int               $execution_time_ms Execution time in milliseconds.
     */
    public function __construct(
        public readonly string $event,
        public readonly DateTimeImmutable $timestamp,
        public readonly array $ability,
        public readonly array $user,
        public readonly array $input,
        public readonly array $output,
        public readonly bool $success,
        public readonly int $execution_time_ms,
    ) {
    }

    /**
     * Convert payload to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array(
            'event'             => $this->event,
            'timestamp'         => $this->timestamp->format('c'),
            'ability'           => $this->ability,
            'user'              => $this->user,
            'input'             => $this->input,
            'output'            => $this->output,
            'success'           => $this->success,
            'execution_time_ms' => $this->execution_time_ms,
        );
    }

    /**
     * Convert payload to JSON string.
     *
     * @return string JSON representation.
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}
