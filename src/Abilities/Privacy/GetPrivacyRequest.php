<?php
/**
 * GetPrivacyRequest ability - retrieves a specific privacy request.
 *
 * @package FAWpmcp\Abilities\Privacy
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Privacy;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Ability to get details about a specific privacy request.
 *
 * Returns complete information about an export or erasure request including:
 * - Request ID, email, type, status
 * - Creation and confirmation timestamps
 *
 * @package FAWpmcp\Abilities\Privacy
 */
final class GetPrivacyRequest extends AbstractAbility {
	/**
	 * Get the unique ability name.
	 *
	 * @return string Ability name.
	 */
	public function getName(): string {
		return 'fa-wpmcp/get-privacy-request';
	}

	/**
	 * Get the ability category.
	 *
	 * @return string Category name.
	 */
	public function getCategory(): string {
		return 'privacy';
	}

	/**
	 * Get the human-readable label.
	 *
	 * @return string Ability label.
	 */
	public function getLabel(): string {
		return 'Get Privacy Request';
	}

	/**
	 * Get the ability description.
	 *
	 * @return string Description.
	 */
	public function getDescription(): string {
		return 'Get details about a specific privacy request (export or erasure) by request ID.';
	}

	/**
	 * Get the input schema.
	 *
	 * @return array<string, mixed> JSON Schema array.
	 */
	public function getInputSchema(): array {
		return array(
			'type'       => 'object',
			'required'   => array( 'request_id' ),
			'properties' => array(
				'request_id' => array(
					'type'        => 'integer',
					'description' => 'The privacy request ID.',
					'minimum'     => 1,
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
				'id'           => array(
					'type'        => 'integer',
					'description' => 'Request ID.',
				),
				'email'        => array(
					'type'        => 'string',
					'description' => 'Email address of the requester.',
				),
				'type'         => array(
					'type'        => 'string',
					'description' => 'Request type (export_personal_data or remove_personal_data).',
				),
				'status'       => array(
					'type'        => 'string',
					'description' => 'Request status (request-pending, request-confirmed, request-failed, request-completed).',
				),
				'created_at'   => array(
					'type'        => 'string',
					'description' => 'When the request was created.',
				),
				'confirmed_at' => array(
					'type'        => 'string',
					'description' => 'When the request was confirmed (if confirmed).',
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
	 * Execute the ability.
	 *
	 * @param array<string, mixed> $input Validated input data.
	 * @return array<string, mixed> Request details.
	 * @throws \RuntimeException If request not found.
	 */
	public function doExecute( array $input ): array {
		$request_id = (int) $input['request_id'];

		// Get the request post.
		$request = get_post( $request_id );

		if ( ! $request || 'user_request' !== $request->post_type ) {
			throw new \RuntimeException( 'Privacy request not found' );
		}

		// Get request metadata.
		$email               = get_post_meta( $request_id, '_wp_user_request_user_email', true );
		$action_name         = get_post_meta( $request_id, 'action_name', true );
		$confirmed_timestamp = get_post_meta( $request_id, '_wp_user_request_confirmed_timestamp', true );
		$confirmed_at        = $confirmed_timestamp ? gmdate( 'Y-m-d H:i:s', (int) $confirmed_timestamp ) : null;

		return array(
			'id'           => $request->ID,
			'email'        => $email,
			'type'         => $action_name,
			'status'       => $request->post_status,
			'created_at'   => $request->post_date,
			'confirmed_at' => $confirmed_at,
		);
	}
}
