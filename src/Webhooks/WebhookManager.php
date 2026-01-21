<?php
/**
 * Webhook system orchestrator.
 *
 * @package FAWpmcp
 */

declare(strict_types=1);

namespace FAWpmcp\Webhooks;

use FAWpmcp\Http\PrivacyRedactor;

/**
 * Webhook system orchestrator.
 *
 * Coordinates webhook triggering, queuing, and delivery.
 */
final class WebhookManager implements WebhookManagerInterface {
    /**
     * Constructor.
     *
     * @param WebhookQueue  $queue  Queue manager.
     * @param WebhookSender $sender HTTP sender.
     * @param WebhookConfig $config Configuration.
     */
    public function __construct(
        private readonly WebhookQueue $queue,
        private readonly WebhookSender $sender,
        private readonly WebhookConfig $config,
    ) {}

    /**
     * Trigger webhooks for an event.
     *
     * Builds payload and enqueues webhooks for subscribed URLs.
     * Sensitive fields in input/output are redacted before transmission.
     *
     * @param string               $event   Event name.
     * @param array<string, mixed> $context Event context data.
     */
    public function trigger( string $event, array $context ): void {
        // Redact sensitive fields from input/output before building payload (pure functions).
        $redacted_input  = PrivacyRedactor::redact( $context['input'] );
        $redacted_output = PrivacyRedactor::redact( $context['output'] );

        // Build payload (pure function).
        $payload = PayloadBuilder::build(
            event: $event,
            ability: [
                'name'      => $context['ability_name'],
                'category'  => $context['category'],
                'operation' => $context['operation'],
            ],
            user: [
                'user_id'    => $context['user_id'],
                'user_login' => $context['user_login'],
                'ip'         => $context['ip'],
            ],
            execution: [
                'input'             => $redacted_input,
                'output'            => $redacted_output,
                'success'           => $context['success'],
                'execution_time_ms' => $context['execution_time_ms'],
            ],
        );

        // Get subscribed URLs (side effect: config read).
        $urls = $this->config->getSubscribedUrls( $event );

        // Queue webhooks (side effect: database writes).
        foreach ( $urls as $url ) {
            $this->queue->enqueue( $url, $payload );
        }
    }

    /**
     * Process pending webhooks from queue.
     *
     * Retrieves up to 10 pending webhooks and attempts delivery.
     */
    public function processQueue(): void {
        // Get pending webhooks (side effect: database read).
        $pending = $this->queue->getPending( 10 );

        foreach ( $pending as $webhook ) {
            $this->processWebhook( $webhook );
        }
    }

    /**
     * Process a single webhook.
     *
     * @param array{id: int, url: string, payload: string, attempt_count: int} $webhook Webhook data.
     */
    private function processWebhook( array $webhook ): void {
        // Generate signature (pure function).
        $signature = SignatureGenerator::generate(
            $webhook['payload'],
            $this->config->getSecret()
        );

        // Send HTTP request (side effect).
        $result = $this->sender->send(
            $webhook['url'],
            $webhook['payload'],
            $signature
        );

        // Update queue status (side effect).
        if ( $result->is_success ) {
            $this->queue->markComplete( $webhook['id'] );
        } else {
            $this->handleFailure( $webhook );
        }
    }

    /**
     * Handle webhook delivery failure.
     *
     * Schedules retry or marks as permanently failed.
     *
     * @param array{id: int, url: string, payload: string, attempt_count: int} $webhook Webhook data.
     */
    private function handleFailure( array $webhook ): void {
        $attempts = $webhook['attempt_count'] + 1;

        // Max 3 attempts (0, 1, 2).
        if ( $attempts >= 3 ) {
            $this->queue->markFailed( $webhook['id'], 'Max retries exceeded' );
        } else {
            // Calculate next attempt (pure function).
            $next_attempt = RetryCalculator::calculateNextAttempt( $attempts );
            $this->queue->scheduleRetry( $webhook['id'], $next_attempt );
        }
    }
}
