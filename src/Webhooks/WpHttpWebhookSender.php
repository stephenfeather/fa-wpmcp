<?php
/**
 * WordPress HTTP API webhook sender.
 *
 * @package FAWpmcp
 */

declare(strict_types=1);

namespace FAWpmcp\Webhooks;

use FAWpmcp\ValueObjects\WebhookResult;

/**
 * WordPress HTTP API webhook sender implementation.
 *
 * Uses wp_remote_post() for HTTP delivery.
 */
final class WpHttpWebhookSender implements WebhookSender {

	/**
	 * Send webhook via HTTP POST.
	 *
	 * @param string $url       Webhook URL.
	 * @param string $payload   JSON payload.
	 * @param string $signature HMAC signature.
	 *
	 * @return WebhookResult Result of delivery attempt.
	 */
	public function send( string $url, string $payload, string $signature ): WebhookResult {
		$response = wp_remote_post(
			$url,
			array(
				'method'      => 'POST',
				'timeout'     => 10,
				'redirection' => 0,
				'headers'     => array(
					'Content-Type'         => 'application/json',
					'X-FA-WPMCP-Signature' => $signature,
				),
				'body'        => $payload,
			)
		);

		// Handle WP_Error.
		if ( is_wp_error( $response ) ) {
			return new WebhookResult(
				is_success: false,
				status_code: 0,
				response_body: '',
				error_message: $response->get_error_message(),
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );
		$is_success  = $status_code >= 200 && $status_code < 300;

		return new WebhookResult(
			is_success: $is_success,
			status_code: $status_code,
			response_body: $body,
			error_message: $is_success ? null : wp_remote_retrieve_response_message( $response ),
		);
	}
}
