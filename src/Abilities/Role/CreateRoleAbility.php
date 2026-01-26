<?php

/**
 * CreateRoleAbility - creates a new WordPress user role.
 *
 * @package FAWpmcp\Abilities\Role
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Role;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\RoleAlreadyExistsException;

/**
 * Ability to create a new WordPress user role.
 *
 * Creates a role with specified name, display name, and optional capabilities.
 *
 * @package FAWpmcp\Abilities\Role
 */
final class CreateRoleAbility extends AbstractAbility
{
    /**
     * Get the unique ability name.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/create-role';
    }

    /**
     * Get the ability category.
     *
     * @return string Category name.
     */
    public function getCategory(): string
    {
        return 'role';
    }

    /**
     * Get the human-readable label.
     *
     * @return string Ability label.
     */
    public function getLabel(): string
    {
        return 'Create Role';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Create a new WordPress user role with specified capabilities.';
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
                'role'         => array(
                    'type'        => 'string',
                    'description' => 'The role slug (lowercase, no spaces).',
                ),
                'display_name' => array(
                    'type'        => 'string',
                    'description' => 'The role display name.',
                ),
                'capabilities' => array(
                    'type'        => 'object',
                    'description' => 'Capabilities to grant (cap_name => true/false).',
                    'default'     => array(),
                ),
            ),
            'required'   => array( 'role', 'display_name' ),
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
                'name'         => array(
                    'type'        => 'string',
                    'description' => 'The created role slug.',
                ),
                'display_name' => array(
                    'type'        => 'string',
                    'description' => 'The role display name.',
                ),
                'capabilities' => array(
                    'type'        => 'object',
                    'description' => 'Role capabilities.',
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
        return 'promote_users';
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
     * Execute the ability.
     *
     * @param array<string, mixed> $input Validated input data.
     * @return array<string, mixed> Created role details.
     * @throws RoleAlreadyExistsException If role already exists.
     */
    public function doExecute(array $input): array
    {
        $role_slug    = (string) $input['role'];
        $display_name = (string) $input['display_name'];
        $capabilities = $input['capabilities'] ?? array();

        // Check if role already exists.
        $existing = get_role($role_slug);
        if (null !== $existing) {
            throw new RoleAlreadyExistsException(
                sprintf('Role "%s" already exists.', $role_slug)
            );
        }

        // Create the role.
        $wp_roles = wp_roles();
        $new_role = $wp_roles->add_role($role_slug, $display_name, $capabilities);

        return array(
            'name'         => $new_role->name,
            'display_name' => $display_name,
            'capabilities' => $new_role->capabilities,
        );
    }
}
