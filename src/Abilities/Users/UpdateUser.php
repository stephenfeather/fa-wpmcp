<?php

/**
 * UpdateUser ability - updates an existing WordPress user.
 *
 * @package FAWpmcp\Abilities\Users
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Users;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\RoleNotAllowedException;
use FAWpmcp\Exceptions\UserNotFoundException;
use FAWpmcp\Exceptions\UserUpdateException;

/**
 * Ability to update an existing WordPress user.
 *
 * Supports updating:
 * - Email, password
 * - Display name, first name, last name
 * - Role
 * - Website, description
 *
 * @package FAWpmcp\Abilities\Users
 */
final class UpdateUser extends AbstractAbility
{
    /**
     * Role policy for validating role assignments.
     *
     * @var RolePolicy
     */
    private RolePolicy $rolePolicy;

    /**
     * Constructor.
     *
     * @param RolePolicy|null $rolePolicy Optional role policy instance.
     */
    public function __construct(?RolePolicy $rolePolicy = null)
    {
        $this->role_policy = $rolePolicy ?? new RolePolicy();
    }

    /**
     * Returns the ability identifier.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/update-user';
    }

    /**
     * Returns the ability category.
     *
     * @return string Category name.
     */
    public function getCategory(): string
    {
        return 'users';
    }

    /**
     * Returns the display label.
     *
     * @return string Ability label.
     */
    public function getLabel(): string
    {
        return 'Update User';
    }

    /**
     * Returns the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Update an existing WordPress user profile including email, password, role, and profile information.';
    }

    /**
     * Returns the JSON Schema for input validation.
     *
     * @return array<string, mixed> JSON Schema array.
     */
    public function getInputSchema(): array
    {
        return array(
            'type'       => 'object',
            'properties' => array(
                'user_id'      => array(
                    'type'        => 'integer',
                    'description' => 'User ID to update.',
                    'minimum'     => 1,
                ),
                'email'        => array(
                    'type'        => 'string',
                    'description' => 'New email address.',
                    'format'      => 'email',
                ),
                'password'     => array(
                    'type'        => 'string',
                    'description' => 'New password.',
                ),
                'role'         => array(
                    'type'        => 'string',
                    'description' => sprintf(
                        'New user role. Maximum assignable role: %s.',
                        $this->role_policy->getMaxRole()
                    ),
                    'enum'        => array_values($this->role_policy->getAllowedRoles()),
                ),
                'first_name'   => array(
                    'type'        => 'string',
                    'description' => 'User first name.',
                ),
                'last_name'    => array(
                    'type'        => 'string',
                    'description' => 'User last name.',
                ),
                'display_name' => array(
                    'type'        => 'string',
                    'description' => 'Display name.',
                ),
                'website'      => array(
                    'type'        => 'string',
                    'description' => 'User website URL.',
                    'format'      => 'uri',
                ),
                'description'  => array(
                    'type'        => 'string',
                    'description' => 'User biographical info.',
                ),
            ),
            'required'   => array( 'user_id' ),
        );
    }

    /**
     * Returns the JSON Schema for output.
     *
     * @return array<string, mixed> JSON Schema array.
     */
    public function getOutputSchema(): array
    {
        return array(
            'type'       => 'object',
            'properties' => array(
                'user_id'        => array(
                    'type'        => 'integer',
                    'description' => 'The ID of the updated user.',
                ),
                'updated_fields' => array(
                    'type'        => 'array',
                    'description' => 'List of fields that were updated.',
                    'items'       => array( 'type' => 'string' ),
                ),
            ),
        );
    }

    /**
     * Returns the WordPress capability required.
     *
     * @return string WordPress capability name.
     */
    public function getRequiredCapability(): string
    {
        return 'edit_users';
    }

    /**
     * Returns the operation type.
     *
     * @return string 'write' for update operations.
     */
    public function getOperationType(): string
    {
        return 'write';
    }

    /**
     * Executes the ability.
     *
     * @param array<string, mixed> $input Validated input data.
     * @return array<string, mixed> Update result.
     * @throws RoleNotAllowedException If role exceeds max allowed.
     * @throws UserNotFoundException If user not found.
     * @throws UserUpdateException If update fails.
     */
    public function doExecute(array $input): array
    {
        $user_id = (int) $input['user_id'];

        // Verify user exists.
        $user = get_userdata($user_id);
        if (! $user || ! $user->exists()) {
            throw new UserNotFoundException(
                "User with ID {$user_id} not found."
            );
        }

        // Pure transformation: build update data with sanitization.
        $update_data = $this->buildUpdateData($input);

        if (empty($update_data)) {
            // Nothing to update.
            return array(
                'user_id'        => $user_id,
                'updated_fields' => array(),
            );
        }

        // Add user ID to update data.
        $update_data['ID'] = $user_id;

        // Side effect: update user in database.
        $result = wp_update_user($update_data);

        // Error handling.
        if (is_wp_error($result)) {
            throw new UserUpdateException(
                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Internal exception message.
                'Failed to update user: ' . $result->get_error_message()
            );
        }

        // Pure transformation: format response.
        return $this->formatResponse($user_id, $update_data);
    }

    /**
     * Build update data array with sanitization.
     *
     * Pure function - sanitizes and transforms input into update data.
     * Only includes fields that are provided in input.
     *
     * @param array<string, mixed> $input Input parameters.
     * @return array<string, mixed> Sanitized update data.
     */
    private function buildUpdateData(array $input): array
    {
        $update_data = array();

        // Update email if provided.
        if (isset($input['email'])) {
            $update_data['user_email'] = sanitize_email($input['email']);
        }

        // Update password if provided.
        if (isset($input['password']) && '' !== $input['password']) {
            $update_data['user_pass'] = $input['password'];
        }

        // Update role if provided.
        if (isset($input['role'])) {
            $update_data['role'] = $this->validateRole($input['role']);
        }

        // Update display name if provided.
        if (isset($input['display_name'])) {
            $update_data['display_name'] = sanitize_text_field($input['display_name']);
        }

        // Update first name if provided.
        if (isset($input['first_name'])) {
            $update_data['first_name'] = sanitize_text_field($input['first_name']);
        }

        // Update last name if provided.
        if (isset($input['last_name'])) {
            $update_data['last_name'] = sanitize_text_field($input['last_name']);
        }

        // Update website if provided.
        if (isset($input['website'])) {
            $update_data['user_url'] = esc_url_raw($input['website']);
        }

        // Update description if provided.
        if (isset($input['description'])) {
            $update_data['description'] = sanitize_textarea_field($input['description']);
        }

        return $update_data;
    }

    /**
     * Validate user role against policy.
     *
     * Validates that the role is allowed per the max API role configuration.
     * Custom roles (non-standard) are allowed through.
     *
     * @param string $role Input role.
     * @return string Valid role.
     * @throws RoleNotAllowedException If role exceeds max allowed.
     */
    private function validateRole(string $role): string
    {
        // Check if role is allowed per policy (throws if not).
        $this->role_policy->validateRole($role);

        return $role;
    }

    /**
     * Format the response after user update.
     *
     * @param int                  $user_id     Updated user ID.
     * @param array<string, mixed> $update_data Data that was updated.
     * @return array<string, mixed> Response data.
     */
    private function formatResponse(int $user_id, array $update_data): array
    {
        // Extract field names (remove 'ID' from list).
        $updated_fields = array_keys($update_data);
        $updated_fields = array_filter(
            $updated_fields,
            function ($key) {
                return 'ID' !== $key;
            }
        );

        return array(
            'user_id'        => $user_id,
            'updated_fields' => array_values($updated_fields),
        );
    }
}
