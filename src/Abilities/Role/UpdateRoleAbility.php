<?php

/**
 * UpdateRoleAbility - updates capabilities of a WordPress user role.
 *
 * @package FAWpmcp\Abilities\Role
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Role;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\RoleNotFoundException;

/**
 * Ability to update capabilities of a WordPress user role.
 *
 * Supports adding and removing capabilities from existing roles.
 *
 * @package FAWpmcp\Abilities\Role
 */
final class UpdateRoleAbility extends AbstractAbility
{
    /**
     * Get the unique ability name.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/update-role';
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
        return 'Update Role';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Update capabilities of a WordPress user role by adding or removing capabilities.';
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
                'role'        => array(
                    'type'        => 'string',
                    'description' => 'The role slug to update.',
                ),
                'add_caps'    => array(
                    'type'        => 'array',
                    'description' => 'Array of capability names to add.',
                    'items'       => array( 'type' => 'string' ),
                ),
                'remove_caps' => array(
                    'type'        => 'array',
                    'description' => 'Array of capability names to remove.',
                    'items'       => array( 'type' => 'string' ),
                ),
            ),
            'required'   => array( 'role' ),
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
                    'description' => 'The updated role slug.',
                ),
                'capabilities' => array(
                    'type'        => 'object',
                    'description' => 'Updated role capabilities.',
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
     * @return array<string, mixed> Updated role details.
     * @throws RoleNotFoundException If role does not exist.
     */
    public function doExecute(array $input): array
    {
        $role_slug   = (string) $input['role'];
        $add_caps    = $input['add_caps'] ?? array();
        $remove_caps = $input['remove_caps'] ?? array();

        // Get the role.
        $role = get_role($role_slug);
        if (null === $role) {
            throw new RoleNotFoundException(
                sprintf('Role "%s" not found.', $role_slug)
            );
        }

        // Add capabilities.
        foreach ($add_caps as $cap) {
            $role->add_cap((string) $cap, true);
        }

        // Remove capabilities.
        foreach ($remove_caps as $cap) {
            $role->remove_cap((string) $cap);
        }

        return array(
            'name'         => $role->name,
            'capabilities' => $role->capabilities,
        );
    }
}
