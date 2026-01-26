<?php
/**
 * CreateErasureRequest ability - creates a personal data erasure request.
 *
 * @package FAWpmcp\Abilities\Privacy
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Privacy;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\PrivacyRequestException;

/**
 * Ability to create a personal data erasure request for GDPR compliance.
 *
 * Creates a request following WordPress's built-in privacy tools.
 * The request will be in 'request-pending' status until confirmed by the user.
 *
 * @package FAWpmcp\Abilities\Privacy
 */
final class CreateErasureRequest extends AbstractAbility {
	/**
	 * Get the unique ability name.
	 *
	 * @return string Ability name.
	 */
	public function getName(): string {
		return 'fa-wpmcp/create-erasure-request';
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
		return 'Create Erasure Request';
	}

	/**
	 * Get the ability description.
	 *
	 * @return string Description.
	 */
	public function getDescription(): string {
		return 'Create a personal data erasure request for GDPR compliance. The user will receive a confirmation email.';
	}

	/**
	 * Get the input schema.
	 *
	 * @return array<string, mixed> JSON Schema array.
	 */
	public function getInputSchema(): array {
		return array(
			'type'       => 'object',
			'required'   => array( 'email' ),
			'properties' => array(
				'email'       => array(
					'type'        => 'string',
					'format'      => 'email',
					'description' => 'Email address of the user requesting data erasure.',
				),
				'description' => array(
					'type'        => 'string',
					'description' => 'Optional description or reason for the request.',
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
				'success'      => array(
					'type'        => 'boolean',
					'description' => 'Whether the request was created successfully.',
				),
				'request_id'   => array(
					'type'        => 'integer',
					'description' => 'The ID of the created request.',
				),
				'status'       => array(
					'type'        => 'string',
					'description' => 'Current status of the request (request-pending, request-confirmed, request-failed, request-completed).',
				),
				'email'        => array(
					'type'        => 'string',
					'description' => 'Email address of the requester.',
				),
				'confirmed_at' => array(
					'type'        => 'string',
					'description' => 'Timestamp when the request was confirmed (if confirmed).',
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
	 * @return string Operation type (read, write, delete).
	 */
	public function getOperationType(): string {
		return 'write';
	}

	/**
	 * Get ability annotations.
	 *
	 * Create operations are non-idempotent - repeated calls create new resources.
	 *
	 * @return array<string, mixed> Annotations array.
	 */
	public function getAnnotations(): array {
		$annotations               = parent::getAnnotations();
		$annotations['idempotent'] = false;
		return $annotations;
	}

	/**
	 * Execute the ability.
	 *
	 * @param array<string, mixed> $input Validated input data.
	 * @return array<string, mixed> Request creation result.
	 * @throws PrivacyRequestException If request creation fails.
	 */
	public function doExecute( array $input ): array {
		$email = $input['email'];
		$data  = array();

		if ( isset( $input['description'] ) && '' !== $input['description'] ) {
			$data['description'] = $input['description'];
		}

		// Create the erasure request.
		$request_id = wp_create_user_request( $email, 'remove_personal_data', $data );

		if ( is_wp_error( $request_id ) ) {
			throw new PrivacyRequestException(
				sprintf( 'Failed to create erasure request: %s', $request_id->get_error_message() )
			);
		}

		// Get the request post to return details.
		$request = get_post( $request_id );

		if ( ! $request ) {
			throw new PrivacyRequestException( 'Failed to retrieve created request.' );
		}

		// Get confirmation timestamp if available.
		$confirmed_timestamp = get_post_meta( $request_id, '_wp_user_request_confirmed_timestamp', true );
		$confirmed_at        = $confirmed_timestamp ? gmdate( 'Y-m-d H:i:s', (int) $confirmed_timestamp ) : null;

		return array(
			'success'      => true,
			'request_id'   => $request_id,
			'status'       => $request->post_status,
			'email'        => $email,
			'confirmed_at' => $confirmed_at,
		);
	}
}
