<?php

/**
 * Immutable webhook result value object.
 *
 * @package FAWpmcp
 */

declare(strict_types=1);

namespace FAWpmcp\ValueObjects;

/**
 * Immutable webhook result value object.
 *
 * @psalm-immutable
 */
final class WebhookResult
{
    /**
     * Constructor.
     *
     * @param bool        $is_success     Whether the webhook delivery succeeded.
     * @param int         $status_code    HTTP status code.
     * @param string      $response_body  Response body.
     * @param string|null $error_message  Error message (null if successful).
     */
    public function __construct(
        public readonly bool $is_success,
        public readonly int $status_code,
        public readonly string $response_body,
        public readonly ?string $error_message,
    ) {
    }
}
