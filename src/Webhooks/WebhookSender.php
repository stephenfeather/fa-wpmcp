<?php

/**
 * Webhook HTTP delivery interface.
 *
 * @package FAWpmcp
 */

declare(strict_types=1);

namespace FAWpmcp\Webhooks;

use FAWpmcp\ValueObjects\WebhookResult;

/**
 * Interface for webhook HTTP delivery.
 *
 * Implementations handle HTTP POST requests to webhook URLs.
 */
interface WebhookSender
{
    /**
     * Send webhook via HTTP POST.
     *
     * @param string $url       Webhook URL.
     * @param string $payload   JSON payload.
     * @param string $signature HMAC signature.
     *
     * @return WebhookResult Result of delivery attempt.
     */
    public function send(string $url, string $payload, string $signature): WebhookResult;
}
