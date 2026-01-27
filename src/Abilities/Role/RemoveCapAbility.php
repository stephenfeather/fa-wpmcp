<?php

/**
 * RemoveCapAbility - removes capabilities from a WordPress user role.
 *
 * @package FAWpmcp\Abilities\Role
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Role;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\RoleNotFoundException;

/**
 * Ability to remove one or more capabilities from a WordPress user role.
 *
 * Removes specified capabilities from an existing role.
 *
 * @package FAWpmcp\Abilities\Role
 */
final class RemoveCapAbility extends AbstractAbility
{
    /**
     * Get the unique ability name.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/remove-cap';
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
        return 'Remove Capability';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Remove one or more capabilities from a WordPress user role.';
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
                    'description' => 'The role slug to remove capabilities from.',
                ),
                'capabilities' => array(
                    'type'        => 'array',
                    'items'       => array( 'type' => 'string' ),
                    'description' => 'Capabilities to remove.',
                ),
            ),
            'required'   => array( 'role', 'capabilities' ),
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
                'role'               => array(
                    'type'        => 'string',
                    'description' => 'The role slug.',
                ),
                'removed'            => array(
                    'type'        => 'array',
                    'items'       => array( 'type' => 'string' ),
                    'description' => 'Capabilities that were removed.',
                ),
                'total_capabilities' => array(
                    'type'        => 'integer',
                    'description' => 'Total capabilities after removal.',
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
     * @return array<string, mixed> Result with removed capabilities.
     * @throws RoleNotFoundException If role does not exist.
     */
    public function doExecute(array $input): array
    {
        $role_slug    = (string) $input['role'];
        $capabilities = $input['capabilities'] ?? array();

        $role = get_role($role_slug);
        if (null === $role) {
            throw new RoleNotFoundException(
                sprintf('Role "%s" not found.', $role_slug)
            );
        }

        $removed = array();
        foreach ($capabilities as $cap) {
            $cap_string = (string) $cap;
            $role->remove_cap($cap_string);
            $removed[] = $cap_string;
        }

        return array(
            'role'               => $role->name,
            'removed'            => $removed,
            'total_capabilities' => count($role->capabilities),
        );
    }
}
